<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Planner\PlanLibraryController;
use App\Http\Controllers\User\ClientController;
use App\Http\Controllers\User\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//LOGIN AND REGISTER
Route::post('/v1/auth/login', [AuthController::class, 'login'])->name('login');
Route::post('/v1/auth/register', [AuthController::class, 'register']);

Route::group(['prefix' => 'v1/client'], function() {
    Route::post('/register', [ClientController::class, 'register']);
});

Route::group(['middleware' => 'auth:api'], function () {

    //AUTH
    Route::group(['prefix' => 'v1/auth'], function() {
        Route::get('/get-profile', [AuthController::class, 'getProfile']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/user-profile', [AuthController::class, 'userProfile']);    
    });

    Route::group(['prefix' => 'v1/user'], function() {
        Route::post('/update-profile', [UserController::class, 'updateProfile']);
        Route::get('/all-team-members', [UserController::class, 'getAllTeamMembers']);
    });

    Route::group(['prefix' => 'v1/plan-library'], function() {
        Route::get('/list', [PlanLibraryController::class, 'getAllPlanLibrary']);
        Route::post('/store', [PlanLibraryController::class, 'storePlanLibrary']);
        Route::post('/bulk-store', [PlanLibraryController::class, 'bulkSotrePlanLibrary']);
        Route::post('/import-excel', [PlanLibraryController::class, 'importExcelPlanLibrary']);
        Route::post('/approve/{id}', [PlanLibraryController::class, 'planApprove']);
        Route::delete('/delete/{id}', [PlanLibraryController::class, 'deletePlan']);
    });

    Route::group(['prefix' => 'v1/client'], function() {
        Route::post('/create-task', [ClientController::class, 'createTaskPlan']);
    });

});