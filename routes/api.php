<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminJobController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiMatchController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\Categories;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SitemapController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentController;



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


// Public Routes
Route::post('/register/candidate', [AuthController::class, 'registerCandidate']);
Route::post('/register/employer', [AuthController::class, 'registerEmployer']);
Route::post('/login', [AuthController::class, 'login']);
// Protected Routes (Requires a valid token)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Future expansion:
    Route::get('/candidate/profile', [CandidateController::class, 'profile']);
    Route::put('/candidate/profile', [CandidateController::class, 'updateProfile']);
    Route::post('/candidate/profile/image', [CandidateController::class, 'updateProfileImage']);
    Route::post('/candidate/profile/resume', [CandidateController::class, 'updateResume']);



    // AI Match Route 
    Route::post('/candidate/generate-match', [AiMatchController::class, 'generateMatch']);
    Route::get('/candidate/my-ai-matches', [AiMatchController::class, 'myMatches']);

    Route::post('/payments/create-order', [PaymentController::class, 'createOrder']);
    Route::post('/payments/verify', [PaymentController::class, 'verifyPayment']);
});


// Forgot / Reset Password (public — no token needed)
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password',  [AuthController::class, 'resetPassword']);

// Webhook Route (Open to public, verified by signature)
Route::post('/webhooks/razorpay', [PaymentController::class, 'webhook']);


Route::get('getAllJobs', [ClientController::class, 'index']);
Route::post('contactus', [ClientController::class, 'contactUs']);
Route::get('jobs-search', [ClientController::class, 'search']);
Route::post('/jobs/filter', [ClientController::class, 'filter']);
Route::get('sitemap.xml', [SitemapController::class, 'generateSitemap']);

Route::post('job', [AdminController::class, 'insertJobs']);
Route::get('job/{id}', [AdminController::class, 'jobsbyId']);
Route::post('adminlogin', [AdminController::class, 'login']);
Route::delete('deletejob/{id}', [AdminController::class, 'deleteJob']);
Route::delete('deleteFeedback/{id}', [AdminController::class, 'deleteUserFeedback']);
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
Route::get('getWebDevelopmentJobs', [AdminController::class, 'getWebDevelopmentJobs']);
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



// Interview Blog Posts Routes
Route::post('insertBlogPosts', [ClientController::class, 'insertBlogPosts']);
Route::post('subscribeNewsletter', [ClientController::class, 'userSubscribeForEmailNotify']);
Route::get('getAllUserSubscriberForEmailNotify', [ClientController::class, 'getAllUserSubscriberForEmailNotify']);
Route::get('getAllBlogPosts', [ClientController::class, 'getAllBlogPosts']);
Route::get('getBlogPostsByID/{id}', [ClientController::class, 'getBlogPostsByID']);
Route::delete('deletePost/{id}', [AdminController::class, 'deletePost']);


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



// ── Admin Routes ───────────────────────────────────────────────
Route::prefix('admin')->group(function () {
    // Public: no token required
    Route::post('/login',  [AdminAuthController::class, 'login']);
    // Protected: valid admin token required
    Route::middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        // Stats
        Route::get('/stats', [AdminJobController::class, 'stats']);
        // Jobs CRUD
        Route::get('/jobs',                    [AdminJobController::class, 'index']);
        Route::get('/jobs/{id}',               [AdminJobController::class, 'show']);
        Route::put('/jobs/{id}',               [AdminJobController::class, 'update']);
        Route::put('/jobs/{id}/toggle-featured',[AdminJobController::class, 'toggleFeatured']);
        Route::put('/jobs/{id}/toggle-urgent', [AdminJobController::class, 'toggleUrgent']);
        Route::delete('/jobs/{id}',            [AdminJobController::class, 'destroy']);
        Route::post('/jobs/{id}/logo',         [AdminJobController::class, 'uploadLogo']);
        // Users
        Route::get('/users',                        [AdminUserController::class, 'index']);
        Route::put('/users/{id}/toggle-status',     [AdminUserController::class, 'toggleStatus']);
        Route::get('/pro-subscribers',              [AdminUserController::class, 'proSubscribers']);
        Route::get('/ai-usage',                     [AdminUserController::class, 'aiUsage']);
        Route::get('/transactions',                 [AdminUserController::class, 'transactions']);
    });
});