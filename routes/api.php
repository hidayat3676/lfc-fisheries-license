<?php

use App\Http\Controllers\Api\V1\ApplicationController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogueController;
use App\Http\Controllers\Api\V1\ExecutiveController;
use App\Http\Controllers\Api\V1\LicenseController;
use App\Http\Controllers\Api\V1\OfficerController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ViolationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'name' => 'RFLMS API',
            'version' => 'v1',
            'status' => 'ok',
            'docs' => 'backend/docs/api-v1.md',
            'try' => [
                url('/api/v1/districts'),
                url('/api/v1/license-categories'),
                url('/api/v1/reservoirs').'?q=Kundal',
                'POST '.url('/api/v1/auth/login'),
            ],
            'note' => 'Open a concrete path below (browser or Postman). Use header Accept: application/json.',
        ]);
    });

    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/districts', [CatalogueController::class, 'districts']);
    Route::get('/reservoirs', [CatalogueController::class, 'reservoirs']);
    Route::get('/reservoirs/{reservoir}', [CatalogueController::class, 'showReservoir']);
    Route::get('/license-categories', [CatalogueController::class, 'categories']);

    Route::post('/reports/violation', [ViolationController::class, 'store']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/verify-pattern', [AuthController::class, 'verifyPattern']);

        Route::get('/profile', [ProfileController::class, 'show']);
        Route::match(['put', 'post'], '/profile', [ProfileController::class, 'update']);
        Route::post('/profile/pattern-lock', [ProfileController::class, 'updatePatternLock']);

        Route::get('/licenses/applications', [ApplicationController::class, 'index']);
        Route::post('/licenses/applications', [ApplicationController::class, 'store']);
        Route::get('/licenses/applications/{application}', [ApplicationController::class, 'show']);

        Route::get('/licenses', [LicenseController::class, 'index']);
        Route::get('/licenses/{license}', [LicenseController::class, 'show']);

        Route::get('/reports/violation', [ViolationController::class, 'index']);
        Route::get('/reports/violation/{report}', [ViolationController::class, 'show']);

        Route::middleware('staff')->prefix('officer')->group(function () {
            Route::get('/dashboard', [OfficerController::class, 'dashboard']);
            Route::get('/offices', [OfficerController::class, 'offices']);
            Route::get('/offices/applications', [OfficerController::class, 'officeApplications']);
            Route::get('/offices/{office}/applications', [OfficerController::class, 'singleOfficeApplications']);
            Route::post('/verify-qr', [OfficerController::class, 'verifyQr']);
            Route::match(['get', 'post'], '/search-cnic', [OfficerController::class, 'searchByCnic']);
            Route::post('/walkin-registration', [OfficerController::class, 'walkinRegistration']);
            Route::post('/walkin-application', [OfficerController::class, 'walkinApplication']);
            Route::get('/applications', [OfficerController::class, 'applications']);
            Route::post('/applications/{application}/approve', [OfficerController::class, 'approveApplication']);
            Route::post('/applications/{application}/reject', [OfficerController::class, 'rejectApplication']);
            Route::post('/applications/{application}/request-info', [OfficerController::class, 'requestInfoApplication']);
            Route::get('/licenses/verify/{token}', [LicenseController::class, 'verify']);
            Route::get('/violations', [ViolationController::class, 'index']);
            Route::get('/violations/{violation}', [ViolationController::class, 'show']);
            Route::patch('/violations/{violation}', [ViolationController::class, 'update']);
        });

        Route::middleware('executive')->prefix('executive')->group(function () {
            Route::get('/dashboard', [ExecutiveController::class, 'dashboard']);
        });
    });
});
