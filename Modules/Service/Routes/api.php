<?php

use Illuminate\Support\Facades\Route;
use Modules\Service\Http\Controllers\Api\V1\Admin\ServiceController as AdminServiceController;
use Modules\Service\Http\Controllers\Api\V1\Admin\ServiceCategoryController as AdminServiceCategoryController;
use Modules\Service\Http\Controllers\Api\V1\Frontend\ServiceController as FrontendServiceController;

/*
|--------------------------------------------------------------------------
| API Routes - Service Module
|--------------------------------------------------------------------------
|
| Three-tier routing:
| 1. Public tier: tenancy.token + tenant.context
| 2. Authenticated tier: + auth:sanctum
| 3. Admin tier: + package.active + feature:service
|
*/

// ========================================
// TIER 1: PUBLIC ROUTES (Tenancy only)
// ========================================
Route::middleware(['tenancy.token', 'tenant.context'])
    ->prefix('api/v1/frontend')
    ->group(function () {
        // Public service browsing
        Route::get('services', [FrontendServiceController::class, 'index']);
        Route::get('services/{slug}', [FrontendServiceController::class, 'show']);
        Route::get('services/category/{categoryId}', [FrontendServiceController::class, 'byCategory']);
        Route::get('services/search/{query}', [FrontendServiceController::class, 'search']);
        Route::get('service-categories', [FrontendServiceController::class, 'categories']);
    });

// ========================================
// TIER 3: ADMIN ROUTES (Tenancy + Auth + Package + Feature)
// ========================================
Route::middleware(['tenancy.token', 'tenant.context', 'auth:sanctum', 'package.active', 'feature:service'])
    ->prefix('api/v1/admin')
    ->group(function () {
        // Service management
        Route::get('services', [AdminServiceController::class, 'index']);
        Route::post('services', [AdminServiceController::class, 'store']);
        Route::get('services/{service}', [AdminServiceController::class, 'show']);
        Route::put('services/{service}', [AdminServiceController::class, 'update']);
        Route::delete('services/{service}', [AdminServiceController::class, 'destroy']);
        Route::post('services/bulk', [AdminServiceController::class, 'bulkAction']);

        // Service category management
        Route::get('service-categories', [AdminServiceCategoryController::class, 'index']);
        Route::post('service-categories', [AdminServiceCategoryController::class, 'store']);
        Route::post('service-categories/bulk', [AdminServiceCategoryController::class, 'bulkAction']);
        Route::get('service-categories/{serviceCategory}', [AdminServiceCategoryController::class, 'show']);
        Route::put('service-categories/{serviceCategory}', [AdminServiceCategoryController::class, 'update']);
        Route::delete('service-categories/{serviceCategory}', [AdminServiceCategoryController::class, 'destroy']);
    });
