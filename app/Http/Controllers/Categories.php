<?php

namespace App\Http\Controllers;

use App\Models\Jobs;
use App\Models\Admin;
use App\Models\Roles;
use App\Models\PayCat;
use App\Models\Category;
use App\Models\Companies;
use App\Models\Contactus;
use App\Models\DomainCat;
use App\Models\BatchesCat;
use App\Models\ExpLevelCat;
use App\Models\LocationCat;
use Illuminate\Http\Request;
use App\Models\JobsCategories;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class Categories extends Controller
{
    
    /// Categories funtions started from here
    
    public function addCategories(Request $request) {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'name' => 'required|string',
            
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

    public function insertCategories(Request $request) {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'name' => 'required|string',
        ]);
    
        // Create a new category instance
        $category = new JobsCategories;
        $category->name = $validatedData['name'];
       
        $category->save();
    
       
        if ($category->save()) {
            return response()->json([
                'message' => 'Category Saved',
                'Categories' => $category,
            ], 200);
        } else {
            return redirect()->back()->with('error', 'Failed to add category.');
        }
    }

    public function getCategories(){
        $CategoryData = JobsCategories::all();

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
    
    // Need to Delete
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
            'name' => 'required|string',
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

    public function insertDomainCat(Request $request){

        $data = $request->validate([
            'name' => 'required|string',
        ]);

        $roleCat = DomainCat::create($data);
        return response()->json([
            'message' => 'Work Types Category Added Successfully!',
            'data' => $roleCat,
        ]);
    }

    public function geDomainCat(){
        $dataroles = DomainCat::all();

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
            'name' => 'required|string',
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

    public function insertBatchCat(Request $request){

        $data = $request->validate([
            'name' => 'required|string',
        ]);

        $roleCat = BatchesCat::create($data);
        return response()->json([
            'message' => 'Batch Category Added Successfully!',
            'data' => $roleCat,
        ]);
    }

    public function getBatchCat(){
        $dataroles = BatchesCat::all();

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

    public function insertExpLevelCat(Request $request){

        $data = $request->validate([
            'name' => 'required|string',
        ]);

        $roleCat = ExpLevelCat::create($data);
        return response()->json([
            'message' => 'Experience Level Category Added Successfully!',
            'data' => $roleCat,
        ]);
    }

    public function getExpLevelCat(){
        $dataroles = ExpLevelCat::all();

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


    public function insertLocationCat(Request $request){

        $data = $request->validate([
            'name' => 'required|string',
        ]);

        $roleCat = LocationCat::create($data);
        return response()->json([
            'message' => 'Location Category Added Successfully!',
            'data' => $roleCat,
        ]);
    }

    public function getLocationCat(){
        $dataroles = LocationCat::all();

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

    public function insertPayCat(Request $request){

        $data = $request->validate([
            'name' => 'required|string',
        ]);

        $roleCat = PayCat::create($data);
        return response()->json([
            'message' => 'Pay Category Added Successfully!',
            'data' => $roleCat,
        ]);
    }

    public function getPayCat(){
        $dataroles = PayCat::all();

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

}