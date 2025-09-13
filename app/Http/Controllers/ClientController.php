<?php

namespace App\Http\Controllers;

use Log;
use App\Models\Jobs;
use App\Models\BlogPost;
use App\Models\Contactus;
use App\Models\UserEmail;
use Illuminate\Http\Request;
use App\Models\Userinterviews;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{

    // Getting all the jobs from db
    public function index()
    {

        // Fetch jobs data in reverse order (latest records first)
        $jobdata = Jobs::orderBy('created_at', 'desc')->get();


        if ($jobdata->count() > 0) {

            $resdata = [
                'status' => 200,
                'JobsData' => $jobdata,
            ];
            return response()->json($resdata, 200);
        } else {
            return response()->json([
                'status' => 404,
                'message' => "No Jobs Available"
            ], 404);
        }
    }

    public function contactUs(Request $req)
    {

        $validator = Validator::make($req->all(), [
            'name' => 'required|string|max:191',
            'email' => 'required|email|max:191',
            'message' => 'required|string|max:1000',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => $validator->messages(),
            ], 422);
        } else {
            // map model name with input fields data 
            $toSave = Contactus::create([
                'name' => $req->name,
                'email' => $req->email,
                'message' => $req->message,
            ]);


            /// if data is save in db
            if ($toSave) {
                return response()->json([
                    'status' => 200,
                    'message' => "Your Message Saved Successfully"
                ], 200);
            } else {
                return response()->json([
                    'status' => 500,
                    'message' => "Msg not saved in db"
                ], 500);
            }
        }
    }

    public function userSubscribeForEmailNotify(Request $request)
    {
        $data = $request->validate([
            'email' => 'nullable|email|max:191|unique:useremail,email',
        ]);

        $subscribed = UserEmail::create($data);

        return response()->json(['message' => 'Subscribed successfully For Email Notification!', 'data' => $subscribed]);
    }

    // Frontend Search functionality, this functions work as the primary for loading the whole jobs on front page
    public function search(Request $request)
    {
        $query = Jobs::query();

        // Filter by role if provided
        if ($request->filled('role')) {
            $query->where('role', 'LIKE', '%' . $request->role . '%');
        }

        // Filter by location if provided
        if ($request->filled('location')) {
            $query->where('location', 'LIKE', '%' . $request->location . '%');
        }

        // Filter by keyword (in title or description)
        if ($request->filled('searchTerm')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->searchTerm . '%')
                    ->orWhere('description', 'LIKE', '%' . $request->searchTerm . '%');
            });
        }

        $jobs = $query->orderBy('created_at', 'desc')->get(); // to get the last inserted job at first

        return response()->json($jobs);
    }

    public function insertBlogPosts(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'content' => 'required|string',
            'anonymous' => 'required|integer|in:0,1', // only allow 0 or 1
            'excerpt' => 'required|string|max:500',
            'category' => 'required|string',
            'tags' => 'required|string',
            'type' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'publish_date' => 'required|date',
            'estimated_read_time' => 'required|integer',
            'status' => 'required|integer|in:0,1,2', // only allow 0 or 1 (0 = draft, 1 = published, 2 = archived)
        ]);

        // handle file upload
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $destination = public_path('uploads/blog/');
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }
            $file->move($destination, $filename);
            $data['image'] = 'uploads/blog/' . $filename;
        } else {
            $data['image'] = null;
        }

        try {
            $blogPost = BlogPost::create($data);
        } catch (\Exception $e) {
            Log::error('BlogPost create failed: ' . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Failed to create blog post',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json(['message' => 'Blog post created successfully!', 'data' => $blogPost], 201);
    }

    public function getAllBlogPosts()
    {
        $blogPosts = BlogPost::orderBy('created_at', 'desc')->get();
        return response()->json(
            [
                'status' => 200,
                'blogs' => $blogPosts,
            ]
        );
    }

    public function getBlogPostsByID($id)
    {
        $blogPost = BlogPost::find($id);
        if ($blogPost) {
            return response()->json([
                'status' => 200,
                'blog' => $blogPost,
            ]);
        } else {
            return response()->json([
                'status' => 404,
                'message' => 'Blog post not found',
            ], 404);
        }
    }
}
