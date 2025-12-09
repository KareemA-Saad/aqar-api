<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AdminAuthController;
use App\Http\Controllers\Api\V1\Auth\TenantUserAuthController;
use App\Http\Controllers\Api\V1\Auth\UserAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

/*
|--------------------------------------------------------------------------
| API Version 1 Routes
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->name('api.v1.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Admin Authentication Routes
    |--------------------------------------------------------------------------
    | Routes for platform administrators.
    | Guard: api_admin
    */
    Route::prefix('admin/auth')->name('admin.auth.')->group(function () {
        // Public routes (no authentication required)
        Route::post('login', [AdminAuthController::class, 'login'])->name('login');
        Route::post('forgot-password', [AdminAuthController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('reset-password', [AdminAuthController::class, 'resetPassword'])->name('reset-password');

        // Protected routes (authentication required)
        Route::middleware('auth:api_admin')->group(function () {
            Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
            Route::get('me', [AdminAuthController::class, 'me'])->name('me');
            Route::post('refresh-token', [AdminAuthController::class, 'refreshToken'])->name('refresh-token');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | User Authentication Routes (Landlord / Tenant Owners)
    |--------------------------------------------------------------------------
    | Routes for users who own/manage tenants.
    | Guard: api_user
    */
    Route::prefix('auth')->name('auth.')->group(function () {
        // Public routes (no authentication required)
        Route::post('register', [UserAuthController::class, 'register'])->name('register');
        Route::post('login', [UserAuthController::class, 'login'])->name('login');
        Route::post('forgot-password', [UserAuthController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('reset-password', [UserAuthController::class, 'resetPassword'])->name('reset-password');
        Route::post('social-login', [UserAuthController::class, 'socialLogin'])->name('social-login');

        // Protected routes (authentication required)
        Route::middleware('auth:api_user')->group(function () {
            Route::post('logout', [UserAuthController::class, 'logout'])->name('logout');
            Route::get('me', [UserAuthController::class, 'me'])->name('me');
            Route::post('refresh-token', [UserAuthController::class, 'refreshToken'])->name('refresh-token');
            Route::post('verify-email', [UserAuthController::class, 'verifyEmail'])->name('verify-email');
            Route::post('resend-verification', [UserAuthController::class, 'resendVerification'])->name('resend-verification');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Tenant User Authentication Routes
    |--------------------------------------------------------------------------
    | Routes for end-users within a tenant context.
    | Guard: api_tenant_user
    | Note: Tenant context is resolved from X-Tenant-ID header or route parameter
    */
    Route::prefix('tenant/{tenant}/auth')->name('tenant.auth.')->group(function () {
        // Public routes (no authentication required)
        Route::post('register', [TenantUserAuthController::class, 'register'])->name('register');
        Route::post('login', [TenantUserAuthController::class, 'login'])->name('login');
        Route::post('forgot-password', [TenantUserAuthController::class, 'forgotPassword'])->name('forgot-password');
        Route::post('reset-password', [TenantUserAuthController::class, 'resetPassword'])->name('reset-password');

        // Protected routes (authentication required)
        Route::middleware('auth:api_tenant_user')->group(function () {
            Route::post('logout', [TenantUserAuthController::class, 'logout'])->name('logout');
            Route::get('me', [TenantUserAuthController::class, 'me'])->name('me');
            Route::post('refresh-token', [TenantUserAuthController::class, 'refreshToken'])->name('refresh-token');
            Route::post('verify-email', [TenantUserAuthController::class, 'verifyEmail'])->name('verify-email');
            Route::post('resend-verification', [TenantUserAuthController::class, 'resendVerification'])->name('resend-verification');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Protected API Routes
    |--------------------------------------------------------------------------
    | Add your protected API endpoints below.
    | Use appropriate guards based on user type:
    | - auth:api_admin - for admin-only routes
    | - auth:api_user - for user routes
    | - auth:api_tenant_user - for tenant user routes
    */

    // Example: Admin protected routes
    Route::middleware('auth:api_admin')->prefix('admin')->name('admin.')->group(function () {
        // Add admin-only endpoints here
        // Example: Route::apiResource('users', AdminUserController::class);
    });

    // Example: User protected routes
    Route::middleware('auth:api_user')->group(function () {
        // Add user endpoints here
        // Example: Route::apiResource('tenants', TenantController::class);
    });

    // Example: Tenant user protected routes
    Route::middleware(['auth:api_tenant_user', 'resolve.tenant'])->prefix('tenant/{tenant}')->name('tenant.')->group(function () {
        // Add tenant-specific endpoints here
        // Example: Route::apiResource('products', ProductController::class);
    });
});

/*
|--------------------------------------------------------------------------
| Health Check
|--------------------------------------------------------------------------
*/
Route::get('health', [\App\Http\Controllers\HealthController::class, 'check'])->name('health');

/*
|--------------------------------------------------------------------------
| Future API Versions
|--------------------------------------------------------------------------
| Add future API versions here following the same pattern:
| Route::prefix('v2')->name('api.v2.')->group(function () { ... });
|
*/
