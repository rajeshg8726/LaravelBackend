<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\InterviewController;


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
Route::get('jobs-search', [ClientController::class, 'search']);

Route::post('job',[AdminController::class,'insertJobs']);
Route::get('job/{id}',[AdminController::class,'jobsbyId']);
Route::post('login',[AdminController::class,'login']);
Route::delete('deletejob/{id}',[AdminController::class,'deleteJob']);
Route::delete('deleteFeedback/{id}',[AdminController::class,'deleteUserFeedback']);
Route::post('updateJob/{id}', [AdminController::class, 'updateJob']);
Route::post('insertCategory', [AdminController::class, 'addCategories']);
Route::get('getContacts', [AdminController::class, 'getContactUs']);
Route::get('getCategory', [AdminController::class, 'getCategory']);


// Getting Jobs Routes
Route::get('getFullTimeJobs', [AdminController::class, 'getFullTimeJobs']);
Route::get('getInternJobs', [AdminController::class, 'getInternJobs']);
Route::get('get2023BatchJobs', [AdminController::class, 'get2023BatchJobs']);
Route::get('get2024BatchJobs', [AdminController::class, 'get2024BatchJobs']);
Route::get('get2025BatchJobs', [AdminController::class, 'get2025BatchJobs']);
Route::get('get2026BatchJobs', [AdminController::class, 'get2026BatchJobs']);
Route::get('getBengaluruJobs', [AdminController::class, 'getBengaluruJobs']);
Route::get('getGurgaonJobs', [AdminController::class, 'getGurgaonJobs']);
Route::get('getHyderabadJobs', [AdminController::class, 'getHyderabadJobs']);
Route::get('getRemoteJobs', [AdminController::class, 'getRemoteJobs']);
Route::get('getPuneJobs', [AdminController::class, 'getPuneJobs']);
Route::get('getNoidaJobs', [AdminController::class, 'getNoidaJobs']);
Route::get('getChennaiJobs', [AdminController::class, 'getChennaiJobs']);
Route::get('getSWEJobs', [AdminController::class, 'getSWEJobs']);
Route::get('getSDEJobs', [AdminController::class, 'getSDEJobs']);
Route::get('getCloudJobs', [AdminController::class, 'getCloudJobs']);
Route::get('getDevOpsJobs', [AdminController::class, 'getDevOpsJobs']);
Route::get('getAnalyticsJobs', [AdminController::class, 'getAnalyticsJobs']);
Route::get('getSWETestingJobs', [AdminController::class, 'getSWETestingJobs']);
Route::get('getTechnicalSupportJobs', [AdminController::class, 'getTechnicalSupportJobs']);
Route::post('userAddedInvExp', [ClientController::class, 'userAddedInvExp']);
Route::get('getUsersInvExps', [InterviewController::class, 'getUsersInvExps']);
Route::get('getAdminAddedInvExps', [InterviewController::class, 'getAdminAddedInvExps']);
Route::get('interviewById/{id}',[InterviewController::class,'interviewById']);
Route::get('getAdminAddedInvById/{id}',[InterviewController::class,'getAdminAddedInvById']);
Route::post('updateAndSaveInvExps', [InterviewController::class, 'updateAndSaveInvExps']);
Route::delete('deleteUserAddedInvExp/{id}',[InterviewController::class,'deleteUserAddedInvExp']);
Route::delete('deleteAdminAddedInvExp/{id}',[InterviewController::class,'deleteAdminAddedInvExp']);


// Categories Routes
Route::post('insertRoleCat', [AdminController::class, 'insertRoleCat']);
Route::get('getRolesCat', [AdminController::class, 'getRolesCat']);
Route::post('insertWorkCat', [AdminController::class, 'insertWorkCat']);
Route::get('getWorkCat', [AdminController::class, 'getWorkCat']);
Route::post('insertCompanyCat', [AdminController::class, 'insertCompanyCat']);
Route::get('getCompanyCat', [AdminController::class, 'getCompanyCat']);

