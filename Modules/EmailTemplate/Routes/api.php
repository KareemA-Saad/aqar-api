<?php

use Illuminate\Support\Facades\Route;
use Modules\EmailTemplate\Http\Controllers\Api\V1\Admin\EmailTemplateController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your EmailTemplate module.
| These routes are loaded by the RouteServiceProvider and assigned the "api" middleware group.
| All routes are prefixed with 'api/v1'.
|
*/

// Public routes (with tenancy middleware)
Route::prefix('v1')->group(function () {
    // No public email template routes
});

// Authenticated routes (tenancy + auth:sanctum)
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // No authenticated-only email template routes
});

// Admin routes (tenancy + auth:sanctum + package.active + feature:email_template)
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'package.active', 'feature:email_template'])->group(function () {
    // Email template management
    Route::get('/email-templates', [EmailTemplateController::class, 'index']);
    Route::post('/email-templates', [EmailTemplateController::class, 'store']);
    Route::get('/email-templates/{id}', [EmailTemplateController::class, 'show']);
    Route::put('/email-templates/{id}', [EmailTemplateController::class, 'update']);
    Route::delete('/email-templates/{id}', [EmailTemplateController::class, 'destroy']);
    Route::post('/email-templates/bulk', [EmailTemplateController::class, 'bulkAction']);
    
    // Email template utilities
    Route::get('/email-templates/types/list', [EmailTemplateController::class, 'templateTypes']);
    Route::get('/email-templates/variables/list', [EmailTemplateController::class, 'templateVariables']);
    Route::get('/email-templates/statistics/overview', [EmailTemplateController::class, 'statistics']);
    Route::post('/email-templates/{id}/duplicate', [EmailTemplateController::class, 'duplicate']);
    Route::post('/email-templates/{id}/preview', [EmailTemplateController::class, 'preview']);
});
