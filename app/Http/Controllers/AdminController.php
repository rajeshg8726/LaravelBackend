<?php

namespace App\Http\Controllers;

use App\Models\Jobs;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function login(Request $request) {

        // Validate the input data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:3',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        // Get details of admin from db
         // Attempt to find the admin by email
         $admin = Admin::where('email', $request->email)->first();

         if($admin->password == $request->password){
            return response()->json([
                'token'=> $admin,

            ],200);
         }

    }
   
    public function insertJobs(Request $request)
    {
        // Validate the incoming request data and automatically return the validated data
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'pay' => 'required|string|max:255',
            'location' => 'required|string|max:255',
            'description' => 'required|string',
            'jobtype' => 'required|string|max:255',
            'joblink' => 'required|string|max:1000',
            'batches' => 'required|string|max:255',
            'companyLogo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate the image
        ]);
    
       // Handle the image upload
if ($request->hasFile('companyLogo')) {
    // Get the original file name
    $filename = time() . '_' . $request->file('companyLogo')->getClientOriginalName();

    // Move the file to the public/uploads directory
    $imagePath = $request->file('companyLogo')->move(public_path('uploads'), $filename);

    // Store the path relative to the public directory in the database
    $validatedData['companyLogo'] = 'uploads/' . $filename;
}

    
        // Create a new job record in the database
        $toSaveJobs = Jobs::create([
            'title' => $validatedData['title'],
            'role' => $validatedData['role'],
            'pay' => $validatedData['pay'],
            'location' => $validatedData['location'],
            'description' => $validatedData['description'],
            'jobtype' => $validatedData['jobtype'],
            'joblink' => $validatedData['joblink'],
            'batches' => $validatedData['batches'],
            'image' => $validatedData['companyLogo'] ?? null, // Store the image path if exists
        ]);
    
        // Return a success response
        return response()->json([
            'message' => 'Job created successfully',
            'job' => $toSaveJobs,
        ], 201);
    }
    
    public function jobsbyId($id)
    {
        $specificJob = Jobs::find($id);
    
        if (!$specificJob) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }
    
        return response()->json([
            'job' => $specificJob,
        ], 200);
    }

    public function deleteJob($id)
{
    // Find the specific job by ID
    $specificJobToDelete = Jobs::find($id);

    // Check if the job exists
    if (!$specificJobToDelete) {
        return response()->json([
            'message' => 'Job not found',
        ], 404);
    }

    // Get the path to the image associated with this job
    $imagePath = public_path($specificJobToDelete->image);

    // Check if the image file exists and delete it
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }

    // Delete the job from the database
    $specificJobToDelete->delete();

    // Return a success response
    return response()->json([
        'status' => 200,
        'message' => 'Job deleted successfully',
    ], 200);
}

    




}
