<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\CouponManage\Http\Controllers\Api\V1\Admin\CouponController;
use Modules\CouponManage\Http\Controllers\Api\V1\Frontend\CouponValidationController;

/*
|--------------------------------------------------------------------------
| CouponManage API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/tenant/{tenant}')->name('api.v1.tenant.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Coupon Validation
    |--------------------------------------------------------------------------
    */
    Route::middleware(['tenancy.token', 'tenant.context'])
        ->prefix('coupons')
        ->name('coupons.')
        ->group(function () {
            Route::post('validate', [CouponValidationController::class, 'validateCoupon'])->name('validate');
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Coupon Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:api_tenant_admin', 'tenancy.token', 'tenant.context', 'package.active', 'feature:ecommerce'])
        ->prefix('admin/coupons')
        ->name('admin.coupons.')
        ->group(function () {
            Route::get('/', [CouponController::class, 'index'])->name('index');
            Route::post('/', [CouponController::class, 'store'])->name('store');
            Route::get('{id}', [CouponController::class, 'show'])->where('id', '[0-9]+')->name('show');
            Route::put('{id}', [CouponController::class, 'update'])->where('id', '[0-9]+')->name('update');
            Route::delete('{id}', [CouponController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
            Route::post('{id}/toggle-status', [CouponController::class, 'toggleStatus'])->where('id', '[0-9]+')->name('toggle-status');
        });
});
