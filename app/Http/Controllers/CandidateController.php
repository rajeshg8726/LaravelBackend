<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CandidateController extends Controller
{
    /** GET /api/candidate/profile */
    public function profile(Request $request)
    {
        $user = $request->user();
        
        // Lazy refresh and check for completion bonus dynamically on dashboard load
        $user->refreshCreditsIfEligible();
        $user->checkAndApplyProfileBonus();

        return response()->json([
            'success' => true,
            'user'    => [
                'id'            => $user->id,
                'full_name'      => $user->full_name,
                'email'         => $user->email,
                'phone'         => $user->phone,
                'location'      => $user->location,
                'bio'           => $user->bio,
                'skills'        => $user->skills ?? [],   // stored as JSON, returned as array
                'work_experience' => $user->work_experience ?? [],
                'education'     => $user->education ?? [],
                'profile_image' => $user->profile_image,
                'is_pro'       => $user->is_pro,
                'pro_expires_at' => $user->pro_expires_at,
                'is_active'     => $user->is_active,
                'resume'        => $user->resume,
                'created_at'    => $user->created_at,
                'ai_credits'    => $user->ai_credits,
                'profile_completeness' => $user->profile_completeness,
                'has_received_profile_bonus' => $user->has_received_profile_bonus,
                'is_first_analysis_free_used' => $user->is_first_analysis_free_used,
            ],
        ]);
    }

    /** PUT /api/candidate/profile */
    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|string|max:255',
            'phone'    => 'sometimes|nullable|string|max:20',
            'location' => 'sometimes|nullable|string|max:255',
            'bio'      => 'sometimes|nullable|string|max:1000',
            'skills'   => 'sometimes|nullable|array',
            'skills.*' => 'string|max:50',
            'work_experience' => 'sometimes|nullable|array',
            'work_experience.*.company' => 'required_with:work_experience|string|max:255',
            'work_experience.*.role' => 'required_with:work_experience|string|max:255',
            'work_experience.*.from_year' => 'required_with:work_experience|string|max:10',
            'work_experience.*.to_year' => 'nullable|string|max:10',
            'work_experience.*.is_current' => 'nullable|boolean',
            'education' => 'sometimes|nullable|array',
            'education.*.degree' => 'required_with:education|string|max:255',
            'education.*.institution' => 'required_with:education|string|max:255',
            'education.*.year' => 'required_with:education|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $user = $request->user();
         // Debugging line to check user data before update
        //  echo "Before Update: " . json_encode($user) . "\n";
        $user->update($validator->validated());

        // Refresh from DB to return latest data
        $user->refresh();
        
        // Also check/apply bonus when profile is updated
        $user->checkAndApplyProfileBonus();

        return response()->json([
            'success' => true,
            'user'    => [
                'id'            => $user->id,
                'full_name'      => $user->full_name,
                'email'         => $user->email,
                'phone'         => $user->phone,
                'location'      => $user->location,
                'bio'           => $user->bio,
                'skills'        => $user->skills ?? [],
                'work_experience' => $user->work_experience ?? [],
                'education'     => $user->education ?? [],
                'profile_image' => $user->profile_image,
                'resume'        => $user->resume,
                'is_pro'        => $user->is_pro,
                'ai_credits'    => $user->ai_credits,
                'profile_completeness' => $user->profile_completeness,
                'has_received_profile_bonus' => $user->has_received_profile_bonus,
                'is_first_analysis_free_used' => $user->is_first_analysis_free_used,
            ],
        ]);
    }

    /** POST /api/candidate/profile/image */
    public function updateProfileImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = $request->user();

        // Delete old image if it exists
        if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
            Storage::disk('public')->delete($user->profile_image);
        }

        $path = $request->file('image')->store('profile_images', 'public');
        $user->update(['profile_image' => 'storage/' . $path]);

        return response()->json([
            'success'       => true,
            'profile_image' => 'storage/' . $path,
        ]);
    }

    /** POST /api/candidate/profile/resume */
    public function updateResume(Request $request)
    {
        $request->validate([
            'resume' => 'required|mimes:pdf,doc,docx|max:5120',
        ]);

        $user = $request->user();

        // Delete old resume
        if ($user->resume && Storage::disk('public')->exists($user->resume)) {
            Storage::disk('public')->delete($user->resume);
        }

        $path = $request->file('resume')->store('resumes', 'public');
        $user->update([
            'resume' => 'storage/' . $path,
            'resume_text' => null
        ]);

        // Parse and cache the resume text immediately
        $user->parseResumeText();

        $warning = null;
        if (!empty($user->resume) && (empty($user->resume_text) || strlen($user->resume_text) < 150)) {
            $warning = "Your resume PDF/document appears to be a scanned image or has very little readable text. For best AI matching results, please upload a text-based PDF or fill out your profile details fully.";
        }

        return response()->json([
            'success' => true,
            'resume'  => 'storage/' . $path,
            'resume_parsed' => !empty($user->resume_text),
            'warning' => $warning,
        ]);
    }
}
