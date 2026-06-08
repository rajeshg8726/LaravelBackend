<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    /** GET /api/admin/users?type=candidate&page=&search= */
   /** GET /api/admin/users?type=candidate&page=&search= */
public function index(Request $request)
{
    $perPage    = 15;
    $type       = $request->type ?? 'candidate';  // 'candidate' or 'employer'
    $isEmployer = ($type === 'employer');          // true or false

    $query = User::where('is_employer', $isEmployer);  // ✅ uses is_employer column

    if ($s = $request->search) {
        $query->where(function ($q) use ($s) {
            $q->where('full_name', 'like', "%{$s}%")
              ->orWhere('email',  'like', "%{$s}%");
        });
    }

    $paginated = $query->latest()->paginate($perPage);
    $paginated->getCollection()->makeHidden(['resume_text', 'skills', 'work_experience', 'education']);

    return response()->json([
        'success'    => true,
        'users'      => $paginated->items(),
        'total'      => $paginated->total(),
        'totalPages' => $paginated->lastPage(),
    ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
}


    /** PUT /api/admin/users/{id}/toggle-status */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);
        return response()->json(['success' => true, 'user' => $user->fresh()], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /** PUT /api/admin/users/{id}/revoke-pro */
    public function revokePro($id)
    {
        $user = User::findOrFail($id);
        
        if (!$user->is_pro) {
            return response()->json([
                'success' => false,
                'message' => 'User does not have an active Pro plan.'
            ], 400);
        }

        $user->update(['is_pro' => false]);
        
        return response()->json([
            'success' => true, 
            'message' => 'Pro plan revoked successfully without initiating an automatic refund.',
            'user' => $user->fresh()
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /** GET /api/admin/pro-subscribers */
    public function proSubscribers(Request $request)
    {
        $perPage = 15;
        $query = User::where('is_employer', false)->where('is_pro', true);

        if ($s = $request->search) {
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $paginated = $query->latest()->paginate($perPage);
        $paginated->getCollection()->makeHidden(['resume_text', 'skills', 'work_experience', 'education']);

        return response()->json([
            'success' => true,
            'users' => $paginated->items(),
            'total' => $paginated->total(),
            'totalPages' => $paginated->lastPage()
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /** GET /api/admin/ai-usage */
    public function aiUsage(Request $request)
    {
        $perPage = 15;
        $query = \App\Models\JobMatch::query()
            ->join('users', 'job_matches.user_id', '=', 'users.id')
            ->join('jobs', 'job_matches.job_id', '=', 'jobs.id')
            ->select(
                'job_matches.*',
                'users.full_name as user_name',
                'users.email as user_email',
                'jobs.role as job_role',
                'jobs.title as job_title'
            );

        if ($s = $request->search) {
            $query->where(function ($q) use ($s) {
                $q->where('users.full_name', 'like', "%{$s}%")
                  ->orWhere('users.email', 'like', "%{$s}%")
                  ->orWhere('jobs.role', 'like', "%{$s}%")
                  ->orWhere('jobs.title', 'like', "%{$s}%");
            });
        }

        $paginated = $query->latest('job_matches.created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'logs' => $paginated->items(),
            'total' => $paginated->total(),
            'totalPages' => $paginated->lastPage()
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /** GET /api/admin/transactions */
    public function transactions(Request $request)
    {
        $perPage = 15;
        $query = \App\Models\Transaction::query()
            ->join('users', 'transactions.user_id', '=', 'users.id')
            ->select(
                'transactions.*',
                'users.full_name as user_name',
                'users.email as user_email',
                'users.phone as user_phone'
            );

        if ($s = $request->search) {
            $query->where(function ($q) use ($s) {
                $q->where('users.full_name', 'like', "%{$s}%")
                  ->orWhere('users.email', 'like', "%{$s}%")
                  ->orWhere('transactions.razorpay_order_id', 'like', "%{$s}%")
                  ->orWhere('transactions.status', 'like', "%{$s}%");
            });
        }

        if ($status = $request->status) {
            $query->where('transactions.status', $status);
        }

        $paginated = $query->latest('transactions.created_at')->paginate($perPage);

        return response()->json([
            'success' => true,
            'transactions' => $paginated->items(),
            'total' => $paginated->total(),
            'totalPages' => $paginated->lastPage()
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /** GET /api/admin/logs */
    public function getLogs(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            return response()->json([
                'success' => true,
                'logs' => [],
                'message' => 'Log file does not exist.'
            ]);
        }

        $logs = [];
        $file = fopen($logPath, 'r');
        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to open log file.'
            ], 500);
        }
        
        $rawLines = [];
        $maxLines = 1500;
        while (($line = fgets($file)) !== false) {
            $rawLines[] = $line;
            if (count($rawLines) > $maxLines) {
                array_shift($rawLines);
            }
        }
        fclose($file);

        $currentEntry = null;
        foreach ($rawLines as $line) {
            if (preg_match('/^\[(?<date>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (?<env>\w+)\.(?<level>\w+): (?<message>.*)/', $line, $matches)) {
                if ($currentEntry) {
                    $logs[] = $currentEntry;
                }
                
                $currentEntry = [
                    'date' => $matches['date'],
                    'env' => $matches['env'],
                    'level' => strtoupper($matches['level']),
                    'message' => trim($matches['message']),
                    'stack_trace' => []
                ];
            } else {
                if ($currentEntry && count($currentEntry['stack_trace']) < 60) {
                    $currentEntry['stack_trace'][] = trim($line);
                }
            }
        }
        
        if ($currentEntry) {
            $logs[] = $currentEntry;
        }

        $logs = array_reverse($logs);
        $logs = array_slice($logs, 0, 150);

        return response()->json([
            'success' => true,
            'logs' => $logs
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /** DELETE /api/admin/logs/clear */
    public function clearLogs(Request $request)
    {
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            file_put_contents($logPath, '');
        }

        return response()->json([
            'success' => true,
            'message' => 'Logs cleared successfully.'
        ], 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
