<?php

use App\Http\Controllers\Api\CampaignTagController;
use App\Http\Controllers\Api\SiteAnalyticController;
use App\Http\Controllers\Api\SiteCategoryController;
use App\Http\Controllers\Api\SiteMenuController;
use App\Http\Controllers\Api\SiteTagController;
use App\Http\Controllers\Api\WordpressServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AnalyticController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\ChannelController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\CMSSiteController;
use App\Http\Controllers\Api\AdTemplateController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\CManagerController;
use App\Http\Controllers\Api\AdPartnerController;
use App\Http\Controllers\Api\CopymaticController;
use App\Http\Controllers\Api\CopyscapeController;
use App\Http\Middleware\JubileeToken;
use App\Http\Controllers\Api\AdServiceController;
use App\Http\Controllers\Api\FacebookTwoTierController;
use App\Http\Controllers\Api\ReadableApiController;
use App\Http\Controllers\Api\SiteAdController;
use App\Http\Controllers\Api\AdBuilderController;
use App\Http\Controllers\Api\FacebookInterestController;
use App\Http\Controllers\Api\FacebookLookalikeController;
use App\Http\Controllers\Api\BlackListController;
use App\Http\Controllers\Api\ContactUsController;
use App\Http\Controllers\Api\RuleSetController;
use App\Http\Controllers\Api\KeywordSpinningController;
use App\Http\Controllers\Api\MROASController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\SubDomainController;

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


/**
 * Authenticated Routes
 */
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    /** Logout route */
    Route::post('logout', [AuthController::class, 'logout']);

    /** Fetch auth user **/
    Route::get('auth/user', [AuthController::class, 'authUser']);

    /** API Routes */
    UserController::apiRoutes();
    AdTemplateController::apiRoutes();
});

/**
 * Auth Routes
 */
Route::prefix('v1')->group(function () {
    /** Login route */
    Route::post('login', [AuthController::class, 'login']);

    /** Register route */
    Route::post('register', [AuthController::class, 'register']);

});
