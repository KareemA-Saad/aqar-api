<?php

use Illuminate\Support\Facades\Route;
use Modules\Newsletter\Http\Controllers\Api\V1\Admin\NewsletterController as AdminNewsletterController;
use Modules\Newsletter\Http\Controllers\Api\V1\Frontend\NewsletterController as FrontendNewsletterController;

/*
|--------------------------------------------------------------------------
| API Routes - Newsletter Module
|--------------------------------------------------------------------------
|
| Three-tier routing:
| 1. Public tier: tenancy.token + tenant.context
| 2. Authenticated tier: + auth:sanctum
| 3. Admin tier: + package.active + feature:newsletter
|
*/

// ========================================
// TIER 1: PUBLIC ROUTES (Tenancy only)
// ========================================
Route::middleware(['tenancy.token', 'tenant.context'])
    ->prefix('api/v1/frontend')
    ->group(function () {
        // Newsletter subscription management
        Route::post('newsletter/subscribe', [FrontendNewsletterController::class, 'subscribe']);
        Route::get('newsletter/verify/{token}', [FrontendNewsletterController::class, 'verify']);
        Route::post('newsletter/unsubscribe', [FrontendNewsletterController::class, 'unsubscribe']);
    });

// ========================================
// TIER 3: ADMIN ROUTES (Tenancy + Auth + Package + Feature)
// ========================================
Route::middleware(['tenancy.token', 'tenant.context', 'auth:sanctum', 'package.active', 'feature:newsletter'])
    ->prefix('api/v1/admin')
    ->group(function () {
        // Newsletter subscription management
        Route::get('newsletters', [AdminNewsletterController::class, 'index']);
        Route::get('newsletters/statistics/overview', [AdminNewsletterController::class, 'statistics']);
        Route::get('newsletters/export/emails', [AdminNewsletterController::class, 'exportEmails']);
        Route::get('newsletters/{newsletter}', [AdminNewsletterController::class, 'show']);
        Route::delete('newsletters/{newsletter}', [AdminNewsletterController::class, 'destroy']);
        Route::post('newsletters/bulk', [AdminNewsletterController::class, 'bulkAction']);
    });
