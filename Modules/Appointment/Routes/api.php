<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Appointment\Http\Controllers\Api\V1\Admin\AppointmentController as AdminAppointmentController;
use Modules\Appointment\Http\Controllers\Api\V1\Admin\CategoryController as AdminCategoryController;
use Modules\Appointment\Http\Controllers\Api\V1\Admin\SubAppointmentController as AdminSubAppointmentController;
use Modules\Appointment\Http\Controllers\Api\V1\Admin\ScheduleController as AdminScheduleController;
use Modules\Appointment\Http\Controllers\Api\V1\Admin\BookingController as AdminBookingController;
use Modules\Appointment\Http\Controllers\Api\V1\Frontend\AppointmentController as FrontendAppointmentController;
use Modules\Appointment\Http\Controllers\Api\V1\Frontend\BookingController as FrontendBookingController;

/*
|--------------------------------------------------------------------------
| Appointment Module API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the Appointment module. These routes are loaded
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
| - feature:appointment - Checks if appointment feature is allowed by plan
*/

Route::prefix('v1/tenant/{tenant}')->name('api.v1.tenant.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Appointment Routes (Frontend)
    |--------------------------------------------------------------------------
    | Public endpoints for browsing appointments.
    | Only tenant context required, no authentication.
    */
    Route::middleware(['tenancy.token', 'tenant.context'])
        ->prefix('appointments')
        ->name('appointments.')
        ->group(function () {
            // Get all categories (for sidebar/navigation)
            Route::get('categories', [FrontendAppointmentController::class, 'categories'])->name('categories');

            // Get featured/popular appointments
            Route::get('featured', [FrontendAppointmentController::class, 'featured'])->name('featured');

            // Search appointments
            Route::get('search', [FrontendAppointmentController::class, 'search'])->name('search');

            // Get subcategories for a category
            Route::get('categories/{categoryId}/subcategories', [FrontendAppointmentController::class, 'subcategories'])
                ->where('categoryId', '[0-9]+')
                ->name('categories.subcategories');

            // Get appointments by category
            Route::get('category/{categoryId}', [FrontendAppointmentController::class, 'byCategory'])
                ->where('categoryId', '[0-9]+')
                ->name('by-category');

            // Get available slots for an appointment on a specific date
            Route::get('{appointmentId}/available-slots', [FrontendBookingController::class, 'availableSlots'])
                ->where('appointmentId', '[0-9]+')
                ->name('available-slots');

            // Get availability calendar for a date range
            Route::get('{appointmentId}/availability', [FrontendBookingController::class, 'availability'])
                ->where('appointmentId', '[0-9]+')
                ->name('availability');

            // Initialize booking (calculate pricing)
            Route::post('{appointmentId}/booking/init', [FrontendBookingController::class, 'init'])
                ->where('appointmentId', '[0-9]+')
                ->name('booking.init');

            // Create a booking
            Route::post('{appointmentId}/booking', [FrontendBookingController::class, 'store'])
                ->where('appointmentId', '[0-9]+')
                ->name('booking.store');

            // Get related appointments
            Route::get('{id}/related', [FrontendAppointmentController::class, 'related'])
                ->where('id', '[0-9]+')
                ->name('related');

            // Get booking by transaction ID (for payment callbacks)
            Route::get('booking/transaction/{transactionId}', [FrontendBookingController::class, 'showByTransaction'])
                ->name('booking.by-transaction');

            // Payment callback webhook
            Route::post('booking/payment-callback', [FrontendBookingController::class, 'paymentCallback'])
                ->name('booking.payment-callback');

            // List all active appointments
            Route::get('/', [FrontendAppointmentController::class, 'index'])->name('index');

            // Get single appointment by ID or slug (must be last to avoid conflicts)
            Route::get('{identifier}', [FrontendAppointmentController::class, 'show'])
                ->where('identifier', '[a-zA-Z0-9\-]+')
                ->name('show');
        });

    /*
    |--------------------------------------------------------------------------
    | Authenticated Appointment Routes (Frontend - User Bookings)
    |--------------------------------------------------------------------------
    | Endpoints that require authentication for user booking management.
    */
    Route::middleware(['auth:sanctum', 'tenancy.token', 'tenant.context'])
        ->prefix('appointments')
        ->name('appointments.')
        ->group(function () {
            // Get user's bookings
            Route::get('my-bookings', [FrontendBookingController::class, 'myBookings'])
                ->name('my-bookings');

            // Get a specific booking
            Route::get('my-bookings/{id}', [FrontendBookingController::class, 'showMyBooking'])
                ->where('id', '[0-9]+')
                ->name('my-bookings.show');

            // Cancel user's booking
            Route::post('my-bookings/{id}/cancel', [FrontendBookingController::class, 'cancelMyBooking'])
                ->where('id', '[0-9]+')
                ->name('my-bookings.cancel');

            // Reschedule user's booking
            Route::post('my-bookings/{id}/reschedule', [FrontendBookingController::class, 'rescheduleMyBooking'])
                ->where('id', '[0-9]+')
                ->name('my-bookings.reschedule');
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Appointment Routes
    |--------------------------------------------------------------------------
    | Protected routes for appointment management.
    | Requires authentication, tenant context, active package, and appointment feature.
    */
    Route::middleware(['auth:sanctum', 'tenancy.token', 'tenant.context', 'package.active', 'feature:appointment'])
        ->prefix('admin/appointments')
        ->name('admin.appointments.')
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Appointment Management Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('services')->name('services.')->group(function () {
                // Get package limit info
                Route::get('package-limit', [AdminAppointmentController::class, 'packageLimit'])->name('package-limit');

                // Bulk delete appointments
                Route::post('bulk-delete', [AdminAppointmentController::class, 'bulkDelete'])->name('bulk-delete');

                // Clone an appointment
                Route::post('{id}/clone', [AdminAppointmentController::class, 'clone'])
                    ->where('id', '[0-9]+')
                    ->name('clone');

                // Toggle appointment status
                Route::patch('{id}/toggle-status', [AdminAppointmentController::class, 'toggleStatus'])
                    ->where('id', '[0-9]+')
                    ->name('toggle-status');

                // List all appointments
                Route::get('/', [AdminAppointmentController::class, 'index'])->name('index');

                // Create a new appointment
                Route::post('/', [AdminAppointmentController::class, 'store'])->name('store');

                // Get a specific appointment
                Route::get('{id}', [AdminAppointmentController::class, 'show'])
                    ->where('id', '[0-9]+')
                    ->name('show');

                // Update an appointment
                Route::put('{id}', [AdminAppointmentController::class, 'update'])
                    ->where('id', '[0-9]+')
                    ->name('update');

                // Delete an appointment
                Route::delete('{id}', [AdminAppointmentController::class, 'destroy'])
                    ->where('id', '[0-9]+')
                    ->name('destroy');
            });

            /*
            |--------------------------------------------------------------------------
            | Category Management Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('categories')->name('categories.')->group(function () {
                // List all categories
                Route::get('/', [AdminCategoryController::class, 'index'])->name('index');

                // Create a new category
                Route::post('/', [AdminCategoryController::class, 'store'])->name('store');

                // Get a specific category
                Route::get('{id}', [AdminCategoryController::class, 'show'])
                    ->where('id', '[0-9]+')
                    ->name('show');

                // Update a category
                Route::put('{id}', [AdminCategoryController::class, 'update'])
                    ->where('id', '[0-9]+')
                    ->name('update');

                // Delete a category
                Route::delete('{id}', [AdminCategoryController::class, 'destroy'])
                    ->where('id', '[0-9]+')
                    ->name('destroy');

                // Toggle category status
                Route::patch('{id}/toggle-status', [AdminCategoryController::class, 'toggleStatus'])
                    ->where('id', '[0-9]+')
                    ->name('toggle-status');
            });

            /*
            |--------------------------------------------------------------------------
            | Subcategory Management Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('subcategories')->name('subcategories.')->group(function () {
                // List all subcategories
                Route::get('/', [AdminCategoryController::class, 'subcategoriesIndex'])->name('index');

                // Create a new subcategory
                Route::post('/', [AdminCategoryController::class, 'storeSubcategory'])->name('store');

                // Get a specific subcategory
                Route::get('{id}', [AdminCategoryController::class, 'showSubcategory'])
                    ->where('id', '[0-9]+')
                    ->name('show');

                // Update a subcategory
                Route::put('{id}', [AdminCategoryController::class, 'updateSubcategory'])
                    ->where('id', '[0-9]+')
                    ->name('update');

                // Delete a subcategory
                Route::delete('{id}', [AdminCategoryController::class, 'destroySubcategory'])
                    ->where('id', '[0-9]+')
                    ->name('destroy');

                // Toggle subcategory status
                Route::patch('{id}/toggle-status', [AdminCategoryController::class, 'toggleSubcategoryStatus'])
                    ->where('id', '[0-9]+')
                    ->name('toggle-status');
            });

            /*
            |--------------------------------------------------------------------------
            | Sub-Appointment Management Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('sub-appointments')->name('sub-appointments.')->group(function () {
                // Bulk delete sub-appointments
                Route::post('bulk-delete', [AdminSubAppointmentController::class, 'bulkDelete'])->name('bulk-delete');

                // List all sub-appointments
                Route::get('/', [AdminSubAppointmentController::class, 'index'])->name('index');

                // Create a new sub-appointment
                Route::post('/', [AdminSubAppointmentController::class, 'store'])->name('store');

                // Get a specific sub-appointment
                Route::get('{id}', [AdminSubAppointmentController::class, 'show'])
                    ->where('id', '[0-9]+')
                    ->name('show');

                // Update a sub-appointment
                Route::put('{id}', [AdminSubAppointmentController::class, 'update'])
                    ->where('id', '[0-9]+')
                    ->name('update');

                // Delete a sub-appointment
                Route::delete('{id}', [AdminSubAppointmentController::class, 'destroy'])
                    ->where('id', '[0-9]+')
                    ->name('destroy');

                // Toggle sub-appointment status
                Route::patch('{id}/toggle-status', [AdminSubAppointmentController::class, 'toggleStatus'])
                    ->where('id', '[0-9]+')
                    ->name('toggle-status');
            });

            /*
            |--------------------------------------------------------------------------
            | Schedule Management Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('schedules')->name('schedules.')->group(function () {
                // Get days for an appointment
                Route::get('days', [AdminScheduleController::class, 'getDays'])->name('days');

                // Manage days
                Route::post('days', [AdminScheduleController::class, 'storeDays'])->name('days.store');
                Route::put('days/{id}', [AdminScheduleController::class, 'updateDay'])
                    ->where('id', '[0-9]+')
                    ->name('days.update');
                Route::delete('days/{id}', [AdminScheduleController::class, 'deleteDay'])
                    ->where('id', '[0-9]+')
                    ->name('days.destroy');

                // Get day types for a day
                Route::get('day-types', [AdminScheduleController::class, 'getDayTypes'])->name('day-types');

                // Manage day types
                Route::post('day-types', [AdminScheduleController::class, 'storeDayType'])->name('day-types.store');
                Route::put('day-types/{id}', [AdminScheduleController::class, 'updateDayType'])
                    ->where('id', '[0-9]+')
                    ->name('day-types.update');
                Route::delete('day-types/{id}', [AdminScheduleController::class, 'deleteDayType'])
                    ->where('id', '[0-9]+')
                    ->name('day-types.destroy');

                // Bulk create time slots
                Route::post('bulk', [AdminScheduleController::class, 'bulkStore'])->name('bulk');

                // Get availability for date
                Route::get('availability', [AdminScheduleController::class, 'getAvailability'])->name('availability');

                // Block/Unblock time slot
                Route::patch('{id}/block', [AdminScheduleController::class, 'blockSlot'])
                    ->where('id', '[0-9]+')
                    ->name('block');
                Route::patch('{id}/unblock', [AdminScheduleController::class, 'unblockSlot'])
                    ->where('id', '[0-9]+')
                    ->name('unblock');

                // CRUD for time slots
                Route::get('/', [AdminScheduleController::class, 'index'])->name('index');
                Route::post('/', [AdminScheduleController::class, 'store'])->name('store');
                Route::get('{id}', [AdminScheduleController::class, 'show'])
                    ->where('id', '[0-9]+')
                    ->name('show');
                Route::put('{id}', [AdminScheduleController::class, 'update'])
                    ->where('id', '[0-9]+')
                    ->name('update');
                Route::delete('{id}', [AdminScheduleController::class, 'destroy'])
                    ->where('id', '[0-9]+')
                    ->name('destroy');
            });

            /*
            |--------------------------------------------------------------------------
            | Booking Management Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('bookings')->name('bookings.')->group(function () {
                // Get booking statistics
                Route::get('stats', [AdminBookingController::class, 'stats'])->name('stats');

                // Bulk update status
                Route::post('bulk-status', [AdminBookingController::class, 'bulkStatus'])->name('bulk-status');

                // Confirm a booking
                Route::post('{id}/confirm', [AdminBookingController::class, 'confirm'])
                    ->where('id', '[0-9]+')
                    ->name('confirm');

                // Complete a booking
                Route::post('{id}/complete', [AdminBookingController::class, 'complete'])
                    ->where('id', '[0-9]+')
                    ->name('complete');

                // Cancel a booking
                Route::post('{id}/cancel', [AdminBookingController::class, 'cancel'])
                    ->where('id', '[0-9]+')
                    ->name('cancel');

                // Reschedule a booking
                Route::post('{id}/reschedule', [AdminBookingController::class, 'reschedule'])
                    ->where('id', '[0-9]+')
                    ->name('reschedule');

                // Update booking status
                Route::put('{id}/status', [AdminBookingController::class, 'updateStatus'])
                    ->where('id', '[0-9]+')
                    ->name('status');

                // Approve manual payment
                Route::post('{id}/approve-payment', [AdminBookingController::class, 'approvePayment'])
                    ->where('id', '[0-9]+')
                    ->name('approve-payment');

                // CRUD
                Route::get('/', [AdminBookingController::class, 'index'])->name('index');
                Route::get('{id}', [AdminBookingController::class, 'show'])
                    ->where('id', '[0-9]+')
                    ->name('show');
                Route::delete('{id}', [AdminBookingController::class, 'destroy'])
                    ->where('id', '[0-9]+')
                    ->name('destroy');
            });
        });
});
