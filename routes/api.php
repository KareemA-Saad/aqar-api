<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AdminAuthController;
use App\Http\Controllers\Api\V1\Auth\TenantUserAuthController;
use App\Http\Controllers\Api\V1\Auth\TwoFactorAuthController;
use App\Http\Controllers\Api\V1\Auth\UserAuthController;
use App\Http\Controllers\Api\V1\Landlord\Admin\AdminController;
use App\Http\Controllers\Api\V1\Landlord\Admin\PricePlanController as AdminPricePlanController;
use App\Http\Controllers\Api\V1\Landlord\Admin\RoleController;
use App\Http\Controllers\Api\V1\Landlord\PlanController;
use App\Http\Controllers\Api\V1\Landlord\Admin\SupportDepartmentController;
use App\Http\Controllers\Api\V1\Landlord\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Api\V1\Landlord\SubscriptionController;
use App\Http\Controllers\Api\V1\Landlord\SupportTicketController;
use App\Http\Controllers\Api\V1\Landlord\TenantController;
use App\Http\Controllers\Api\V1\Landlord\UserController;
use App\Http\Controllers\Api\V1\Landlord\UserDashboardController;
use App\Http\Controllers\Api\V1\Landlord\Admin\EmailSettingsController;
use App\Http\Controllers\Api\V1\Landlord\Admin\SettingsController;
use App\Http\Controllers\Api\V1\Shared\MediaController;
use App\Http\Controllers\Api\V1\Tenant\TenantInfoController;
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
    | Public Price Plan Routes
    |--------------------------------------------------------------------------
    | Public endpoints for viewing pricing plans.
    | No authentication required for most endpoints.
    */
    Route::prefix('plans')->name('plans.')->group(function () {
        Route::get('/', [PlanController::class, 'index'])->name('index');
        Route::get('compare', [PlanController::class, 'compare'])->name('compare');
        Route::get('{slug}', [PlanController::class, 'show'])->name('show');

        // Requires authentication
        Route::middleware('auth:api_user')->group(function () {
            Route::post('check-coupon', [PlanController::class, 'checkCoupon'])->name('check-coupon');
        });
    });

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

        // Two-Factor Authentication - Verify (public, uses 2FA token)
        Route::post('2fa/verify', [TwoFactorAuthController::class, 'verify'])
            ->middleware('throttle:5,1') // 5 attempts per minute
            ->name('2fa.verify');

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
        /*
        |--------------------------------------------------------------------------
        | Admin Management Routes
        |--------------------------------------------------------------------------
        */
        // Admin profile (own)
        Route::put('profile', [AdminController::class, 'updateProfile'])->name('profile.update');

        // Admin CRUD
        Route::get('admins', [AdminController::class, 'index'])->name('admins.index');
        Route::post('admins', [AdminController::class, 'store'])->name('admins.store');
        Route::get('admins/{admin}', [AdminController::class, 'show'])->name('admins.show');
        Route::put('admins/{admin}', [AdminController::class, 'update'])->name('admins.update');
        Route::delete('admins/{admin}', [AdminController::class, 'destroy'])->name('admins.destroy');
        Route::put('admins/{admin}/password', [AdminController::class, 'updatePassword'])->name('admins.password');

        /*
        |--------------------------------------------------------------------------
        | Role Management Routes
        |--------------------------------------------------------------------------
        */
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // Permissions
        Route::get('permissions', [RoleController::class, 'permissions'])->name('permissions.index');

        /*
        |--------------------------------------------------------------------------
        | Price Plan Management Routes (Admin)
        |--------------------------------------------------------------------------
        */
        Route::prefix('price-plans')->name('price-plans.')->group(function () {
            Route::get('/', [AdminPricePlanController::class, 'index'])->name('index');
            Route::post('/', [AdminPricePlanController::class, 'store'])->name('store');
            Route::get('{id}', [AdminPricePlanController::class, 'show'])->name('show');
            Route::put('{id}', [AdminPricePlanController::class, 'update'])->name('update');
            Route::delete('{id}', [AdminPricePlanController::class, 'destroy'])->name('destroy');
            Route::patch('{id}/toggle-status', [AdminPricePlanController::class, 'toggleStatus'])->name('toggle-status');
            Route::patch('{id}/reorder-features', [AdminPricePlanController::class, 'reorderFeatures'])->name('reorder-features');
        });

        /*
        |--------------------------------------------------------------------------
        | User Management Routes (Admin managing users)
        |--------------------------------------------------------------------------
        */
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
        Route::get('users/{user}/payments', [UserController::class, 'paymentHistory'])->name('users.payments');

        /*
        |--------------------------------------------------------------------------
        | Support Ticket Management Routes (Admin)
        |--------------------------------------------------------------------------
        */
        Route::prefix('support-tickets')->name('support-tickets.')->group(function () {
            Route::get('stats', [AdminSupportTicketController::class, 'stats'])->name('stats');
            Route::get('/', [AdminSupportTicketController::class, 'index'])->name('index');
            Route::get('{id}', [AdminSupportTicketController::class, 'show'])->name('show');
            Route::put('{id}', [AdminSupportTicketController::class, 'update'])->name('update');
            Route::post('{id}/reply', [AdminSupportTicketController::class, 'reply'])->name('reply');
            Route::post('{id}/close', [AdminSupportTicketController::class, 'close'])->name('close');
        });

        /*
        |--------------------------------------------------------------------------
        | Support Department Management Routes (Admin)
        |--------------------------------------------------------------------------
        */
        Route::prefix('support-departments')->name('support-departments.')->group(function () {
            Route::get('/', [SupportDepartmentController::class, 'index'])->name('index');
            Route::post('/', [SupportDepartmentController::class, 'store'])->name('store');
            Route::get('{department}', [SupportDepartmentController::class, 'show'])->name('show');
            Route::put('{department}', [SupportDepartmentController::class, 'update'])->name('update');
            Route::delete('{department}', [SupportDepartmentController::class, 'destroy'])->name('destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | Media Management Routes (Admin)
        |--------------------------------------------------------------------------
        */
        Route::prefix('media')->name('media.')->group(function () {
            Route::get('/', [MediaController::class, 'index'])->name('index');
            Route::post('/', [MediaController::class, 'upload'])->name('upload');
            Route::get('storage-info', [MediaController::class, 'storageInfo'])->name('storage-info');
            Route::post('bulk-delete', [MediaController::class, 'bulkDelete'])->name('bulk-delete');
            Route::get('{id}', [MediaController::class, 'show'])->name('show');
            Route::put('{id}', [MediaController::class, 'update'])->name('update');
            Route::delete('{id}', [MediaController::class, 'destroy'])->name('destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | Settings Management Routes (Admin)
        |--------------------------------------------------------------------------
        */
        Route::prefix('settings')->name('settings.')->group(function () {
            // Get all available settings groups
            Route::get('/', [SettingsController::class, 'groups'])->name('groups');

            // Get all settings organized by groups
            Route::get('all', [SettingsController::class, 'all'])->name('all');

            // Search settings by key pattern
            Route::get('search', [SettingsController::class, 'search'])->name('search');

            // Clear settings cache
            Route::post('clear-cache', [SettingsController::class, 'clearCache'])->name('clear-cache');

            // Single setting by key
            Route::get('key/{key}', [SettingsController::class, 'getByKey'])->name('key.show');
            Route::put('key/{key}', [SettingsController::class, 'updateByKey'])->name('key.update');

            // Settings by group
            Route::get('{group}', [SettingsController::class, 'index'])->name('group.show');
            Route::put('{group}', [SettingsController::class, 'update'])->name('group.update');
        });

        /*
        |--------------------------------------------------------------------------
        | Email Settings Management Routes (Admin)
        |--------------------------------------------------------------------------
        */
        Route::prefix('email-settings')->name('email-settings.')->group(function () {
            // SMTP Configuration
            Route::get('smtp', [EmailSettingsController::class, 'getSmtpConfig'])->name('smtp.show');
            Route::put('smtp', [EmailSettingsController::class, 'updateSmtpConfig'])->name('smtp.update');

            // Test Email
            Route::post('test', [EmailSettingsController::class, 'testEmail'])->name('test');

            // Email Templates
            Route::get('templates', [EmailSettingsController::class, 'getEmailTemplates'])->name('templates.index');
            Route::get('templates/{template}', [EmailSettingsController::class, 'getEmailTemplate'])->name('templates.show');
            Route::put('templates/{template}', [EmailSettingsController::class, 'updateEmailTemplate'])->name('templates.update');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | User Dashboard Routes (Self-Service)
    |--------------------------------------------------------------------------
    | Routes for authenticated users to manage their own profile and data.
    | Guard: api_user
    */
    Route::middleware('auth:api_user')->group(function () {
        // Dashboard
        Route::get('dashboard', [UserDashboardController::class, 'dashboard'])->name('dashboard');

        // Profile management
        Route::get('profile', [UserDashboardController::class, 'profile'])->name('profile');
        Route::put('profile', [UserDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::post('profile/change-password', [UserDashboardController::class, 'changePassword'])->name('profile.password');

        // User's own tenants (alternative to /tenants)
        Route::get('my-tenants', [UserDashboardController::class, 'tenants'])->name('my-tenants.index');
        Route::post('my-tenants', [UserDashboardController::class, 'createTenant'])->name('my-tenants.store');

        // Support tickets (legacy endpoint via UserDashboardController)
        Route::get('my-tickets', [UserDashboardController::class, 'supportTickets'])->name('my-tickets.index');

        /*
        |--------------------------------------------------------------------------
        | Two-Factor Authentication Routes (User)
        |--------------------------------------------------------------------------
        */
        Route::prefix('2fa')->name('2fa.')->group(function () {
            Route::get('status', [TwoFactorAuthController::class, 'status'])->name('status');
            Route::post('setup', [TwoFactorAuthController::class, 'setup'])->name('setup');
            Route::post('enable', [TwoFactorAuthController::class, 'enable'])->name('enable');
            Route::post('disable', [TwoFactorAuthController::class, 'disable'])->name('disable');
            Route::get('devices', [TwoFactorAuthController::class, 'devices'])->name('devices.index');
            Route::delete('devices', [TwoFactorAuthController::class, 'revokeAllDevices'])->name('devices.destroy-all');
            Route::delete('devices/{deviceId}', [TwoFactorAuthController::class, 'revokeDevice'])->name('devices.destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | Support Ticket Routes (User)
        |--------------------------------------------------------------------------
        */
        Route::prefix('support-tickets')->name('support-tickets.')->group(function () {
            Route::get('/', [SupportTicketController::class, 'index'])->name('index');
            Route::post('/', [SupportTicketController::class, 'store'])->name('store');
            Route::get('{id}', [SupportTicketController::class, 'show'])->name('show');
            Route::post('{id}/reply', [SupportTicketController::class, 'reply'])->name('reply');
            Route::post('{id}/close', [SupportTicketController::class, 'close'])->name('close');
        });

        // Support departments (for ticket creation dropdown)
        Route::get('support-departments', [SupportTicketController::class, 'departments'])->name('support-departments.index');

        // Payment history
        Route::get('my-payments', [UserDashboardController::class, 'paymentHistory'])->name('my-payments.index');

        /*
        |--------------------------------------------------------------------------
        | Subscription Management Routes
        |--------------------------------------------------------------------------
        | Routes for users to manage their subscriptions.
        */
        Route::prefix('subscriptions')->name('subscriptions.')->group(function () {
            Route::get('current', [SubscriptionController::class, 'current'])->name('current');
            Route::get('history', [SubscriptionController::class, 'history'])->name('history');
            Route::post('initiate', [SubscriptionController::class, 'initiate'])->name('initiate');
            Route::post('complete', [SubscriptionController::class, 'complete'])->name('complete');
            Route::post('upgrade', [SubscriptionController::class, 'upgrade'])->name('upgrade');
            Route::post('{subscriptionId}/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
            Route::post('{subscriptionId}/renew', [SubscriptionController::class, 'renew'])->name('renew');
        });

        /*
        |--------------------------------------------------------------------------
        | Media Management Routes (User)
        |--------------------------------------------------------------------------
        */
        Route::prefix('media')->name('media.')->group(function () {
            Route::get('/', [MediaController::class, 'index'])->name('index');
            Route::post('/', [MediaController::class, 'upload'])->name('upload');
            Route::get('storage-info', [MediaController::class, 'storageInfo'])->name('storage-info');
            Route::post('bulk-delete', [MediaController::class, 'bulkDelete'])->name('bulk-delete');
            Route::get('{id}', [MediaController::class, 'show'])->name('show');
            Route::put('{id}', [MediaController::class, 'update'])->name('update');
            Route::delete('{id}', [MediaController::class, 'destroy'])->name('destroy');
        });
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
            Route::get('info', [TenantInfoController::class, 'info'])->name('info');

            /*
            |--------------------------------------------------------------------------
            | Media Management Routes (Tenant Context)
            |--------------------------------------------------------------------------
            */
            Route::prefix('media')->name('media.')->group(function () {
                Route::get('/', [MediaController::class, 'index'])->name('index');
                Route::post('/', [MediaController::class, 'upload'])->name('upload');
                Route::get('storage-info', [MediaController::class, 'storageInfo'])->name('storage-info');
                Route::post('bulk-delete', [MediaController::class, 'bulkDelete'])->name('bulk-delete');
                Route::get('{id}', [MediaController::class, 'show'])->name('show');
                Route::put('{id}', [MediaController::class, 'update'])->name('update');
                Route::delete('{id}', [MediaController::class, 'destroy'])->name('destroy');
            });

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
