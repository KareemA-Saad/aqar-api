<?php

use Illuminate\Support\Facades\Route;
use Modules\Wallet\Http\Controllers\Api\V1\Admin\WalletController;
use Modules\Wallet\Http\Controllers\Api\V1\Admin\WalletHistoryController;
use Modules\Wallet\Http\Controllers\Api\V1\Admin\WalletSettingsController;
use Modules\Wallet\Http\Controllers\Api\V1\Frontend\WalletController as FrontendWalletController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your Wallet module.
| These routes are loaded by the RouteServiceProvider and assigned the "api" middleware group.
| All routes are prefixed with 'api/v1'.
|
*/

// Public routes (with tenancy middleware)
Route::prefix('v1')->group(function () {
    // No public wallet routes
});

// Authenticated routes (tenancy + auth:sanctum)
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    Route::prefix('frontend')->group(function () {
        // User wallet management
        Route::get('/wallet', [FrontendWalletController::class, 'show']);
        Route::get('/wallet/history', [FrontendWalletController::class, 'history']);
        Route::post('/wallet/deposit', [FrontendWalletController::class, 'deposit']);
        Route::post('/wallet/add-funds', [FrontendWalletController::class, 'addFunds']);
        
        // Wallet settings
        Route::get('/wallet/settings', [FrontendWalletController::class, 'getSettings']);
        Route::put('/wallet/settings', [FrontendWalletController::class, 'updateSettings']);
        Route::post('/wallet/settings/toggle-auto-renew', [FrontendWalletController::class, 'toggleAutoRenew']);
        Route::post('/wallet/settings/toggle-alert', [FrontendWalletController::class, 'toggleAlert']);
    });
});

// Admin routes (tenancy + auth:sanctum + package.active + feature:wallet)
Route::prefix('v1/admin')->middleware(['auth:sanctum', 'package.active', 'feature:wallet'])->group(function () {
    // Wallet management
    Route::get('/wallets', [WalletController::class, 'index']);
    Route::get('/wallets/{id}', [WalletController::class, 'show']);
    Route::put('/wallets/{id}/balance', [WalletController::class, 'updateBalance']);
    Route::put('/wallets/{id}/status', [WalletController::class, 'updateStatus']);
    Route::delete('/wallets/{id}', [WalletController::class, 'destroy']);
    Route::post('/wallets/bulk', [WalletController::class, 'bulkAction']);
    Route::get('/wallets/statistics/overview', [WalletController::class, 'statistics']);
    Route::get('/wallets/low-balance/list', [WalletController::class, 'lowBalanceWallets']);

    // Wallet history management
    Route::get('/wallet-histories', [WalletHistoryController::class, 'index']);
    Route::get('/wallet-histories/{id}', [WalletHistoryController::class, 'show']);
    Route::put('/wallet-histories/{id}/approve', [WalletHistoryController::class, 'approveManualPayment']);
    Route::put('/wallet-histories/{id}/status', [WalletHistoryController::class, 'updatePaymentStatus']);
    Route::delete('/wallet-histories/{id}', [WalletHistoryController::class, 'destroy']);
    Route::post('/wallet-histories/bulk', [WalletHistoryController::class, 'bulkAction']);
    Route::get('/wallet-histories/statistics/overview', [WalletHistoryController::class, 'statistics']);

    // Wallet settings management
    Route::get('/wallet-settings/user/{userId}', [WalletSettingsController::class, 'show']);
    Route::put('/wallet-settings/user/{userId}', [WalletSettingsController::class, 'update']);
    Route::delete('/wallet-settings/user/{userId}', [WalletSettingsController::class, 'destroy']);
});
