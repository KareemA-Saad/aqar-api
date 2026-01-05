<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\ShippingModule\Http\Controllers\Api\V1\Admin\ZoneController;
use Modules\ShippingModule\Http\Controllers\Api\V1\Admin\ShippingMethodController;

/*
|--------------------------------------------------------------------------
| ShippingModule API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1/tenant/{tenant}')->name('api.v1.tenant.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Admin Zone Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:api_tenant_admin', 'tenancy.token', 'tenant.context', 'package.active', 'feature:ecommerce'])
        ->prefix('admin/shipping/zones')
        ->name('admin.shipping.zones.')
        ->group(function () {
            Route::get('/', [ZoneController::class, 'index'])->name('index');
            Route::post('/', [ZoneController::class, 'store'])->name('store');
            Route::get('{id}', [ZoneController::class, 'show'])->where('id', '[0-9]+')->name('show');
            Route::put('{id}', [ZoneController::class, 'update'])->where('id', '[0-9]+')->name('update');
            Route::delete('{id}', [ZoneController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Shipping Method Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:api_tenant_admin', 'tenancy.token', 'tenant.context', 'package.active', 'feature:ecommerce'])
        ->prefix('admin/shipping/methods')
        ->name('admin.shipping.methods.')
        ->group(function () {
            Route::get('/', [ShippingMethodController::class, 'index'])->name('index');
            Route::post('/', [ShippingMethodController::class, 'store'])->name('store');
            Route::get('{id}', [ShippingMethodController::class, 'show'])->where('id', '[0-9]+')->name('show');
            Route::put('{id}', [ShippingMethodController::class, 'update'])->where('id', '[0-9]+')->name('update');
            Route::delete('{id}', [ShippingMethodController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
            Route::post('{id}/set-default', [ShippingMethodController::class, 'setDefault'])->where('id', '[0-9]+')->name('set-default');
        });
});
