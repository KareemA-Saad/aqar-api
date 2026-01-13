<?php

use Illuminate\Support\Facades\Route;
use Modules\TwoFactorAuthentication\Http\Controllers\Api\V1\Admin\TwoFactorAuthController;
use Modules\TwoFactorAuthentication\Http\Controllers\Api\V1\Frontend\TwoFactorAuthController as FrontendTwoFactorAuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your TwoFactorAuthentication module.
| These routes are loaded by the RouteServiceProvider and assigned the "api" middleware group.
| All routes are prefixed with 'api/v1'.
|
*/

// Public routes (with tenancy middleware)
Route::prefix('v1')->group(function () {
    // No public 2FA routes
});

// Authenticated routes (tenancy + auth:sanctum)
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    Route::prefix('frontend/two-factor-auth')->group(function () {
        // User 2FA management
        Route::get('/status', [FrontendTwoFactorAuthController::class, 'status']);
        Route::post('/generate-secret', [FrontendTwoFactorAuthController::class, 'generateSecret']);
        Route::post('/enable', [FrontendTwoFactorAuthController::class, 'enable']);
        Route::post('/disable', [FrontendTwoFactorAuthController::class, 'disable']);
        Route::post('/verify', [FrontendTwoFactorAuthController::class, 'verify']);
    });
});

// Admin routes (tenancy + auth:sanctum + package.active + feature:two_factor_auth)
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'package.active', 'feature:two_factor_auth'])->group(function () {
    Route::prefix('two-factor-auth')->group(function () {
        // Admin 2FA management
        Route::get('/users', [TwoFactorAuthController::class, 'usersWithTwoFactor']);
        Route::get('/statistics', [TwoFactorAuthController::class, 'statistics']);
        Route::get('/user/{userId}', [TwoFactorAuthController::class, 'getUserSettings']);
        Route::post('/disable/{userId}', [TwoFactorAuthController::class, 'adminDisable']);
    });
});
