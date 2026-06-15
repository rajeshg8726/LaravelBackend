<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jobs;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminJobController extends Controller
{
    /** GET /api/admin/stats */
    public function stats()
    {
        $weekStart = now()->startOfWeek();

        // Calculate Revenue and transaction counts
        $totalRevenue = \App\Models\Transaction::where('status', 'SUCCESS')->sum('amount');
        $proRevenue = \App\Models\Transaction::where('status', 'SUCCESS')->where('plan_type', 'PRO')->sum('amount');
        $topupRevenue = \App\Models\Transaction::where('status', 'SUCCESS')->where('plan_type', 'TOPUP')->sum('amount');
        
        $totalProPurchases = \App\Models\Transaction::where('status', 'SUCCESS')->where('plan_type', 'PRO')->count();
        $totalTopupPurchases = \App\Models\Transaction::where('status', 'SUCCESS')->where('plan_type', 'TOPUP')->count();

        $successfulTxCount = \App\Models\Transaction::where('status', 'SUCCESS')->count();
        $failedTxCount = \App\Models\Transaction::where('status', 'FAILED')->count();
        $pendingTxCount = \App\Models\Transaction::where('status', 'PENDING')->count();
        
        $totalTxCount = $successfulTxCount + $failedTxCount + $pendingTxCount;
        $txConversionRate = $totalTxCount > 0 ? (int) round(($successfulTxCount / $totalTxCount) * 100) : 100;

        $resumeTotalChecks = \App\Models\ResumeHealthCheck::count();
        $resumeUniqueUsers = \App\Models\ResumeHealthCheck::distinct('user_id')->count('user_id');
        $resumeAvgScore = (int) round(\App\Models\ResumeHealthCheck::avg('overall_score') ?? 0);
        $resumeScoreGood = \App\Models\ResumeHealthCheck::where('overall_score', '>=', 42)->count();
        $resumeScoreWarning = \App\Models\ResumeHealthCheck::where('overall_score', '>=', 18)->where('overall_score', '<', 42)->count();
        $resumeScorePoor = \App\Models\ResumeHealthCheck::where('overall_score', '<', 18)->count();

        return response()->json([
            'success' => true,
            'stats'   => [
                'totalJobs'       => Jobs::withoutGlobalScope('published')->count(),
                'totalCandidates' => User::where('is_employer', false)->count(),
                'totalEmployers'  => User::where('is_employer', true)->count(),
                'jobsThisWeek'    => Jobs::withoutGlobalScope('published')->where('created_at', '>=', $weekStart)->count(),
                
                // Add PRO & Top-Up Insights
                'totalRevenue'    => $totalRevenue,
                'proRevenue'      => $proRevenue,
                'topupRevenue'    => $topupRevenue,
                'totalPro'        => User::where('is_pro', true)->count(),
                'totalProPurchases' => $totalProPurchases,
                'totalTopupPurchases' => $totalTopupPurchases,
                'txConversionRate' => $txConversionRate,
                'successfulTx'    => $successfulTxCount,
                'failedTx'        => $failedTxCount,

                // Add Resume Health Checker Stats
                'resumeTotalChecks'    => $resumeTotalChecks,
                'resumeUniqueUsers'    => $resumeUniqueUsers,
                'resumeAvgScore'       => $resumeAvgScore,
                'resumeScoreGood'      => $resumeScoreGood,
                'resumeScoreWarning'   => $resumeScoreWarning,
                'resumeScorePoor'      => $resumeScorePoor,
            ],
            'recentJobs' => Jobs::withoutGlobalScope('published')->latest()
                ->take(10)
                ->get(['id', 'role', 'title', 'location', 'image', 'created_at']),
            'recentTransactions' => \App\Models\Transaction::where('status', 'SUCCESS')
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($tx) {
                    $user = $tx->user;
                    return [
                        'id' => $tx->id,
                        'user_name' => $user->full_name ?? 'Candidate',
                        'user_email' => $user->email ?? '',
                        'plan_type' => $tx->plan_type,
                        'amount' => $tx->amount,
                        'razorpay_payment_id' => $tx->razorpay_payment_id,
                        'created_at' => $tx->created_at ? $tx->created_at->toIso8601String() : null
                    ];
                })
        ]);
    }


    /** GET /api/admin/jobs */
    public function index(Request $request)
    {
        $perPage = 15;
        $query   = Jobs::withoutGlobalScope('published');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($s = $request->search) {
            $query->where(function ($q) use ($s) {
                $q->where('role', 'like', "%{$s}%")
                  ->orWhere('title', 'like', "%{$s}%");
            });
        }

        if ($request->filter === 'featured') {
            $query->where('is_featured', true);
        } elseif ($request->filter === 'urgent') {
            $query->where('is_urgent', true);
        } elseif ($request->filter === 'old') {
            $query->where('created_at', '<', now()->subDays(30));
        } elseif ($request->filter === 'old_60') {
            $query->where('created_at', '<', now()->subDays(60));
        }

        $paginated = $query->latest()->paginate($perPage);

        return response()->json([
            'success'    => true,
            'jobs'       => $paginated->items(),
            'total'      => $paginated->total(),
            'totalPages' => $paginated->lastPage(),
        ]);
    }

    /** GET /api/admin/jobs/{id} */
    public function show($id)
    {
        $job = Jobs::withoutGlobalScope('published')->findOrFail($id);
        return response()->json(['success' => true, 'job' => $job]);
    }

    /** PUT /api/admin/jobs/{id} */
    public function update(Request $request, $id)
    {
        $job = Jobs::withoutGlobalScope('published')->findOrFail($id);

        $validated = $request->validate([
            'title'                   => 'sometimes|string',
            'role'                    => 'sometimes|string',
            'pay'                     => 'sometimes|string',
            'location'                => 'sometimes|string',
            'description'             => 'nullable|string',
            'eligibility'             => 'nullable|string',
            'rolesAndResponsibilities'=> 'nullable|string',
            'niceToHave'              => 'nullable|string',
            'requirements'            => 'nullable|string',
            'jobtype'                 => 'nullable|string',
            'jobbyrole'               => 'nullable|integer',
            'jobbycity'               => 'nullable|integer',
            'batch1'                  => 'nullable|integer',
            'batch2'                  => 'nullable|integer',
            'batch3'                  => 'nullable|integer',
            'batches'                 => 'nullable|string',
            'jobpayrange'             => 'nullable|integer',
            'jobexplevel'             => 'nullable|integer',
            'joblink'                 => 'nullable|string',
            'is_featured'             => 'nullable|boolean',
            'is_urgent'               => 'nullable|boolean',
        ]);

        $job->update($validated);

        return response()->json(['success' => true, 'job' => $job->fresh()]);
    }

    /** PUT /api/admin/jobs/{id}/toggle-featured */
    public function toggleFeatured($id)
    {
        $job = Jobs::withoutGlobalScope('published')->findOrFail($id);
        $job->update(['is_featured' => !$job->is_featured]);
        return response()->json(['success' => true, 'job' => $job->fresh()]);
    }

    /** PUT /api/admin/jobs/{id}/toggle-urgent */
    public function toggleUrgent($id)
    {
        $job = Jobs::withoutGlobalScope('published')->findOrFail($id);
        $job->update(['is_urgent' => !$job->is_urgent]);
        return response()->json(['success' => true, 'job' => $job->fresh()]);
    }

    /** DELETE /api/admin/jobs/{id} */
    public function destroy($id)
    {
        $job = Jobs::withoutGlobalScope('published')->findOrFail($id);

        // Delete company logo from storage
        if ($job->image && Storage::disk('public')->exists($job->image)) {
            Storage::disk('public')->delete($job->image);
        }

        $job->delete();
        return response()->json(['success' => true, 'message' => 'Job deleted.']);
    }

    /** POST /api/admin/jobs/{id}/logo */
    public function uploadLogo(Request $request, $id)
    {
        $request->validate([
            'companyLogo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $job = Jobs::withoutGlobalScope('published')->findOrFail($id);

        // Delete old logo
        if ($job->image && Storage::disk('public')->exists($job->image)) {
            Storage::disk('public')->delete($job->image);
        }

        $path = $request->file('companyLogo')
            ->store('company_logos', 'public');

        $job->update(['image' => 'storage/' . $path]);

        return response()->json(['success' => true, 'image' => 'storage/' . $path]);
    }

    /** PUT /api/admin/jobs/{id}/publish */
    public function publish($id)
    {
        $job = Jobs::withoutGlobalScope('published')->findOrFail($id);
        $job->update(['status' => 'published']);
        return response()->json(['success' => true, 'message' => 'Job published successfully.', 'job' => $job->fresh()]);
    }
}
