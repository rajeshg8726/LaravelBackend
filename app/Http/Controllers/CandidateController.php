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
                'profile_image' => $user->profile_image,
                'is_pro'       => $user->is_pro,
                'pro_expires_at' => $user->pro_expires_at,
                'is_active'     => $user->is_active,
                'resume'        => $user->resume,
                'created_at'    => $user->created_at,
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
            // Debugging line to check user data after update
            // echo "After Update: " . json_encode($user) . "\n";
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
                'profile_image' => $user->profile_image,
                'resume'        => $user->resume,
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
        $user->update(['resume' => 'storage/' . $path]);

        return response()->json([
            'success' => true,
            'resume'  => 'storage/' . $path,
        ]);
    }
}
