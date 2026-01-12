<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Event\Http\Controllers\Api\V1\Admin\EventController as AdminEventController;
use Modules\Event\Http\Controllers\Api\V1\Admin\EventCategoryController as AdminEventCategoryController;
use Modules\Event\Http\Controllers\Api\V1\Admin\EventCommentController as AdminEventCommentController;
use Modules\Event\Http\Controllers\Api\V1\Admin\EventPaymentLogController as AdminEventPaymentLogController;
use Modules\Event\Http\Controllers\Api\V1\Frontend\EventController as FrontendEventController;

/*
|--------------------------------------------------------------------------
| Event Module API Routes
|--------------------------------------------------------------------------
|
| API routes for the Event module with tenant context support.
|
| Middleware stack:
| - tenancy.token - Resolves and initializes tenant context
| - tenant.context - Ensures valid tenant context exists
| - auth:sanctum - Requires authentication (for authenticated routes)
| - package.active - Checks subscription is active (for admin routes)
| - feature:event - Checks if event feature is enabled (for admin routes)
*/

Route::prefix('v1/tenant/{tenant}')->name('api.v1.tenant.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Event Routes (Frontend)
    |--------------------------------------------------------------------------
    | Public endpoints for viewing events and categories.
    | Only tenant context required, no authentication.
    */
    Route::middleware(['tenancy.token', 'tenant.context'])
        ->prefix('events')
        ->name('events.')
        ->group(function () {
            // Get all active event categories
            Route::get('categories', [FrontendEventController::class, 'categories'])->name('categories');

            // Search events
            Route::get('search', [FrontendEventController::class, 'search'])->name('search');

            // Get upcoming events
            Route::get('upcoming', [FrontendEventController::class, 'upcoming'])->name('upcoming');

            // Get events by category
            Route::get('category/{categoryId}', [FrontendEventController::class, 'byCategory'])
                ->where('categoryId', '[0-9]+')
                ->name('by-category');

            // List all published events
            Route::get('/', [FrontendEventController::class, 'index'])->name('index');

            // Get comments for an event
            Route::get('{slug}/comments', [FrontendEventController::class, 'comments'])
                ->where('slug', '[a-zA-Z0-9\-]+')
                ->name('comments');

            // Get single event by slug (must be after specific routes)
            Route::get('{slug}', [FrontendEventController::class, 'show'])
                ->where('slug', '[a-zA-Z0-9\-]+')
                ->name('show');
        });

    // Get booking by ticket code (public for user verification)
    Route::middleware(['tenancy.token', 'tenant.context'])
        ->get('bookings/ticket/{code}', [FrontendEventController::class, 'getBooking'])
        ->name('bookings.ticket');

    /*
    |--------------------------------------------------------------------------
    | Authenticated Event Routes (Frontend)
    |--------------------------------------------------------------------------
    | Endpoints that require authentication for event interaction.
    */
    Route::middleware(['auth:sanctum', 'tenancy.token', 'tenant.context'])
        ->prefix('events')
        ->name('events.')
        ->group(function () {
            // Post a comment on an event
            Route::post('{slug}/comments', [FrontendEventController::class, 'storeComment'])
                ->where('slug', '[a-zA-Z0-9\-]+')
                ->name('comments.store');

            // Book event tickets (TEST MODE)
            Route::post('{slug}/book', [FrontendEventController::class, 'book'])
                ->where('slug', '[a-zA-Z0-9\-]+')
                ->name('book');
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Event Routes
    |--------------------------------------------------------------------------
    | Admin endpoints for managing events, categories, comments, and bookings.
    | Requires authentication, active subscription, and event feature enabled.
    */
    Route::middleware(['auth:sanctum', 'tenancy.token', 'tenant.context', 'package.active', 'feature:event'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            
            // Event Management
            Route::prefix('events')->name('events.')->group(function () {
                Route::get('/', [AdminEventController::class, 'index'])->name('index');
                Route::post('/', [AdminEventController::class, 'store'])->name('store');
                Route::get('{id}', [AdminEventController::class, 'show'])->where('id', '[0-9]+')->name('show');
                Route::put('{id}', [AdminEventController::class, 'update'])->where('id', '[0-9]+')->name('update');
                Route::delete('{id}', [AdminEventController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
                
                // Clone event
                Route::post('{id}/clone', [AdminEventController::class, 'clone'])->where('id', '[0-9]+')->name('clone');
                
                // Bulk actions
                Route::post('bulk', [AdminEventController::class, 'bulkAction'])->name('bulk');
                
                // Get event statistics
                Route::get('{id}/statistics', [AdminEventController::class, 'statistics'])->where('id', '[0-9]+')->name('statistics');
                
                // Event comments
                Route::get('{eventId}/comments', [AdminEventCommentController::class, 'index'])->where('eventId', '[0-9]+')->name('comments.index');
                Route::delete('{eventId}/comments', [AdminEventCommentController::class, 'bulkDelete'])->where('eventId', '[0-9]+')->name('comments.bulk-delete');
            });

            // Event Category Management
            Route::prefix('event-categories')->name('event-categories.')->group(function () {
                Route::get('/', [AdminEventCategoryController::class, 'index'])->name('index');
                Route::get('active', [AdminEventCategoryController::class, 'active'])->name('active');
                Route::post('/', [AdminEventCategoryController::class, 'store'])->name('store');
                Route::get('{id}', [AdminEventCategoryController::class, 'show'])->where('id', '[0-9]+')->name('show');
                Route::put('{id}', [AdminEventCategoryController::class, 'update'])->where('id', '[0-9]+')->name('update');
                Route::delete('{id}', [AdminEventCategoryController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
            });

            // Event Comment Management (standalone for all events)
            Route::prefix('event-comments')->name('event-comments.')->group(function () {
                Route::delete('{id}', [AdminEventCommentController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
            });

            // Event Booking/Payment Management
            Route::prefix('event-bookings')->name('event-bookings.')->group(function () {
                Route::get('/', [AdminEventPaymentLogController::class, 'index'])->name('index');
                Route::get('report', [AdminEventPaymentLogController::class, 'report'])->name('report');
                Route::get('ticket/{code}', [AdminEventPaymentLogController::class, 'findByTicketCode'])->name('ticket');
                Route::get('{id}', [AdminEventPaymentLogController::class, 'show'])->where('id', '[0-9]+')->name('show');
                Route::delete('{id}', [AdminEventPaymentLogController::class, 'destroy'])->where('id', '[0-9]+')->name('destroy');
                
                // Payment status update
                Route::patch('{id}/status', [AdminEventPaymentLogController::class, 'updateStatus'])->where('id', '[0-9]+')->name('update-status');
                
                // Check-in management
                Route::post('{id}/check-in', [AdminEventPaymentLogController::class, 'checkIn'])->where('id', '[0-9]+')->name('check-in');
                Route::delete('{id}/check-in', [AdminEventPaymentLogController::class, 'undoCheckIn'])->where('id', '[0-9]+')->name('undo-check-in');
            });
        });
});
