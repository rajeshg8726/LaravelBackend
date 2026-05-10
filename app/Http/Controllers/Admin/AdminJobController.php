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
   /** GET /api/admin/stats */
public function stats()
{
    $weekStart = now()->startOfWeek();

    return response()->json([
        'success' => true,
        'stats'   => [
            'totalJobs'       => Jobs::count(),
            'totalCandidates' => User::where('is_employer', false)->count(), // ✅
            'totalEmployers'  => User::where('is_employer', true)->count(),  // ✅
            'jobsThisWeek'    => Jobs::where('created_at', '>=', $weekStart)->count(),
        ],
        'recentJobs' => Jobs::latest()
            ->take(10)
            ->get(['id', 'role', 'title', 'location', 'image', 'created_at']),
    ]);
}


    /** GET /api/admin/jobs */
    public function index(Request $request)
    {
        $perPage = 15;
        $query   = Jobs::query();

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
        $job = Jobs::findOrFail($id);
        return response()->json(['success' => true, 'job' => $job]);
    }

    /** PUT /api/admin/jobs/{id} */
    public function update(Request $request, $id)
    {
        $job = Jobs::findOrFail($id);

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
        $job = Jobs::findOrFail($id);
        $job->update(['is_featured' => !$job->is_featured]);
        return response()->json(['success' => true, 'job' => $job->fresh()]);
    }

    /** PUT /api/admin/jobs/{id}/toggle-urgent */
    public function toggleUrgent($id)
    {
        $job = Jobs::findOrFail($id);
        $job->update(['is_urgent' => !$job->is_urgent]);
        return response()->json(['success' => true, 'job' => $job->fresh()]);
    }

    /** DELETE /api/admin/jobs/{id} */
    public function destroy($id)
    {
        $job = Jobs::findOrFail($id);

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

        $job = Jobs::findOrFail($id);

        // Delete old logo
        if ($job->image && Storage::disk('public')->exists($job->image)) {
            Storage::disk('public')->delete($job->image);
        }

        $path = $request->file('companyLogo')
            ->store('company_logos', 'public');

        $job->update(['image' => 'storage/' . $path]);

        return response()->json(['success' => true, 'image' => 'storage/' . $path]);
    }
}
