<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AdminAuthController;
use App\Http\Controllers\Api\V1\Auth\TenantUserAuthController;
use App\Http\Controllers\Api\V1\Auth\UserAuthController;
use App\Http\Controllers\Api\V1\Landlord\TenantController;
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
    | Tenant Management Routes (Landlord Users)
    |--------------------------------------------------------------------------
    | Routes for users to manage their tenants.
    | Guard: api_user
    */
    Route::middleware('auth:api_user')->prefix('tenants')->name('tenants.')->group(function () {
        Route::get('/', [TenantController::class, 'index'])->name('index');
        Route::post('/', [TenantController::class, 'store'])->name('store');
        Route::get('{tenant}', [TenantController::class, 'show'])->name('show');
        Route::put('{tenant}', [TenantController::class, 'update'])->name('update');
        Route::delete('{tenant}', [TenantController::class, 'destroy'])->name('destroy');

        // Tenant switching - get new token with tenant context
        Route::post('{tenant}/switch', [TenantController::class, 'switchTenant'])->name('switch');

        // Database status and management
        Route::get('{tenant}/database-status', [TenantController::class, 'databaseStatus'])->name('database-status');
        Route::post('{tenant}/setup-database', [TenantController::class, 'setupDatabase'])->name('setup-database');
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Protected Routes
    |--------------------------------------------------------------------------
    | Routes for platform administrators.
    | Guard: api_admin
    */
    Route::middleware('auth:api_admin')->prefix('admin')->name('admin.')->group(function () {
        // Add admin-only endpoints here
        // Example: Route::apiResource('users', AdminUserController::class);
        // Example: Route::apiResource('all-tenants', AdminTenantController::class);
    });

    /*
    |--------------------------------------------------------------------------
    | Tenant Context Routes (With Database Switching)
    |--------------------------------------------------------------------------
    | Routes that operate within a tenant's database context.
    |
    | Middleware stack:
    | - auth:sanctum - Requires authentication (any guard)
    | - tenancy.token - Resolves and initializes tenant context
    | - tenant.context - Ensures valid tenant context exists
    | - package.active - Checks subscription is not expired
    |
    | For feature-specific routes, add:
    | - feature:featureName - Checks if feature is allowed by plan
    |
    | Example usage:
    | Route::middleware('feature:blog')->group(fn() => ...);
    | Route::middleware('feature:eCommerce,inventory')->group(fn() => ...);
    */
    Route::middleware(['auth:sanctum', 'tenancy.token', 'tenant.context', 'package.active'])
        ->prefix('tenant/{tenant}')
        ->name('tenant.')
        ->group(function () {
            // Tenant info endpoint - always available
            Route::get('info', function () {
                return response()->json([
                    'success' => true,
                    'message' => 'Tenant context active',
                    'data' => [
                        'tenant_id' => tenant_id(),
                        'settings' => tenant_settings(),
                        'package' => tenant_package_info(),
                        'remaining_days' => tenant_remaining_days(),
                        'features' => tenant_features(),
                    ],
                ]);
            })->name('info');

            // Add tenant-specific endpoints here
            // These routes have access to the tenant's database
            //
            // Example: Blog routes (requires 'blog' feature)
            // Route::middleware('feature:blog')->group(function () {
            //     Route::apiResource('blogs', BlogController::class);
            // });
            //
            // Example: Product routes (requires 'eCommerce' feature)
            // Route::middleware('feature:eCommerce')->group(function () {
            //     Route::apiResource('products', ProductController::class);
            //     Route::apiResource('categories', CategoryController::class);
            // });
            //
            // Example: Inventory routes (requires 'inventory' feature)
            // Route::middleware('feature:Inventory')->group(function () {
            //     Route::apiResource('inventory', InventoryController::class);
            // });
        });

    /*
    |--------------------------------------------------------------------------
    | Tenant User Routes (End Users within Tenant)
    |--------------------------------------------------------------------------
    | Routes for authenticated tenant end-users.
    | These users belong to a specific tenant and can only access that tenant's data.
    |
    | Guard: api_tenant_user
    */
    Route::middleware(['auth:api_tenant_user', 'tenancy.token', 'tenant.context', 'package.active'])
        ->prefix('tenant/{tenant}/user')
        ->name('tenant.user.')
        ->group(function () {
            // Add tenant user specific endpoints here
            // Example: User profile, orders, etc.
            //
            // Route::get('profile', [TenantUserController::class, 'profile'])->name('profile');
            // Route::put('profile', [TenantUserController::class, 'updateProfile'])->name('profile.update');
            // Route::apiResource('orders', TenantUserOrderController::class)->only(['index', 'show']);
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
