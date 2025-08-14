<?php

namespace App\Http\Controllers;

use App\Models\Jobs;
use App\Models\Admin;
use App\Models\Roles;
use App\Models\Category;
use App\Models\Companies;
use App\Models\Contactus;
use App\Models\DomainCat;
use App\Models\Worktypes;
use App\Models\BatchesCat;
use App\Models\ExpLevelCat;
use App\Models\LocationCat;
use Illuminate\Http\Request;
use App\Models\JobsCategories;
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
            'title' => 'required|string',
            'role' => 'required|string',
            'pay' => 'required|string',
            'location' => 'required|string',
            'description' => 'nullable|string',
            'eligibility' => 'nullable|string',
            'rolesAndResponsibilities' => 'nullable|string',
            'niceToHave' => 'nullable|string',
            'requirements' => 'nullable|string',
            'jobtype' => 'nullable|string', // jobtype is for company type like service based or product based
        'jobbyrole' => 'nullable|integer|max:255', // for jobRole
        'jobbycity' => 'nullable|integer|max:255', /// for jobLocation
        'batch1' => 'nullable|integer|max:255', // for any batch with past year like 2021,2022,2023
        'batch2' => 'nullable|integer|max:255', // for any batch with current plus future batches 2025, 2026
        'batch3' => 'nullable|integer|max:255', // for job domain like SWE, SDE, Cloud, DevOps, Analytics, Testing, Technical Support
        'jobpayrange' => 'nullable|integer|max:255', // Optional field for job pay range
        'jobexplevel' => 'nullable|integer|max:255', // for job experience level like intern, fresher, 1-2 years, 3-5 years, etc.
            'joblink' => 'required|string',
            'batches' => 'required|string',
            'companyLogo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Validate the image
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
            'eligibility' => $validatedData['eligibility'],
            'rolesAndResponsibilities' => $validatedData['rolesAndResponsibilities'],
            'requirements' => $validatedData['requirements'],
            'niceToHave' => $validatedData['niceToHave'],
            'jobtype' => $validatedData['jobtype'],
            'jobbyrole' => $validatedData['jobbyrole'],
            'jobbycity' => $validatedData['jobbycity'],
            'batch1' => $validatedData['batch1'],
            'batch2' => $validatedData['batch2'],
            'batch3' => $validatedData['batch3'],
            'jobpayrange' => $validatedData['jobpayrange'], // Optional field
            'jobexplevel' => $validatedData['jobexplevel'],
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


    // Only delete the image if it exists and is not a default/placeholder image
    if (!empty($specificJobToDelete->image) && $specificJobToDelete->image !== 'uploads/default.png') {
        $imagePath = public_path($specificJobToDelete->image);
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }

    // Delete the job from the database
    $specificJobToDelete->delete();

    
    // Return a success response
    return response()->json([
        'status' => 200,
        'message' => 'Job deleted successfully',
    ], 200);
}

public function updateJob(Request $request, $id)
{
    // Find the job by ID or fail
    $job = Jobs::findOrFail($id);
    // check if job batch exists


    // Validate the incoming request data
    $validatedData = $request->validate([
        'title' => 'nullable|string',
        'role' => 'nullable|string',
        'pay' => 'nullable|string',
        'location' => 'nullable|string',
        'description' => 'nullable|string',
        'eligibility' => 'nullable|string',
        'rolesAndResponsibilities' => 'nullable|string',
        'niceToHave' => 'nullable|string',
        'requirements' => 'nullable|string',
        'jobtype' => 'nullable|string', // jobtype is for company type like service based or product based
        'jobbyrole' => 'nullable|integer|max:255', // for jobRole
        'jobbycity' => 'nullable|integer|max:255', /// for jobLocation
        'batch1' => 'nullable|integer|max:255', // for any batch with any year like 2023, 2024, 2025, 2026
        'batch2' => 'nullable|integer|max:255', // for any batch with any year like 2023, 2024, 2025, 2026
        'batch3' => 'nullable|integer|max:255', // for job domain like SWE, SDE, Cloud, DevOps, Analytics, Testing, Technical Support
        'jobpayrange' => 'nullable|integer|max:255', // Optional field for job pay range
        'jobexplevel' => 'nullable|integer|max:255', // for job experience level like intern, fresher, 1-2 years, 3-5 years, etc.
        'joblink' => 'nullable|string|max:1000',
        'batches' => 'nullable|string',
        'companyLogo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Make image optional
    ]);

    // Handle the image upload if present
    if ($request->hasFile('companyLogo')) {
        // Get the original file name
        $filename = time() . '_' . $request->file('companyLogo')->getClientOriginalName();

        // Move the file to the public/uploads directory
        $imagePath = $request->file('companyLogo')->move(public_path('uploads'), $filename);

        // Store the path relative to the public directory in the database
        $validatedData['companyLogo'] = 'uploads/' . $filename;
    } else {
        // Keep the old image if no new image is uploaded
        $validatedData['companyLogo'] = $job->image;
    }

    // Update the job record in the database with fallback values
    $job->update([
        'title' => $validatedData['title'] ?? $job->title,
        'role' => $validatedData['role'] ?? $job->role,
        'pay' => $validatedData['pay'] ?? $job->pay,
        'location' => $validatedData['location'] ?? $job->location,
        'description' => $validatedData['description'] ?? $job->description,
        'eligibility' => $validatedData['eligibility'] ?? $job->eligibility,
        'rolesAndResponsibilities' => $validatedData['rolesAndResponsibilities'] ?? $job->rolesAndResponsibilities,
        'requirements' => $validatedData['requirements'] ?? $job->requirements,
        'niceToHave' => $validatedData['niceToHave'] ?? $job->niceToHave,
        'jobtype' => $validatedData['jobtype'] ?? $job->jobtype,
        'jobbyrole' => $validatedData['jobbyrole'] ?? $job->jobbyrole,
        'jobbycity' => $validatedData['jobbycity'] ?? $job->jobbycity,
        'batch1' => $validatedData['batch1'] ?? $job->batch1,
        'batch2' => $validatedData['batch2'] ?? $job->batch2,
        'batch3' => $validatedData['batch3'] ?? $job->batch3,
        'jobpayrange' => $validatedData['jobpayrange'] ?? $job->jobpayrange, // Optional field
        'jobexplevel' => $validatedData['jobexplevel'] ?? $job->jobexplevel,
        'joblink' => $validatedData['joblink'] ?? $job->joblink,
        'batches' => $validatedData['batches'] ?? $job->batches,
        'image' => $validatedData['companyLogo'], // Updated image path or existing image
    ]);

    

    // Return a success response
    return response()->json([
        'message' => 'Job updated successfully',
        'job' => $job,
    ], 200);
}




    public function getContactUs(){

        $contactData = Contactus::all();

        if(!$contactData){
            return response()->json([
                'status' => 'No Any Feedback Available'
            ], 404);
        }

        return response()->json([
            'status' => 'Feedback Available',
            'feedbackData' => $contactData
        ], 200);
    }

    

    public function deleteUserFeedback($id) {
        // Find the specific feedback by ID
        $specificFeedbackToDelete = Contactus::find($id);
    
        // Check if the feedback exists
        if (!$specificFeedbackToDelete) {
            return response()->json([
                'message' => 'Feedback not found',
            ], 404);
        }
    
        // Delete the feedback
        $specificFeedbackToDelete->delete();
    
        // Return a success response
        return response()->json([
            'status' => 200,
            'message' => 'Feedback deleted successfully',
        ], 200);
    }











    /// Getting Different jobs functions started from here.
   
 

    // Getting Jobs by Experience Level
    public function getInternJobs() {
        $category = ExpLevelCat::where('name', 'Internships')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Internships' category
        $jobbyid = Jobs::where('jobexplevel', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }



    public function getFreshersJobs() {
        // Find the 'Freshers' category by name
        $category = ExpLevelCat::where('name', 'Freshers')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Freshers' category
        $jobbyid = Jobs::where('jobexplevel', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }


    public function get01YearsJobs() {
        // Find the '0-1 Years' category by name
        $category = ExpLevelCat::where('name', '0-1 Years')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the '0-1 Years' category
        $jobbyid = Jobs::where('jobexplevel', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }



    public function get13YearsJobs() {
        // Find the '1-3 Years' category by name
        $category = ExpLevelCat::where('name', '1-3 Years')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the '1-3 Years' category
        $jobbyid = Jobs::where('jobexplevel', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }


    public function get35YearsJobs() {
        // Find the '3-5 Years' category by name
        $category = ExpLevelCat::where('name', '3-5 Years')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the '3-5 Years' category
        $jobbyid = Jobs::where('jobexplevel', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }


    public function getSeniorRolesJobs() {
        // Find the 'Senior Roles' category by name
        $category = ExpLevelCat::where('name', 'Senior Roles (5+ Years)')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Senior Roles' category
        $jobbyid = Jobs::where('jobexplevel', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }


    public function getMagerialOrLeadershipJobs() {
        // Find the 'Managerial or Leadership' category by name
        $category = ExpLevelCat::where('name', 'Managerial/Leadership Roles')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Managerial or Leadership' category
        $jobbyid = Jobs::where('jobexplevel', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }





    public function getAllBatches() {
        // Retrieve all batches from the BatchesCat model
        $batches = BatchesCat::all();
    
        // Check if there are any batches found
        if ($batches->isEmpty()) {
            return response()->json([
                'status' => 'No Batches Available'
            ], 404);
        }
    
        // Return the batches with success response
        return response()->json([
            'status' => 'Batches Available',
            'batches' => $batches
        ], 200);
    }


   public function getAllRoles(){

        // Retrieve all roles from the Roles model
        $roles = Roles::all();
    
        // Check if there are any roles found
        if ($roles->isEmpty()) {
            return response()->json([
                'status' => 'No Roles Available'
            ], 404);
        }
    
        // Return the roles with success response
        return response()->json([
            'status' => 'Roles Available',
            'roles' => $roles
        ], 200);
    }
   


    public function getAllDomains() {
        // Retrieve all domains from the Companies model
        $domains = DomainCat::all();
    
        // Check if there are any domains found
        if ($domains->isEmpty()) {
            return response()->json([
                'status' => 'No Domains Available'
            ], 404);
        }
    
        // Return the domains with success response
        return response()->json([
            'status' => 'Domains Available',
            'domains' => $domains
        ], 200);
    }


    public function getAllCompaniTypes() {
        // Retrieve all company types from the Companies model
        $companyTypes = Companies::all();
    
        // Check if there are any company types found
        if ($companyTypes->isEmpty()) {
            return response()->json([
                'status' => 'No Company Types Available'
            ], 404);
        }
    
        // Return the company types with success response
        return response()->json([
            'status' => 'Company Types Available',
            'companyTypes' => $companyTypes
        ], 200);
    }


    public function getAllLocations() {
        // Retrieve all locations from the Category model
        $locations = LocationCat::all();
    
        // Check if there are any locations found
        if ($locations->isEmpty()) {
            return response()->json([
                'status' => 'No Locations Available'
            ], 404);
        }
    
        // Return the locations with success response
        return response()->json([
            'status' => 'Locations Available',
            'locations' => $locations
        ], 200);
    }


    public function getAllPayRanges() {
        // Retrieve all pay ranges from the Category model
        $payRanges = PayCat::all();
    
        // Check if there are any pay ranges found
        if ($payRanges->isEmpty()) {
            return response()->json([
                'status' => 'No Pay Ranges Available'
            ], 404);
        }
    
        // Return the pay ranges with success response
        return response()->json([
            'status' => 'Pay Ranges Available',
            'payRanges' => $payRanges
        ], 200);
    }


    // jobs by different domains started from here
    public function getAIMLNLPJobs() {
        // Find the 'AI/ML/NLP' category by name
        $category = DomainCat::where('name', 'AI/ML/NLP')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }

    
        // Retrieve jobs associated with the 'AI/ML/NLP' category batch3
        // Assuming 'batch3' is used for job domain in your Jobs model
        $jobbyid = Jobs::where('batch3', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available',
                'category' => $category
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid,

        ], 200);
    }



    public function getWebDevelopmentJobs() {
        // Find the 'Web Development' category by name
        $category = DomainCat::where('name', 'Web Development')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Web Development' category batch3
        // Assuming 'batch3' is used for job domain in your Jobs model
        $jobbyid = Jobs::where('batch3', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available',
                'category' => $category
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid,
            'category' => $category
        ], 200);
    }

    public function getAppDevelopmentJobs() {
        // Find the 'Mobile Development' category by name
        $category = DomainCat::where('name', 'App Development')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Mobile Development' category batch3
        // Assuming 'batch3' is used for job domain in your Jobs model
        $jobbyid = Jobs::where('batch3', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available',
                'category' => $category
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid,
            'category' => $category
        ], 200);
    }


    public function getBigDataJobs() {
        // Find the 'Big Data' category by name
        $category = DomainCat::where('name', 'Big Data')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Big Data' category batch3
        // Assuming 'batch3' is used for job domain in your Jobs model
        $jobbyid = Jobs::where('batch3', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available',
                'category' => $category
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid,
            'category' => $category
        ], 200);
    }


    public function getBlockchainJobs() {
        // Find the 'Blockchain' category by name
        $category = DomainCat::where('name', 'Blockchain')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Blockchain' category batch3
        // Assuming 'batch3' is used for job domain in your Jobs model
        $jobbyid = Jobs::where('batch3', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available',
                'category' => $category
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid,
            'category' => $category
        ], 200);
    }


    public function getCloudComputingJobs() {
        // Find the 'Cloud Computing' category by name
        $category = DomainCat::where('name', 'Cloud Computing')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Cloud Computing' category batch3
        // Assuming 'batch3' is used for job domain in your Jobs model
        $jobbyid = Jobs::where('batch3', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available',
                'category' => $category
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid,
            'category' => $category
        ], 200);
    }


    public function getARVRJobs() {
        // Find the 'AR/VR' category by name
        $category = DomainCat::where('name', 'AR/VR')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'AR/VR' category batch3
        // Assuming 'batch3' is used for job domain in your Jobs model
        $jobbyid = Jobs::where('batch3', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available',
                'category' => $category
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid,
            'category' => $category
        ], 200);
    }


    public function getGameDevelopmentJobs() {
        // Find the 'Game Development' category by name
        $category = DomainCat::where('name', 'Game Development')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Game Development' category batch3
        // Assuming 'batch3' is used for job domain in your Jobs model
        $jobbyid = Jobs::where('batch3', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available',
                'category' => $category
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid,
            'category' => $category
        ], 200);
    }


    public function getOpenSourceHackathonJobs() {
        // Find the 'Open Source/Hackathon' category by name
        $category = DomainCat::where('name', 'Open Source/Hackathons')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Open Source/Hackathon' category batch3
        // Assuming 'batch3' is used for job domain in your Jobs model
        $jobbyid = Jobs::where('batch3', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available',
                'category' => $category
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid,
            'category' => $category
        ], 200);
    }


    public function getBengaluruJobs() {
        // Find the 'Bengaluru' category by name
        $category = LocationCat::where('name', 'Bengaluru')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Full Time' category
        $jobbyid = Jobs::where('jobbycity', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }

    public function getGurgaonJobs() {
        // Find the 'Gurgaon' category by name
        $category = LocationCat::where('name', 'Gurgaon')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Full Time' category
        $jobbyid = Jobs::where('jobbycity', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }
    public function getHyderabadJobs() {
        // Find the 'Hyderabad' category by name
        $category = LocationCat::where('name', 'Hyderabad')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Full Time' category
        $jobbyid = Jobs::where('jobbycity', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }
    public function getRemoteJobs() {
        // Find the 'Remote' category by name
        $category = LocationCat::where('name', 'Remote')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Full Time' category
        $jobbyid = Jobs::where('jobbycity', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }
   
    public function getPuneJobs() {
        // Find the 'Pune Jobs' category by name
        $category = LocationCat::where('name', 'Pune')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Full Time' category
        $jobbyid = Jobs::where('jobbycity', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }

    public function getChennaiJobs() {
        // Find the 'Chennai' category by name
        $category = LocationCat::where('name', 'Chennai')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Full Time' category
        $jobbyid = Jobs::where('jobbycity', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }
    
    public function getNoidaJobs() {
        // Find the 'Noida jobs' category by name
        $category = LocationCat::where('name', 'Noida')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Full Time' category
        $jobbyid = Jobs::where('jobbycity', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }

    public function getOutsideIndiaJobs() {
        // Find the 'Outside India' category by name
        $category = LocationCat::where('name', 'OutSide IN')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Full Time' category
        $jobbyid = Jobs::where('jobbycity', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }


    // Getting Jobs by Industry Types
    public function getProductBasedJobs() {
        // Find the 'Product Based' category by name
        $category = Companies::where('name', 'Product-Based Companies')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Product Based' category
        $jobbyid = Jobs::where('jobtype', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }

    public function getServiceBasedJobs() {
        // Find the 'Service Based' category by name
        $category = Companies::where('name', 'Service-Based Companies')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Service Based' category
        $jobbyid = Jobs::where('jobtype', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }

    public function getStartupsBasedJobs() {
        // Find the 'Startup' category by name
        $category = Companies::where('name', 'Startups')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Startup' category
        $jobbyid = Jobs::where('jobtype', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }

    public function getMNCBasedJobs() {
        // Find the 'MNC' category by name
        $category = Companies::where('name', 'MNCs')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'MNC' category
        $jobbyid = Jobs::where('jobtype', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }


    public function getRemoteBasedJobs() {
        // Find the 'Remote' category by name
        $category = Companies::where('name', 'Remote-first Companies')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Remote' category
        $jobbyid = Jobs::where('jobtype', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }


    
    /// For Jobs by Roles

    public function getSoftwareEngineerOrDeveloperJobs() {
        // Find the 'Full Time' category by name
        $category = Roles::where('name', 'Software Developer/Engineer')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Full Time' category
        $jobbyid = Jobs::where('jobbyrole', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }
    public function getDataScientistJobs() {
        // Find the 'Data Scientist' category by name
        $category = Roles::where('name', 'Data Scientist')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Data Scientist' category
        $jobbyid = Jobs::where('jobbyrole', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }

    public function getDataAnalystJobs() {
        // Find the 'Data Analyst' category by name
        $category = Roles::where('name', 'Data Analyst')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Data Analyst' category
        $jobbyid = Jobs::where('jobbyrole', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);

    }


        public function getFrontendDeveloperJobs() {
        // Find the 'Frontend Developer' category by name
        $category = Roles::where('name', 'Frontend Developer')->first();
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
        // Retrieve jobs associated with the 'Frontend Developer' category
        $jobbyid = Jobs::where('jobbyrole', $category->id)->
        orderBy('created_at', 'desc')->get();
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);


        }
        public function getBackendDeveloperJobs() {
        // Find the 'Backend Developer' category by name
        $category = Roles::where('name', 'Backend Developer')->first();
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
        // Retrieve jobs associated with the 'Frontend Developer' category
        $jobbyid = Jobs::where('jobbyrole', $category->id)->
        orderBy('created_at', 'desc')->get();
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);


        }

       public function getFullStackDeveloperJobs() {
        // Find the 'Full Stack Developer' category by name
        $category = Roles::where('name', 'Full Stack Developer')->first();
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
        // Retrieve jobs associated with the 'Full Stack Developer' category
        $jobbyid = Jobs::where('jobbyrole', $category->id)->
        orderBy('created_at', 'desc')->get();
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }

        // Return the jobs with success response
        return response()->json([  
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    
    }


    public function getDevOpsEngineerJobs() {
        // Find the 'DevOps Engineer' category by name
        $category = Roles::where('name', 'DevOps Engineer')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'DevOps Engineer' category
        $jobbyid = Jobs::where('jobbyrole', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);

    }

        public function getQAAutomationTesterJobs() {
        // Find the 'QA Automation Tester' category by name
        $category = Roles::where('name', 'QA/Automation Tester')->first();
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }

        // Retrieve jobs associated with the 'QA Automation Tester' category
        $jobbyid = Jobs::where('jobbyrole', $category->id)->orderBy('created_at', 'desc')->get();   
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }

    public function getTechicalSupportEngineerJobs() {
        // Find the 'Technical Support Engineer' category by name
        $category = Roles::where('name', 'Technical Support Engineer')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Technical Support Engineer' category
        $jobbyid = Jobs::where('jobbyrole', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);

    
}

    public function getUIUXDesignerJobs() {
        // Find the 'UI/UX Designer' category by name
        $category = Roles::where('name', 'UI/UX Designer')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'UI/UX Designer' category
        $jobbyid = Jobs::where('jobbyrole', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);

    }



    public function getMachineLearningEngineerJobs() {
        // Find the 'Machine Learning Engineer' category by name
        $category = Roles::where('name', 'Machine Learning Engineer')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Machine Learning Engineer' category
        $jobbyid = Jobs::where('jobbyrole', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);

    }

    public function getCloudEngineerJobs() {
        // Find the 'Cloud Engineer' category by name
        $category = Roles::where('name', 'Cloud Engineer')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Cloud Engineer' category
        $jobbyid = Jobs::where('jobbyrole', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }


    public function getCyberSecurityJobs() {
        // Find the 'Cyber Security' category by name
        $category = Roles::where('name', 'Cybersecurity Analyst')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Cyber Security' category
        $jobbyid = Jobs::where('jobbyrole', $category->id)->orderBy('created_at', 'desc')->get();
    
        // Check if there are any jobs found
        if ($jobbyid->isEmpty()) {
            return response()->json([
                'status' => 'No Jobs Available'
            ], 404);
        }
    
        // Return the jobs with success response
        return response()->json([
            'status' => 'Jobs Available',
            'jobs' => $jobbyid
        ], 200);
    }

















}
