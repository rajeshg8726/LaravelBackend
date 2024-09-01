<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClientController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::get('getAllJobs', [ClientController::class, 'index'] );
Route::post('contactus',[ClientController::class,'contactUs']);
Route::post('job',[AdminController::class,'insertJobs']);
Route::get('job/{id}',[AdminController::class,'jobsbyId']);
Route::post('login',[AdminController::class,'login']);
Route::delete('deletejob/{id}',[AdminController::class,'deleteJob']);


