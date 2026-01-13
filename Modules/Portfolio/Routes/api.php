<?php

use Illuminate\Support\Facades\Route;
use Modules\Portfolio\Http\Controllers\Api\V1\Admin\PortfolioController as AdminPortfolioController;
use Modules\Portfolio\Http\Controllers\Api\V1\Admin\PortfolioCategoryController as AdminPortfolioCategoryController;
use Modules\Portfolio\Http\Controllers\Api\V1\Frontend\PortfolioController as FrontendPortfolioController;

/*
|--------------------------------------------------------------------------
| API Routes - Portfolio Module
|--------------------------------------------------------------------------
|
| Three-tier routing:
| 1. Public tier: tenancy.token + tenant.context
| 2. Authenticated tier: + auth:sanctum
| 3. Admin tier: + package.active + feature:portfolio
|
*/

// ========================================
// TIER 1: PUBLIC ROUTES (Tenancy only)
// ========================================
Route::middleware(['tenancy.token', 'tenant.context'])
    ->prefix('api/v1/frontend')
    ->group(function () {
        // Public portfolio browsing
        Route::get('portfolios', [FrontendPortfolioController::class, 'index']);
        Route::get('portfolios/{slug}', [FrontendPortfolioController::class, 'show']);
        Route::get('portfolios/category/{categoryId}', [FrontendPortfolioController::class, 'byCategory']);
        Route::get('portfolios/tag/{tag}', [FrontendPortfolioController::class, 'byTag']);
        Route::get('portfolios/search/{query}', [FrontendPortfolioController::class, 'search']);
        Route::get('portfolio-categories', [FrontendPortfolioController::class, 'categories']);
        Route::get('portfolio-tags', [FrontendPortfolioController::class, 'tags']);
    });

// ========================================
// TIER 3: ADMIN ROUTES (Tenancy + Auth + Package + Feature)
// ========================================
Route::middleware(['tenancy.token', 'tenant.context', 'auth:sanctum', 'package.active', 'feature:portfolio'])
    ->prefix('api/v1/admin')
    ->group(function () {
        // Portfolio management
        Route::get('portfolios', [AdminPortfolioController::class, 'index']);
        Route::post('portfolios', [AdminPortfolioController::class, 'store']);
        Route::get('portfolios/tags/all', [AdminPortfolioController::class, 'allTags']);
        Route::get('portfolios/{portfolio}', [AdminPortfolioController::class, 'show']);
        Route::put('portfolios/{portfolio}', [AdminPortfolioController::class, 'update']);
        Route::delete('portfolios/{portfolio}', [AdminPortfolioController::class, 'destroy']);
        Route::post('portfolios/bulk', [AdminPortfolioController::class, 'bulkAction']);

        // Portfolio category management
        Route::get('portfolio-categories', [AdminPortfolioCategoryController::class, 'index']);
        Route::post('portfolio-categories', [AdminPortfolioCategoryController::class, 'store']);
        Route::get('portfolio-categories/{portfolioCategory}', [AdminPortfolioCategoryController::class, 'show']);
        Route::put('portfolio-categories/{portfolioCategory}', [AdminPortfolioCategoryController::class, 'update']);
        Route::delete('portfolio-categories/{portfolioCategory}', [AdminPortfolioCategoryController::class, 'destroy']);
    });
