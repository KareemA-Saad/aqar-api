<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\Api\V1\Admin\ProductController as AdminProductController;
use Modules\Product\Http\Controllers\Api\V1\Admin\OrderController as AdminOrderController;
use Modules\Product\Http\Controllers\Api\V1\Frontend\ProductController as FrontendProductController;
use Modules\Product\Http\Controllers\Api\V1\Frontend\CartController;
use Modules\Product\Http\Controllers\Api\V1\Frontend\CheckoutController;

/*
|--------------------------------------------------------------------------
| Product Module API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the Product/E-Commerce module. These routes
| are loaded by the RouteServiceProvider within a group which is assigned
| the "api" middleware group.
|
*/

/*
|--------------------------------------------------------------------------
| Tenant Context Routes (With Database Switching)
|--------------------------------------------------------------------------
| Routes that operate within a tenant's database context.
|
| Middleware stack:
| - tenancy.token - Resolves and initializes tenant context
| - tenant.context - Ensures valid tenant context exists
|
| For admin routes, add:
| - auth:sanctum - Requires authentication
| - package.active - Checks subscription is not expired
| - feature:ecommerce - Checks if e-commerce feature is allowed by plan
*/

Route::prefix('v1/tenant/{tenant}')->name('api.v1.tenant.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Product Routes (Frontend)
    |--------------------------------------------------------------------------
    | Public endpoints for browsing products.
    | Only tenant context required, no authentication.
    */
    Route::middleware(['tenancy.token', 'tenant.context'])
        ->prefix('products')
        ->name('products.')
        ->group(function () {
            // Get available filters (colors, sizes, brands, price range)
            Route::get('filters', [FrontendProductController::class, 'filters'])->name('filters');

            // Search products
            Route::get('search', [FrontendProductController::class, 'search'])->name('search');

            // List all published products
            Route::get('/', [FrontendProductController::class, 'index'])->name('index');

            // Get related products
            Route::get('{id}/related', [FrontendProductController::class, 'related'])
                ->where('id', '[0-9]+')
                ->name('related');

            // Get product reviews
            Route::get('{id}/reviews', [FrontendProductController::class, 'reviews'])
                ->where('id', '[0-9]+')
                ->name('reviews');

            // Get single product by slug
            Route::get('{slug}', [FrontendProductController::class, 'show'])
                ->where('slug', '[a-zA-Z0-9\-]+')
                ->name('show');
        });

    /*
    |--------------------------------------------------------------------------
    | Public Category Routes (Frontend)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['tenancy.token', 'tenant.context'])
        ->prefix('categories')
        ->name('categories.')
        ->group(function () {
            // List all categories
            Route::get('/', [FrontendProductController::class, 'categories'])->name('index');

            // Get products by category
            Route::get('{id}/products', [FrontendProductController::class, 'productsByCategory'])
                ->where('id', '[0-9]+')
                ->name('products');
        });

    /*
    |--------------------------------------------------------------------------
    | Cart Routes (Public with optional authentication)
    |--------------------------------------------------------------------------
    | Cart endpoints support both guest and authenticated users.
    | Guest carts use X-Cart-Token header for identification.
    */
    Route::middleware(['tenancy.token', 'tenant.context'])
        ->prefix('cart')
        ->name('cart.')
        ->group(function () {
            // Get current cart
            Route::get('/', [CartController::class, 'index'])->name('index');

            // Get cart summary
            Route::get('summary', [CartController::class, 'summary'])->name('summary');

            // Add item to cart
            Route::post('items', [CartController::class, 'addItem'])->name('items.add');

            // Update cart item quantity
            Route::put('items/{itemId}', [CartController::class, 'updateItem'])
                ->where('itemId', '[0-9]+')
                ->name('items.update');

            // Remove item from cart
            Route::delete('items/{itemId}', [CartController::class, 'removeItem'])
                ->where('itemId', '[0-9]+')
                ->name('items.remove');

            // Clear cart
            Route::delete('/', [CartController::class, 'clear'])->name('clear');

            // Apply coupon
            Route::post('coupon', [CartController::class, 'applyCoupon'])->name('coupon.apply');

            // Remove coupon
            Route::delete('coupon', [CartController::class, 'removeCoupon'])->name('coupon.remove');

            // Update addresses
            Route::put('addresses', [CartController::class, 'updateAddresses'])->name('addresses.update');
        });

    /*
    |--------------------------------------------------------------------------
    | Authenticated Cart Routes
    |--------------------------------------------------------------------------
    | Cart endpoints that require authentication.
    */
    Route::middleware(['auth:api_tenant_user', 'tenancy.token', 'tenant.context'])
        ->prefix('cart')
        ->name('cart.')
        ->group(function () {
            // Merge guest cart on login
            Route::post('merge', [CartController::class, 'mergeCart'])->name('merge');
        });

    /*
    |--------------------------------------------------------------------------
    | Checkout Routes (Public - with cart token)
    |--------------------------------------------------------------------------
    | Checkout endpoints for processing orders.
    | Supports both guest and authenticated users via cart token.
    */
    Route::middleware(['tenancy.token', 'tenant.context'])
        ->prefix('checkout')
        ->name('checkout.')
        ->group(function () {
            // Get available shipping methods
            Route::get('shipping-methods', [CheckoutController::class, 'getShippingMethods'])->name('shipping-methods');

            // Get available payment methods
            Route::get('payment-methods', [CheckoutController::class, 'getPaymentMethods'])->name('payment-methods');

            // Calculate order totals (shipping, tax, etc.)
            Route::post('calculate', [CheckoutController::class, 'calculate'])->name('calculate');

            // Process checkout and create order
            Route::post('/', [CheckoutController::class, 'checkout'])->name('process');

            // Payment webhook (no auth required)
            Route::post('webhook/{gateway}', [CheckoutController::class, 'webhook'])
                ->name('webhook')
                ->withoutMiddleware(['throttle:api']);

            // Verify payment status
            Route::get('verify/{orderNumber}', [CheckoutController::class, 'verifyPayment'])->name('verify');
        });

    /*
    |--------------------------------------------------------------------------
    | Authenticated Product Routes (Frontend)
    |--------------------------------------------------------------------------
    | Product endpoints that require authentication.
    */
    Route::middleware(['auth:api_tenant_user', 'tenancy.token', 'tenant.context'])
        ->prefix('products')
        ->name('products.')
        ->group(function () {
            // Submit product review
            Route::post('{id}/reviews', [FrontendProductController::class, 'storeReview'])
                ->where('id', '[0-9]+')
                ->name('reviews.store');
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Product Routes
    |--------------------------------------------------------------------------
    | Protected routes for product management.
    | Requires authentication, tenant context, active package, and ecommerce feature.
    */
    Route::middleware(['auth:api_tenant_admin', 'tenancy.token', 'tenant.context', 'package.active', 'feature:ecommerce'])
        ->prefix('admin/products')
        ->name('admin.products.')
        ->group(function () {
            // Bulk delete products
            Route::post('bulk-delete', [AdminProductController::class, 'bulkDelete'])->name('bulk-delete');

            // List all products
            Route::get('/', [AdminProductController::class, 'index'])->name('index');

            // Create a new product
            Route::post('/', [AdminProductController::class, 'store'])->name('store');

            // Get a specific product
            Route::get('{id}', [AdminProductController::class, 'show'])
                ->where('id', '[0-9]+')
                ->name('show');

            // Update a product
            Route::put('{id}', [AdminProductController::class, 'update'])
                ->where('id', '[0-9]+')
                ->name('update');

            // Delete a product
            Route::delete('{id}', [AdminProductController::class, 'destroy'])
                ->where('id', '[0-9]+')
                ->name('destroy');

            // Toggle product status
            Route::post('{id}/toggle-status', [AdminProductController::class, 'toggleStatus'])
                ->where('id', '[0-9]+')
                ->name('toggle-status');

            // Update stock
            Route::post('{id}/stock', [AdminProductController::class, 'updateStock'])
                ->where('id', '[0-9]+')
                ->name('stock.update');

            /*
            |--------------------------------------------------------------------------
            | Product Variant Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('{id}/variants')->name('variants.')->group(function () {
                // Add variant
                Route::post('/', [AdminProductController::class, 'addVariant'])->name('store');

                // Update variant
                Route::put('{variantId}', [AdminProductController::class, 'updateVariant'])
                    ->where('variantId', '[0-9]+')
                    ->name('update');

                // Delete variant
                Route::delete('{variantId}', [AdminProductController::class, 'deleteVariant'])
                    ->where('variantId', '[0-9]+')
                    ->name('destroy');
            });
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Order Routes
    |--------------------------------------------------------------------------
    | Protected routes for order management.
    */
    Route::middleware(['auth:api_tenant_admin', 'tenancy.token', 'tenant.context', 'package.active', 'feature:ecommerce'])
        ->prefix('admin/orders')
        ->name('admin.orders.')
        ->group(function () {
            // Get order statistics
            Route::get('statistics', [AdminOrderController::class, 'statistics'])->name('statistics');

            // List all orders
            Route::get('/', [AdminOrderController::class, 'index'])->name('index');

            // Get a specific order
            Route::get('{id}', [AdminOrderController::class, 'show'])
                ->where('id', '[0-9]+')
                ->name('show');

            // Update order status
            Route::put('{id}/status', [AdminOrderController::class, 'updateStatus'])
                ->where('id', '[0-9]+')
                ->name('status.update');

            // Update payment status
            Route::put('{id}/payment-status', [AdminOrderController::class, 'updatePaymentStatus'])
                ->where('id', '[0-9]+')
                ->name('payment-status.update');

            // Cancel order
            Route::post('{id}/cancel', [AdminOrderController::class, 'cancel'])
                ->where('id', '[0-9]+')
                ->name('cancel');
        });
});
