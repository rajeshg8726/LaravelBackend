<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Categories;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SitemapController;
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
Route::get('sitemap.xml', [SitemapController::class, 'generateSitemap']);

Route::post('job',[AdminController::class,'insertJobs']);
Route::get('job/{id}',[AdminController::class,'jobsbyId']);
Route::post('login',[AdminController::class,'login']);
Route::delete('deletejob/{id}',[AdminController::class,'deleteJob']);
Route::delete('deleteFeedback/{id}',[AdminController::class,'deleteUserFeedback']);
Route::post('updateJob/{id}', [AdminController::class, 'updateJob']);
Route::get('getContacts', [AdminController::class, 'getContactUs']);
Route::get('getAllBatches', [AdminController::class, 'getAllBatches']);
Route::get('getAllRoles', [AdminController::class, 'getAllRoles']);
Route::get('getAllDomains', [AdminController::class, 'getAllDomains']);
Route::get('getAllCompaniTypes', [AdminController::class, 'getAllCompaniTypes']);
Route::get('getAllLocations', [AdminController::class, 'getAllLocations']);
Route::get('getAllPayRanges', [AdminController::class, 'getAllPayRanges']);
Route::get('getAllLocations', [AdminController::class, 'getAllLocations']);

// Getting Jobs Routes by Experience Level
Route::get('getInternJobs', [AdminController::class, 'getInternJobs']);
Route::get('getFreshersJobs', [AdminController::class, 'getFreshersJobs']);
Route::get('get01YearsJobs', [AdminController::class, 'get01YearsJobs']);
Route::get('get13YearsJobs', [AdminController::class, 'get13YearsJobs']);
Route::get('get35YearsJobs', [AdminController::class, 'get35YearsJobs']);
Route::get('getSeniorRolesJobs', [AdminController::class, 'getSeniorRolesJobs']);
Route::get('getMagerialOrLeadershipJobs', [AdminController::class, 'getMagerialOrLeadershipJobs']);

// Getting Jobs Routes by Domain Wise
Route::get('getAIMLNLPJobs', [AdminController::class, 'getAIMLNLPJobs']);
Route::get('getBigDataJobs', [AdminController::class, 'getBigDataJobs']);
Route::get('getBlockchainJobs', [AdminController::class, 'getBlockchainJobs']);
Route::get('getCloudComputingJobs', [AdminController::class, 'getCloudComputingJobs']);
Route::get('getGameDevelopmentJobs', [AdminController::class, 'getGameDevelopmentJobs']);
Route::get('getAppDevelopmentJobs', [AdminController::class, 'getAppDevelopmentJobs']);
Route::get('getWebDevelopmentJobs', [AdminController::class, 'getWebDevelopment
Jobs']);
Route::get('getARVRJobs', [AdminController::class, 'getARVRJobs']);
Route::get('getOpenSourceHackathonJobs', [AdminController::class, 'getOpenSourceHackathonJobs']);

// Getting Jobs Routes by Location
Route::get('getBengaluruJobs', [AdminController::class, 'getBengaluruJobs']);
Route::get('getGurgaonJobs', [AdminController::class, 'getGurgaonJobs']);
Route::get('getHyderabadJobs', [AdminController::class, 'getHyderabadJobs']);
Route::get('getRemoteJobs', [AdminController::class, 'getRemoteJobs']);
Route::get('getPuneJobs', [AdminController::class, 'getPuneJobs']);
Route::get('getNoidaJobs', [AdminController::class, 'getNoidaJobs']);
Route::get('getChennaiJobs', [AdminController::class, 'getChennaiJobs']);
Route::get('getOutsideIndiaJobs', [AdminController::class, 'getOutsideIndiaJobs']);

// Getting Jobs Routes by Industry types
Route::get('getProductBasedJobs', [AdminController::class, 'getProductBasedJobs']);
Route::get('getServiceBasedJobs', [AdminController::class, 'getServiceBasedJobs']);
Route::get('getStartupsBasedJobs', [AdminController::class, 'getStartupsBasedJobs']);
Route::get('getMNCBasedJobs', [AdminController::class, 'getMNCBasedJobs']);
Route::get('getRemoteBasedJobs', [AdminController::class, 'getRemoteBasedJobs']);


// Getting Jobs Routes by Jobs Roles
Route::get('getSoftwareEngineerOrDeveloperJobs', [AdminController::class, 'getSoftwareEngineerOrDeveloperJobs']);
Route::get('getDataScientistJobs', [AdminController::class, 'getDataScientistJobs']);
Route::get('getDataAnalystJobs', [AdminController::class, 'getDataAnalystJobs']);
Route::get('getDevOpsEngineerJobs', [AdminController::class, 'getDevOpsEngineerJobs']);
Route::get('getCyberSecurityJobs', [AdminController::class, 'getCyberSecurityJobs']);
Route::get('getUIUXDesignerJobs', [AdminController::class, 'getUIUXDesignerJobs']);
Route::get('getFrontendDeveloperJobs', [AdminController::class, 'getFrontendDeveloperJobs']);
Route::get('getBackendDeveloperJobs', [AdminController::class, 'getBackendDeveloperJobs']);
Route::get('getFullStackDeveloperJobs', [AdminController::class, 'getFullStackDeveloperJobs']);
Route::get('getQAAutomationTesterJobs', [AdminController::class, 'getQAAutomationTesterJobs']);
Route::get('getTechnicalSupportJobs', [AdminController::class, 'getTechicalSupportEngineerJobs']);
Route::get('getCloudEngineerJobs', [AdminController::class, 'getCloudEngineerJobs']);
Route::get('getMachineLearningEngineerJobs', [AdminController::class, 'getMachineLearningEngineerJobs']);



// Interview Experience Routes
Route::post('userAddedInvExp', [ClientController::class, 'userAddedInvExp']);
Route::get('getUsersInvExps', [InterviewController::class, 'getUsersInvExps']);
Route::get('getAdminAddedInvExps', [InterviewController::class, 'getAdminAddedInvExps']);
Route::get('interviewById/{id}',[InterviewController::class,'interviewById']);
Route::get('getAdminAddedInvById/{id}',[InterviewController::class,'getAdminAddedInvById']);
Route::post('updateAndSaveInvExps', [InterviewController::class, 'updateAndSaveInvExps']);
Route::delete('deleteUserAddedInvExp/{id}',[InterviewController::class,'deleteUserAddedInvExp']);
Route::delete('deleteAdminAddedInvExp/{id}',[InterviewController::class,'deleteAdminAddedInvExp']);


// Categories Routes
Route::post('insertCategory', [Categories::class, 'addCategories']);
Route::get('getCategory', [Categories::class, 'getCategory']);


Route::post('insertRoleCat', [Categories::class, 'insertRoleCat']);
Route::get('getRolesCat', [Categories::class, 'getRolesCat']);
Route::post('insertCompanyCat', [Categories::class, 'insertCompanyCat']);
Route::get('getCompanyCat', [Categories::class, 'getCompanyCat']);
Route::post('insertCategories', [Categories::class, 'insertCategories']);
Route::get('getCategories', [Categories::class, 'getCategories']);
Route::post('insertDomainCat', [Categories::class, 'insertDomainCat']);
Route::get('getDomainCat', [Categories::class, 'geDomainCat']);
Route::post('insertExpLevelCat', [Categories::class, 'insertExpLevelCat']);
Route::get('getExpLevelCat', [Categories::class, 'getExpLevelCat']);
Route::post('insertLocationCat', [Categories::class, 'insertLocationCat']); 
Route::get('getLocationCat', [Categories::class, 'getLocationCat']);
Route::post('insertPayCat', [Categories::class, 'insertPayCat']);
Route::get('getPayCat', [Categories::class, 'getPayCat']);
Route::post('insertPayCat', [Categories::class, 'insertPayCat']);
Route::get('getPayCat', [Categories::class, 'getPayCat']);
Route::post('insertBatchCat', [Categories::class, 'insertBatchCat']);
Route::get('getBatchCat', [Categories::class, 'getBatchCat']);

