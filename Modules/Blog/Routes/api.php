<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Blog\Http\Controllers\Api\V1\Admin\BlogController as AdminBlogController;
use Modules\Blog\Http\Controllers\Api\V1\Admin\BlogCategoryController as AdminBlogCategoryController;
use Modules\Blog\Http\Controllers\Api\V1\Frontend\BlogController as FrontendBlogController;

/*
|--------------------------------------------------------------------------
| Blog Module API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the Blog module. These routes are loaded
| by the RouteServiceProvider within a group which is assigned the "api"
| middleware group.
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
| - feature:blog - Checks if blog feature is allowed by plan
*/

Route::prefix('v1/tenant/{tenant}')->name('api.v1.tenant.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Blog Routes (Frontend)
    |--------------------------------------------------------------------------
    | Public endpoints for viewing blog content.
    | Only tenant context required, no authentication.
    */
    Route::middleware(['tenancy.token', 'tenant.context'])
        ->prefix('blog')
        ->name('blog.')
        ->group(function () {
            // Get all categories (for sidebar/navigation)
            Route::get('categories', [FrontendBlogController::class, 'categories'])->name('categories');

            // Search blog posts
            Route::get('search', [FrontendBlogController::class, 'search'])->name('search');

            // Get recent posts
            Route::get('recent', [FrontendBlogController::class, 'recent'])->name('recent');

            // Get popular posts
            Route::get('popular', [FrontendBlogController::class, 'popular'])->name('popular');

            // Get posts by category
            Route::get('category/{slug}', [FrontendBlogController::class, 'byCategory'])->name('by-category');

            // Get posts by tag
            Route::get('tag/{tag}', [FrontendBlogController::class, 'byTag'])->name('by-tag');

            // List all published blog posts
            Route::get('/', [FrontendBlogController::class, 'index'])->name('index');

            // Get comments for a blog post
            Route::get('{postId}/comments', [FrontendBlogController::class, 'comments'])
                ->where('postId', '[0-9]+')
                ->name('comments');

            // Get single blog post by slug (must be last to avoid conflicts)
            Route::get('{slug}', [FrontendBlogController::class, 'show'])
                ->where('slug', '[a-zA-Z0-9\-]+')
                ->name('show');
        });

    /*
    |--------------------------------------------------------------------------
    | Authenticated Blog Routes (Frontend)
    |--------------------------------------------------------------------------
    | Endpoints that require authentication for blog interaction.
    */
    Route::middleware(['auth:sanctum', 'tenancy.token', 'tenant.context'])
        ->prefix('blog')
        ->name('blog.')
        ->group(function () {
            // Store a comment on a blog post
            Route::post('{postId}/comments', [FrontendBlogController::class, 'storeComment'])
                ->where('postId', '[0-9]+')
                ->name('comments.store');
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Blog Routes
    |--------------------------------------------------------------------------
    | Protected routes for blog management.
    | Requires authentication, tenant context, active package, and blog feature.
    */
    Route::middleware(['auth:sanctum', 'tenancy.token', 'tenant.context', 'package.active', 'feature:blog'])
        ->prefix('admin/blog')
        ->name('admin.blog.')
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Blog Post Management Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('posts')->name('posts.')->group(function () {
                // Bulk action on blog posts
                Route::post('bulk-action', [AdminBlogController::class, 'bulkAction'])->name('bulk-action');

                // List all blog posts
                Route::get('/', [AdminBlogController::class, 'index'])->name('index');

                // Create a new blog post
                Route::post('/', [AdminBlogController::class, 'store'])->name('store');

                // Get a specific blog post
                Route::get('{id}', [AdminBlogController::class, 'show'])->name('show');

                // Update a blog post
                Route::put('{id}', [AdminBlogController::class, 'update'])->name('update');

                // Delete a blog post
                Route::delete('{id}', [AdminBlogController::class, 'destroy'])->name('destroy');

                // Toggle blog post status
                Route::patch('{id}/toggle-status', [AdminBlogController::class, 'toggleStatus'])->name('toggle-status');
            });

            /*
            |--------------------------------------------------------------------------
            | Blog Category Management Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('categories')->name('categories.')->group(function () {
                // List all categories
                Route::get('/', [AdminBlogCategoryController::class, 'index'])->name('index');

                // Create a new category
                Route::post('/', [AdminBlogCategoryController::class, 'store'])->name('store');

                // Get a specific category
                Route::get('{id}', [AdminBlogCategoryController::class, 'show'])->name('show');

                // Update a category
                Route::put('{id}', [AdminBlogCategoryController::class, 'update'])->name('update');

                // Delete a category
                Route::delete('{id}', [AdminBlogCategoryController::class, 'destroy'])->name('destroy');

                // Toggle category status
                Route::patch('{id}/toggle-status', [AdminBlogCategoryController::class, 'toggleStatus'])->name('toggle-status');
            });
        });
});
