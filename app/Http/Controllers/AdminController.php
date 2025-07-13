<?php

namespace App\Http\Controllers;

use App\Models\Jobs;
use App\Models\Admin;
use App\Models\Roles;
use App\Models\Category;
use App\Models\Companies;
use App\Models\Contactus;
use App\Models\Worktypes;
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
            'description' => 'nullable|string',
            'eligibility' => 'nullable|string',
            'rolesAndResponsibilities' => 'nullable|string',
            'niceToHave' => 'nullable|string',
            'requirements' => 'nullable|string',
            'jobtype' => 'required|string|max:255',
            'jobbyrole' => 'required|int|max:255',
            'jobbycity' => 'required|int|max:255',
            'batch1' => 'nullable|int|max:255',
            'batch2' => 'nullable|int|max:255',
            'batch3' => 'nullable|int|max:255',
            'joblink' => 'required|string|max:1000',
            'batches' => 'required|string|max:255',
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

    // Validate the incoming request data
    $validatedData = $request->validate([
        'title' => 'nullable|string|max:255',
        'role' => 'nullable|string|max:255',
        'pay' => 'nullable|string|max:255',
        'location' => 'nullable|string|max:255',
        'description' => 'nullable|string',
        'eligibility' => 'nullable|string',
        'rolesAndResponsibilities' => 'nullable|string',
        'niceToHave' => 'nullable|string',
        'requirements' => 'nullable|string',
        'jobtype' => 'nullable|string|max:255',
        'jobbyrole' => 'nullable|integer|max:255',
        'jobbycity' => 'nullable|integer|max:255',
        'batch1' => 'nullable|integer|max:255',
        'batch2' => 'nullable|integer|max:255',
        'batch3' => 'nullable|integer|max:255',
        'joblink' => 'nullable|string|max:1000',
        'batches' => 'nullable|string|max:255',
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


    /// Categories funtions started from here
    
    public function addCategories(Request $request) {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            
        ]);
    
        // Create a new category instance
        $category = new Category;
        $category->name = $validatedData['name'];
       
        $category->save();
    
       
        if ($category->save()) {
            return response()->json([
                'message' => 'Category Saved',
                'Category' => $category,
            ], 200);
        } else {
            return redirect()->back()->with('error', 'Failed to add category.');
        }
    }


    public function getCategory(){
        $CategoryData = Category::all();

        if(!$CategoryData){
            return response()->json([
                'status' => 'No Any Category Available'
            ], 404);
        }

        return response()->json([
            'status' => 'Category Available',
            'CategoryData' => $CategoryData
        ], 200);

    }


    public function insertRoleCat(Request $request){

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $roleCat = Roles::create($data);
        return response()->json([
            'message' => 'Role Category Added Successfully!',
            'data' => $roleCat,
        ]);
    }

    public function getRolesCat(){
        $dataroles = Roles::all();

        if(!$dataroles){
            return response()->json([
                'status' => 'No Any Category Available'
            ], 404);
        }

        return response()->json([
            'status' => 'Category Available',
            'roleData' => $dataroles
        ], 200);
    }

    public function insertWorkCat(Request $request){

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $roleCat = Worktypes::create($data);
        return response()->json([
            'message' => 'Work Types Category Added Successfully!',
            'data' => $roleCat,
        ]);
    }

    public function getWorkCat(){
        $dataroles = Worktypes::all();

        if(!$dataroles){
            return response()->json([
                'status' => 'No Any Category Available'
            ], 404);
        }

        return response()->json([
            'status' => 'Category Available',
            'roleData' => $dataroles
        ], 200);
    }
    public function insertCompanyCat(Request $request){

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $roleCat = Companies::create($data);
        return response()->json([
            'message' => 'Companies Category Added Successfully!',
            'data' => $roleCat,
        ]);
    }

    public function getCompanyCat(){
        $dataroles = Companies::all();

        if(!$dataroles){
            return response()->json([
                'status' => 'No Any Category Available'
            ], 404);
        }

        return response()->json([
            'status' => 'Category Available',
            'roleData' => $dataroles
        ], 200);
    }











    /// Getting Different jobs functions started from here.
    public function getFullTimeJobs() {
        // Find the 'Full Time' category by name
        $category = Category::where('name', 'Full Time')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Full Time' category
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

    public function getInternJobs() {
        // Find the 'Full Time' category by name
        $category = Category::where('name', 'Internship')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Full Time' category
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

    public function get2023BatchJobs() {
        // Find the 'Full Time' category by name
        $category = Category::where('name', '2023 Batch')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Full Time' category
        $jobbyid = Jobs::where('batch1', $category->id)->orderBy('created_at', 'desc')->get();
    
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
   
    public function get2024BatchJobs() {
        // Find the 'Full Time' category by name
        $category = Category::where('name', '2024 Batch')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Full Time' category
        $jobbyid = Jobs::where('batch2', $category->id)->orderBy('created_at', 'desc')->get();
    
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
    
    public function get2025BatchJobs() {
        // Find the 'Full Time' category by name
        $category = Category::where('name', '2025 Batch')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Full Time' category
        $jobbyid = Jobs::where('batch3', $category->id)->orderBy('created_at', 'desc')->get();
    
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
    public function get2026BatchJobs() {
        // Find the 'Full Time' category by name
        $category = Category::where('name', '2026 Batch')->first();
    
        // Check if the category exists
        if (!$category) {
            return response()->json([
                'status' => 'Category not found'
            ], 404);
        }
    
        // Retrieve jobs associated with the 'Full Time' category
        $jobbyid = Jobs::where('batch3', $category->id)->orderBy('created_at', 'desc')->get();
    
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

  

    public function getBengaluruJobs() {
        // Find the 'Full Time' category by name
        $category = Category::where('name', 'Bengaluru')->first();
    
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
        // Find the 'Full Time' category by name
        $category = Category::where('name', 'Gurgaon')->first();
    
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
        // Find the 'Full Time' category by name
        $category = Category::where('name', 'Hyderabad')->first();
    
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
        // Find the 'Full Time' category by name
        $category = Category::where('name', 'Remote')->first();
    
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
        // Find the 'Full Time' category by name
        $category = Category::where('name', 'Pune')->first();
    
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
        // Find the 'Full Time' category by name
        $category = Category::where('name', 'Chennai')->first();
    
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
        // Find the 'Full Time' category by name
        $category = Category::where('name', 'Noida')->first();
    
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
    
    /// For Jobs by Roles

    public function getSWEJobs() {
        // Find the 'Full Time' category by name
        $category = Category::where('name', 'Software-Engineer')->first();
    
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
    public function getSWETestingJobs() {
        // Find the 'Full Time' category by name
        $category = Category::where('name', 'Software-Testing')->first();
    
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
    
    public function getSDEJobs() {
        // Find the 'Full Time' category by name
        $category = Category::where('name', 'Software-Developer')->first();
    
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
    
    public function getCloudJobs() {
        // Find the 'Full Time' category by name
        $category = Category::where('name', 'Cloud-Engineer')->first();
    
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
    
    public function getDevOpsJobs() {
        // Find the 'Full Time' category by name
        $category = Category::where('name', 'DevOps-Engineer')->first();
    
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
    
    public function getAnalyticsJobs() {
        // Find the 'Full Time' category by name
        $category = Category::where('name', 'Analytics-&-Data-Science')->first();
    
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


    public function getTechnicalSupportJobs() {
        // Find the 'Full Time' category by name
        $category = Category::where('name', 'Technical-Support')->first();
    
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
    
    
    





}



