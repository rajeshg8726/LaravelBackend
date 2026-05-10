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
            $q->where('fullName', 'like', "%{$s}%")
              ->orWhere('email',  'like', "%{$s}%");
        });
    }

    $paginated = $query->latest()->paginate($perPage);

    return response()->json([
        'success'    => true,
        'users'      => $paginated->items(),
        'total'      => $paginated->total(),
        'totalPages' => $paginated->lastPage(),
    ]);
}


    /** PUT /api/admin/users/{id}/toggle-status */
    public function toggleStatus($id)
    {
        $user = User::findOrFail($id);
        $user->update(['is_active' => !$user->is_active]);
        return response()->json(['success' => true, 'user' => $user->fresh()]);
    }
}
