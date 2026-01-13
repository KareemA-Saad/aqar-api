<?php

use Illuminate\Support\Facades\Route;
use Modules\Knowledgebase\Http\Controllers\Api\V1\Admin\KnowledgebaseController as AdminKnowledgebaseController;
use Modules\Knowledgebase\Http\Controllers\Api\V1\Admin\KnowledgebaseCategoryController as AdminKnowledgebaseCategoryController;
use Modules\Knowledgebase\Http\Controllers\Api\V1\Frontend\KnowledgebaseController as FrontendKnowledgebaseController;

/*
|--------------------------------------------------------------------------
| API Routes - Knowledgebase Module
|--------------------------------------------------------------------------
|
| Three-tier routing:
| 1. Public tier: tenancy.token + tenant.context
| 2. Authenticated tier: + auth:sanctum
| 3. Admin tier: + package.active + feature:knowledgebase
|
*/

// ========================================
// TIER 1: PUBLIC ROUTES (Tenancy only)
// ========================================
Route::middleware(['tenancy.token', 'tenant.context'])
    ->prefix('api/v1/frontend')
    ->group(function () {
        // Public knowledgebase browsing
        Route::get('knowledgebases', [FrontendKnowledgebaseController::class, 'index']);
        Route::get('knowledgebases/popular/list', [FrontendKnowledgebaseController::class, 'popular']);
        Route::get('knowledgebases/{slug}', [FrontendKnowledgebaseController::class, 'show']);
        Route::get('knowledgebases/category/{categoryId}', [FrontendKnowledgebaseController::class, 'byCategory']);
        Route::get('knowledgebases/search/{query}', [FrontendKnowledgebaseController::class, 'search']);
        Route::get('knowledgebase-categories', [FrontendKnowledgebaseController::class, 'categories']);
    });

// ========================================
// TIER 3: ADMIN ROUTES (Tenancy + Auth + Package + Feature)
// ========================================
Route::middleware(['tenancy.token', 'tenant.context', 'auth:sanctum', 'package.active', 'feature:knowledgebase'])
    ->prefix('api/v1/admin')
    ->group(function () {
        // Knowledgebase management
        Route::get('knowledgebases', [AdminKnowledgebaseController::class, 'index']);
        Route::post('knowledgebases', [AdminKnowledgebaseController::class, 'store']);
        Route::get('knowledgebases/popular/top', [AdminKnowledgebaseController::class, 'popular']);
        Route::get('knowledgebases/{knowledgebase}', [AdminKnowledgebaseController::class, 'show']);
        Route::put('knowledgebases/{knowledgebase}', [AdminKnowledgebaseController::class, 'update']);
        Route::delete('knowledgebases/{knowledgebase}', [AdminKnowledgebaseController::class, 'destroy']);
        Route::post('knowledgebases/bulk', [AdminKnowledgebaseController::class, 'bulkAction']);

        // Knowledgebase category management
        Route::get('knowledgebase-categories', [AdminKnowledgebaseCategoryController::class, 'index']);
        Route::post('knowledgebase-categories', [AdminKnowledgebaseCategoryController::class, 'store']);
        Route::get('knowledgebase-categories/{knowledgebaseCategory}', [AdminKnowledgebaseCategoryController::class, 'show']);
        Route::put('knowledgebase-categories/{knowledgebaseCategory}', [AdminKnowledgebaseCategoryController::class, 'update']);
        Route::delete('knowledgebase-categories/{knowledgebaseCategory}', [AdminKnowledgebaseCategoryController::class, 'destroy']);
    });
