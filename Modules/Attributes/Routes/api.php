<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Attributes\Http\Controllers\Api\V1\Admin\CategoryController;
use Modules\Attributes\Http\Controllers\Api\V1\Admin\SubCategoryController;
use Modules\Attributes\Http\Controllers\Api\V1\Admin\ChildCategoryController;
use Modules\Attributes\Http\Controllers\Api\V1\Admin\BrandController;
use Modules\Attributes\Http\Controllers\Api\V1\Admin\ColorController;
use Modules\Attributes\Http\Controllers\Api\V1\Admin\SizeController;
use Modules\Attributes\Http\Controllers\Api\V1\Admin\TagController;
use Modules\Attributes\Http\Controllers\Api\V1\Frontend\AttributeController;

/*
|--------------------------------------------------------------------------
| Attributes Module API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the Attributes module. These routes handle
| product categories, brands, colors, sizes, and tags management.
|
*/

Route::prefix('v1/tenant/{tenant}')->name('api.v1.tenant.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Attribute Routes (Frontend)
    |--------------------------------------------------------------------------
    | Public endpoints for fetching attributes (categories, brands, etc.)
    */
    Route::middleware(['tenancy.token', 'tenant.context'])
        ->prefix('attributes')
        ->name('attributes.')
        ->group(function () {
            // Get all categories with hierarchy
            Route::get('categories', [AttributeController::class, 'categories'])->name('categories');

            // Get all brands
            Route::get('brands', [AttributeController::class, 'brands'])->name('brands');

            // Get all colors
            Route::get('colors', [AttributeController::class, 'colors'])->name('colors');

            // Get all sizes
            Route::get('sizes', [AttributeController::class, 'sizes'])->name('sizes');

            // Get all tags
            Route::get('tags', [AttributeController::class, 'tags'])->name('tags');
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Category Routes
    |--------------------------------------------------------------------------
    | Protected routes for category management.
    */
    Route::middleware(['auth:api_tenant_admin', 'tenancy.token', 'tenant.context', 'package.active', 'feature:ecommerce'])
        ->prefix('admin/categories')
        ->name('admin.categories.')
        ->group(function () {
            Route::get('/', [CategoryController::class, 'index'])->name('index');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::get('{id}', [CategoryController::class, 'show'])->where('id', '[0-9]+')->name('show');
            Route::put('{id}', [CategoryController::class, 'update'])->where('id', '[0-9]+')->name('update');
            Route::delete('{id}', [CategoryController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
            Route::post('bulk-delete', [CategoryController::class, 'bulkDelete'])->name('bulk-delete');
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Sub-Category Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:api_tenant_admin', 'tenancy.token', 'tenant.context', 'package.active', 'feature:ecommerce'])
        ->prefix('admin/sub-categories')
        ->name('admin.sub-categories.')
        ->group(function () {
            Route::get('/', [SubCategoryController::class, 'index'])->name('index');
            Route::post('/', [SubCategoryController::class, 'store'])->name('store');
            Route::get('{id}', [SubCategoryController::class, 'show'])->where('id', '[0-9]+')->name('show');
            Route::put('{id}', [SubCategoryController::class, 'update'])->where('id', '[0-9]+')->name('update');
            Route::delete('{id}', [SubCategoryController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Child-Category Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:api_tenant_admin', 'tenancy.token', 'tenant.context', 'package.active', 'feature:ecommerce'])
        ->prefix('admin/child-categories')
        ->name('admin.child-categories.')
        ->group(function () {
            Route::get('/', [ChildCategoryController::class, 'index'])->name('index');
            Route::post('/', [ChildCategoryController::class, 'store'])->name('store');
            Route::get('{id}', [ChildCategoryController::class, 'show'])->where('id', '[0-9]+')->name('show');
            Route::put('{id}', [ChildCategoryController::class, 'update'])->where('id', '[0-9]+')->name('update');
            Route::delete('{id}', [ChildCategoryController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Brand Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:api_tenant_admin', 'tenancy.token', 'tenant.context', 'package.active', 'feature:ecommerce'])
        ->prefix('admin/brands')
        ->name('admin.brands.')
        ->group(function () {
            Route::get('/', [BrandController::class, 'index'])->name('index');
            Route::post('/', [BrandController::class, 'store'])->name('store');
            Route::get('{id}', [BrandController::class, 'show'])->where('id', '[0-9]+')->name('show');
            Route::put('{id}', [BrandController::class, 'update'])->where('id', '[0-9]+')->name('update');
            Route::delete('{id}', [BrandController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Color Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:api_tenant_admin', 'tenancy.token', 'tenant.context', 'package.active', 'feature:ecommerce'])
        ->prefix('admin/colors')
        ->name('admin.colors.')
        ->group(function () {
            Route::get('/', [ColorController::class, 'index'])->name('index');
            Route::post('/', [ColorController::class, 'store'])->name('store');
            Route::get('{id}', [ColorController::class, 'show'])->where('id', '[0-9]+')->name('show');
            Route::put('{id}', [ColorController::class, 'update'])->where('id', '[0-9]+')->name('update');
            Route::delete('{id}', [ColorController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Size Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:api_tenant_admin', 'tenancy.token', 'tenant.context', 'package.active', 'feature:ecommerce'])
        ->prefix('admin/sizes')
        ->name('admin.sizes.')
        ->group(function () {
            Route::get('/', [SizeController::class, 'index'])->name('index');
            Route::post('/', [SizeController::class, 'store'])->name('store');
            Route::get('{id}', [SizeController::class, 'show'])->where('id', '[0-9]+')->name('show');
            Route::put('{id}', [SizeController::class, 'update'])->where('id', '[0-9]+')->name('update');
            Route::delete('{id}', [SizeController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Tag Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:api_tenant_admin', 'tenancy.token', 'tenant.context', 'package.active', 'feature:ecommerce'])
        ->prefix('admin/tags')
        ->name('admin.tags.')
        ->group(function () {
            Route::get('/', [TagController::class, 'index'])->name('index');
            Route::post('/', [TagController::class, 'store'])->name('store');
            Route::get('{id}', [TagController::class, 'show'])->where('id', '[0-9]+')->name('show');
            Route::put('{id}', [TagController::class, 'update'])->where('id', '[0-9]+')->name('update');
            Route::delete('{id}', [TagController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
        });
});
