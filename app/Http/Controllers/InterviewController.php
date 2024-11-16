<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Userinterviews;
use App\Models\AdminAddedInvExp;

class InterviewController extends Controller
{
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

    public function getUsersInvExps(){
        $InvData = Userinterviews::orderBy('created_at', 'desc')->get();

        if(!$InvData){
            return response()->json([
                'status' => 'No Any Interviews Available'
            ], 404);
        }

        return response()->json([
            'status' => 'Interviews Available',
            'InvData' => $InvData
        ], 200);

    }
    public function getAdminAddedInvExps(){
        $InvData = AdminAddedInvExp::orderBy('created_at', 'desc')->get();

        if(!$InvData){
            return response()->json([
                'status' => 'No Any Interviews Available'
            ], 404);
        }

        return response()->json([
            'status' => 'Interviews Available',
            'InvData' => $InvData
        ], 200);

    }

    public function getAdminAddedInvById($id){
        $InvData = AdminAddedInvExp :: find($id);

        if(!$InvData){
            return response()->json([
                'status' => 'No Any Interviews Available'
            ], 404);
        }

        return response()->json([
            'status' => 'Interviews Available',
            'InvData' => $InvData
        ], 200);

    }

    public function interviewById($id)
    {
        $specificInv = Userinterviews::find($id);
    
        if (!$specificInv) {
            return response()->json([
                'message' => 'Job not found',
            ], 404);
        }
    
        return response()->json([
            'InvData' => $specificInv,
        ], 200);
    }

    public function updateAndSaveInvExps( Request $request ){
        $data = $request->validate([
            'email' => 'nullable|email',
            'name' => 'nullable|string|max:255',
            'companyName' => 'nullable|string|max:255',
            'rounds' => 'nullable|integer',
            'companyOption' => 'nullable|integer',
            'roleOption' => 'nullable|integer',
            'workOption' => 'nullable|integer',
            'experience' => 'nullable|string',
            'title'  => 'nullable|string',
            'jobRole' => 'nullable|string|max:255',
            'details' => 'nullable|string',
            'anonymous' => 'boolean',
        ]);

        $interview = AdminAddedInvExp::create($data);

        return response()->json(['message' => 'Interview data stored successfully!', 'data' => $interview]);
  
    }

    public function deleteUserAddedInvExp($id) {
        // Find the specific feedback by ID
        $specificFeedbackToDelete = Userinterviews::find($id);
    
        // Check if the feedback exists
        if (!$specificFeedbackToDelete) {
            return response()->json([
                'message' => 'Interviews not found',
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

    public function deleteAdminAddedInvExp($id) {
        // Find the specific feedback by ID
        $specificFeedbackToDelete = AdminAddedInvExp::find($id);
    
        // Check if the feedback exists
        if (!$specificFeedbackToDelete) {
            return response()->json([
                'message' => 'Interviews not found',
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





}
