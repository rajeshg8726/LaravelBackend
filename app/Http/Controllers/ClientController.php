<?php

namespace App\Http\Controllers;

use App\Models\Jobs;
use App\Models\Contactus;
use Illuminate\Http\Request;
use App\Models\Userinterviews;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{

    // Getting all the jobs from db
    public function index(){
        
        // Fetch jobs data in reverse order (latest records first)
    $jobdata = Jobs::orderBy('created_at', 'desc')->get();


        if($jobdata->count() > 0){

            $resdata = [
                'status' => 200,
                'JobsData' => $jobdata,
            ];
            return response()->json($resdata, 200);
    
        }
        else
        {
            return response()->json([
                'status' => 404,
                'message'=> "No Jobs Available"
            ] , 404);
        }

    }

    public function contactUs( Request $req ){

            $validator = Validator::make($req->all(), [
                'name' => 'required|string|max:191',
                'email' => 'required|email|max:191',
                'message' => 'required|string|max:1000',

            ]);

            if($validator->fails())
            {
                return response()->json([
                    'status' => 422,
                    'message'=>$validator->messages(),
                ] , 422);
            }
            else
            {
                // map model name with input fields data 
                $toSave = Contactus::create([
                    'name' => $req->name,
                    'email' => $req->email,
                    'message' => $req->message,
                ]);


                /// if data is save in db
                if($toSave){
                    return response()->json([
                        'status' => 200,
                        'message'=> "Your Message Saved Successfully"
                    ] , 200);
                }
                else{
                    return response()->json([
                        'status' => 500,
                        'message'=> "Msg not saved in db"
                    ] , 500);
                }
            }


    }


    public function userAddedInvExp (Request $request ){
        $data = $request->validate([
            'email' => 'nullable|email',
            'name' => 'nullable|string|max:255',
            'companyName' => 'nullable|string|max:255',
            'rounds' => 'nullable|integer',
            'experience' => 'nullable|string',
            'jobRole' => 'nullable|string|max:255',
            'details' => 'nullable|string',
            'anonymous' => 'boolean',
        ]);

        $interview = Userinterviews::create($data);

        return response()->json(['message' => 'Interview data stored successfully!', 'data' => $interview]);
  
    }

}
