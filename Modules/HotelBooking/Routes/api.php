<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

// Admin Controllers
use Modules\HotelBooking\Http\Controllers\Admin\HotelController as AdminHotelController;
use Modules\HotelBooking\Http\Controllers\Admin\RoomTypeController as AdminRoomTypeController;
use Modules\HotelBooking\Http\Controllers\Admin\RoomController as AdminRoomController;
use Modules\HotelBooking\Http\Controllers\Admin\BookingController as AdminBookingController;
use Modules\HotelBooking\Http\Controllers\Admin\InventoryController as AdminInventoryController;
use Modules\HotelBooking\Http\Controllers\Admin\AmenityController as AdminAmenityController;
use Modules\HotelBooking\Http\Controllers\Admin\CancellationPolicyController as AdminCancellationPolicyController;

// Frontend Controllers
use Modules\HotelBooking\Http\Controllers\Frontend\HotelController as FrontendHotelController;
use Modules\HotelBooking\Http\Controllers\Frontend\RoomController as FrontendRoomController;
use Modules\HotelBooking\Http\Controllers\Frontend\BookingController as FrontendBookingController;
use Modules\HotelBooking\Http\Controllers\Frontend\ReviewController as FrontendReviewController;

/*
|--------------------------------------------------------------------------
| Hotel Booking Module API Routes
|--------------------------------------------------------------------------
|
| Here are the API routes for the Hotel Booking module. These routes
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
| - feature:hotel-booking - Checks if hotel booking feature is allowed by plan
*/

Route::prefix('v1/tenant/{tenant}')->name('api.v1.tenant.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Hotel Routes (Frontend)
    |--------------------------------------------------------------------------
    | Public endpoints for browsing hotels and rooms.
    | Only tenant context required, no authentication.
    */
    Route::middleware(['tenancy.token', 'tenant.context'])
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Hotel Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('hotels')
                ->name('hotels.')
                ->group(function () {
                    // Get popular destinations
                    Route::get('popular-destinations', [FrontendHotelController::class, 'popularDestinations'])
                        ->name('popular-destinations');

                    // Get search suggestions
                    Route::get('suggestions', [FrontendHotelController::class, 'suggestions'])
                        ->name('suggestions');

                    // Search hotels with availability
                    Route::get('search', [FrontendHotelController::class, 'search'])
                        ->name('search');

                    // List all published hotels
                    Route::get('/', [FrontendHotelController::class, 'index'])
                        ->name('index');

                    // Get available rooms for a hotel
                    Route::get('{slug}/rooms', [FrontendHotelController::class, 'availableRooms'])
                        ->name('rooms');

                    // Get hotel reviews
                    Route::get('{hotelId}/reviews', [FrontendReviewController::class, 'index'])
                        ->where('hotelId', '[0-9]+')
                        ->name('reviews');

                    // Get single hotel by slug
                    Route::get('{slug}', [FrontendHotelController::class, 'show'])
                        ->name('show');
                });

            /*
            |--------------------------------------------------------------------------
            | Room Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('rooms')
                ->name('rooms.')
                ->group(function () {
                    // Get available meal plans
                    Route::get('meal-plans', [FrontendRoomController::class, 'mealPlans'])
                        ->name('meal-plans');

                    // Get available extras/add-ons
                    Route::get('extras', [FrontendRoomController::class, 'extras'])
                        ->name('extras');

                    // Search available rooms
                    Route::get('search', [FrontendRoomController::class, 'search'])
                        ->name('search');

                    // Check room availability
                    Route::post('check-availability', [FrontendRoomController::class, 'checkAvailability'])
                        ->name('check-availability');

                    // Get room type details
                    Route::get('{id}', [FrontendRoomController::class, 'show'])
                        ->where('id', '[0-9]+')
                        ->name('show');

                    // Get room availability calendar
                    Route::get('{id}/availability', [FrontendRoomController::class, 'availability'])
                        ->where('id', '[0-9]+')
                        ->name('availability');

                    // Calculate room price
                    Route::get('{id}/price', [FrontendRoomController::class, 'calculatePrice'])
                        ->where('id', '[0-9]+')
                        ->name('price');
                });

            /*
            |--------------------------------------------------------------------------
            | Booking Routes (Public - with hold token)
            |--------------------------------------------------------------------------
            */
            Route::prefix('bookings')
                ->name('bookings.')
                ->group(function () {
                    // Get available payment methods
                    Route::get('payment-methods', [FrontendBookingController::class, 'paymentMethods'])
                        ->name('payment-methods');

                    // Calculate booking price
                    Route::post('calculate', [FrontendBookingController::class, 'calculate'])
                        ->name('calculate');

                    // Initialize booking (create room holds)
                    Route::post('init', [FrontendBookingController::class, 'init'])
                        ->name('init');

                    // Get hold status
                    Route::get('hold/{token}', [FrontendBookingController::class, 'holdStatus'])
                        ->name('hold.status');

                    // Extend hold
                    Route::post('hold/{token}/extend', [FrontendBookingController::class, 'extendHold'])
                        ->name('hold.extend');

                    // Release hold
                    Route::delete('hold/{token}', [FrontendBookingController::class, 'releaseHold'])
                        ->name('hold.release');

                    // Create booking from hold
                    Route::post('/', [FrontendBookingController::class, 'store'])
                        ->name('store');

                    // Payment webhook (no auth required)
                    Route::post('webhook/{gateway}', [FrontendBookingController::class, 'webhook'])
                        ->name('webhook')
                        ->withoutMiddleware(['throttle:api']);

                    // Get booking by code (for guests)
                    Route::get('{code}', [FrontendBookingController::class, 'show'])
                        ->name('show');

                    // Process payment
                    Route::post('{code}/pay', [FrontendBookingController::class, 'pay'])
                        ->name('pay');

                    // Cancel booking
                    Route::post('{code}/cancel', [FrontendBookingController::class, 'cancel'])
                        ->name('cancel');
                });
        });

    /*
    |--------------------------------------------------------------------------
    | Authenticated User Routes (Frontend)
    |--------------------------------------------------------------------------
    | Routes that require user authentication.
    */
    Route::middleware(['auth:api_tenant_user', 'tenancy.token', 'tenant.context'])
        ->group(function () {

            // User's bookings
            Route::prefix('bookings')
                ->name('bookings.')
                ->group(function () {
                    // Get my bookings
                    Route::get('my-bookings', [FrontendBookingController::class, 'myBookings'])
                        ->name('my-bookings');

                    // Get upcoming bookings
                    Route::get('upcoming', [FrontendBookingController::class, 'upcoming'])
                        ->name('upcoming');
                });

            // User's reviews
            Route::prefix('reviews')
                ->name('reviews.')
                ->group(function () {
                    // Get my reviews
                    Route::get('my-reviews', [FrontendReviewController::class, 'myReviews'])
                        ->name('my-reviews');

                    // Update my review
                    Route::put('{id}', [FrontendReviewController::class, 'update'])
                        ->where('id', '[0-9]+')
                        ->name('update');

                    // Delete my review
                    Route::delete('{id}', [FrontendReviewController::class, 'destroy'])
                        ->where('id', '[0-9]+')
                        ->name('destroy');
                });

            // Hotel reviews (authenticated)
            Route::prefix('hotels/{hotelId}/reviews')
                ->name('hotels.reviews.')
                ->group(function () {
                    // Check if can review
                    Route::get('can-review', [FrontendReviewController::class, 'canReview'])
                        ->name('can-review');

                    // Submit review
                    Route::post('/', [FrontendReviewController::class, 'store'])
                        ->name('store');
                });
        });

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    | Protected routes for hotel booking management.
    | Requires authentication, tenant context, active package, and hotel-booking feature.
    */
    Route::middleware(['auth:api_tenant_admin', 'tenancy.token', 'tenant.context', 'package.active', 'feature:hotel-booking'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            /*
            |--------------------------------------------------------------------------
            | Admin Hotel Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('hotels')
                ->name('hotels.')
                ->group(function () {
                    // List all hotels
                    Route::get('/', [AdminHotelController::class, 'index'])
                        ->name('index');

                    // Create a new hotel
                    Route::post('/', [AdminHotelController::class, 'store'])
                        ->name('store');

                    // Get a specific hotel
                    Route::get('{id}', [AdminHotelController::class, 'show'])
                        ->where('id', '[0-9]+')
                        ->name('show');

                    // Update a hotel
                    Route::put('{id}', [AdminHotelController::class, 'update'])
                        ->where('id', '[0-9]+')
                        ->name('update');

                    // Delete a hotel
                    Route::delete('{id}', [AdminHotelController::class, 'destroy'])
                        ->where('id', '[0-9]+')
                        ->name('destroy');

                    // Toggle hotel status
                    Route::post('{id}/toggle-status', [AdminHotelController::class, 'toggleStatus'])
                        ->where('id', '[0-9]+')
                        ->name('toggle-status');

                    // Sync hotel images
                    Route::post('{id}/sync-images', [AdminHotelController::class, 'syncImages'])
                        ->where('id', '[0-9]+')
                        ->name('sync-images');
                });

            /*
            |--------------------------------------------------------------------------
            | Admin Room Type Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('hotels/{hotelId}/room-types')
                ->name('room-types.')
                ->group(function () {
                    // List room types for hotel
                    Route::get('/', [AdminRoomTypeController::class, 'index'])
                        ->name('index');

                    // Create room type
                    Route::post('/', [AdminRoomTypeController::class, 'store'])
                        ->name('store');

                    // Get room type
                    Route::get('{id}', [AdminRoomTypeController::class, 'show'])
                        ->where('id', '[0-9]+')
                        ->name('show');

                    // Update room type
                    Route::put('{id}', [AdminRoomTypeController::class, 'update'])
                        ->where('id', '[0-9]+')
                        ->name('update');

                    // Delete room type
                    Route::delete('{id}', [AdminRoomTypeController::class, 'destroy'])
                        ->where('id', '[0-9]+')
                        ->name('destroy');
                });

            /*
            |--------------------------------------------------------------------------
            | Admin Room Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('room-types/{roomTypeId}/rooms')
                ->name('rooms.')
                ->group(function () {
                    // List rooms for room type
                    Route::get('/', [AdminRoomController::class, 'index'])
                        ->name('index');

                    // Create room
                    Route::post('/', [AdminRoomController::class, 'store'])
                        ->name('store');

                    // Get room
                    Route::get('{id}', [AdminRoomController::class, 'show'])
                        ->where('id', '[0-9]+')
                        ->name('show');

                    // Update room
                    Route::put('{id}', [AdminRoomController::class, 'update'])
                        ->where('id', '[0-9]+')
                        ->name('update');

                    // Delete room
                    Route::delete('{id}', [AdminRoomController::class, 'destroy'])
                        ->where('id', '[0-9]+')
                        ->name('destroy');

                    // Toggle room status
                    Route::post('{id}/toggle-status', [AdminRoomController::class, 'toggleStatus'])
                        ->where('id', '[0-9]+')
                        ->name('toggle-status');

                    // Block room
                    Route::post('{id}/block', [AdminRoomController::class, 'block'])
                        ->where('id', '[0-9]+')
                        ->name('block');

                    // Unblock room
                    Route::post('{id}/unblock', [AdminRoomController::class, 'unblock'])
                        ->where('id', '[0-9]+')
                        ->name('unblock');

                    // Get booked dates
                    Route::get('{id}/booked-dates', [AdminRoomController::class, 'bookedDates'])
                        ->where('id', '[0-9]+')
                        ->name('booked-dates');
                });

            /*
            |--------------------------------------------------------------------------
            | Admin Booking Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('bookings')
                ->name('bookings.')
                ->group(function () {
                    // Get booking statistics
                    Route::get('statistics', [AdminBookingController::class, 'statistics'])
                        ->name('statistics');

                    // Get today's arrivals
                    Route::get('today-arrivals', [AdminBookingController::class, 'todayArrivals'])
                        ->name('today-arrivals');

                    // Get today's departures
                    Route::get('today-departures', [AdminBookingController::class, 'todayDepartures'])
                        ->name('today-departures');

                    // Get in-house guests
                    Route::get('in-house', [AdminBookingController::class, 'inHouseGuests'])
                        ->name('in-house');

                    // List all bookings
                    Route::get('/', [AdminBookingController::class, 'index'])
                        ->name('index');

                    // Get a specific booking
                    Route::get('{id}', [AdminBookingController::class, 'show'])
                        ->where('id', '[0-9]+')
                        ->name('show');

                    // Update booking status
                    Route::put('{id}/status', [AdminBookingController::class, 'updateStatus'])
                        ->where('id', '[0-9]+')
                        ->name('status.update');

                    // Confirm booking
                    Route::post('{id}/confirm', [AdminBookingController::class, 'confirm'])
                        ->where('id', '[0-9]+')
                        ->name('confirm');

                    // Check-in guest
                    Route::post('{id}/check-in', [AdminBookingController::class, 'checkIn'])
                        ->where('id', '[0-9]+')
                        ->name('check-in');

                    // Check-out guest
                    Route::post('{id}/check-out', [AdminBookingController::class, 'checkOut'])
                        ->where('id', '[0-9]+')
                        ->name('check-out');

                    // Cancel booking
                    Route::post('{id}/cancel', [AdminBookingController::class, 'cancel'])
                        ->where('id', '[0-9]+')
                        ->name('cancel');

                    // Mark as no-show
                    Route::post('{id}/no-show', [AdminBookingController::class, 'markNoShow'])
                        ->where('id', '[0-9]+')
                        ->name('no-show');

                    // Check refund eligibility
                    Route::get('{id}/refund-eligibility', [AdminBookingController::class, 'refundEligibility'])
                        ->where('id', '[0-9]+')
                        ->name('refund-eligibility');

                    // Process refund
                    Route::post('{id}/refund', [AdminBookingController::class, 'processRefund'])
                        ->where('id', '[0-9]+')
                        ->name('refund');
                });

            /*
            |--------------------------------------------------------------------------
            | Admin Inventory Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('inventory')
                ->name('inventory.')
                ->group(function () {
                    // Get inventory statistics
                    Route::get('statistics', [AdminInventoryController::class, 'statistics'])
                        ->name('statistics');

                    // Get calendar view
                    Route::get('calendar', [AdminInventoryController::class, 'calendar'])
                        ->name('calendar');

                    // Initialize inventory for room type
                    Route::post('initialize', [AdminInventoryController::class, 'initialize'])
                        ->name('initialize');

                    // Sync inventory with room count
                    Route::post('sync', [AdminInventoryController::class, 'sync'])
                        ->name('sync');

                    // Block dates
                    Route::post('block', [AdminInventoryController::class, 'blockDates'])
                        ->name('block');

                    // Unblock dates
                    Route::post('unblock', [AdminInventoryController::class, 'unblockDates'])
                        ->name('unblock');

                    // Set seasonal pricing
                    Route::post('seasonal-pricing', [AdminInventoryController::class, 'setSeasonalPricing'])
                        ->name('seasonal-pricing');

                    // Bulk update inventory
                    Route::post('bulk', [AdminInventoryController::class, 'bulkUpdate'])
                        ->name('bulk');

                    // Get inventory for room type
                    Route::get('{roomTypeId}', [AdminInventoryController::class, 'index'])
                        ->where('roomTypeId', '[0-9]+')
                        ->name('index');

                    // Update inventory for specific date
                    Route::put('{roomTypeId}/{date}', [AdminInventoryController::class, 'update'])
                        ->where('roomTypeId', '[0-9]+')
                        ->name('update');
                });

            /*
            |--------------------------------------------------------------------------
            | Admin Amenity Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('amenities')
                ->name('amenities.')
                ->group(function () {
                    // List all amenities
                    Route::get('/', [AdminAmenityController::class, 'index'])
                        ->name('index');

                    // Create amenity
                    Route::post('/', [AdminAmenityController::class, 'store'])
                        ->name('store');

                    // Get amenity
                    Route::get('{id}', [AdminAmenityController::class, 'show'])
                        ->where('id', '[0-9]+')
                        ->name('show');

                    // Update amenity
                    Route::put('{id}', [AdminAmenityController::class, 'update'])
                        ->where('id', '[0-9]+')
                        ->name('update');

                    // Delete amenity
                    Route::delete('{id}', [AdminAmenityController::class, 'destroy'])
                        ->where('id', '[0-9]+')
                        ->name('destroy');

                    // Toggle amenity status
                    Route::post('{id}/toggle-status', [AdminAmenityController::class, 'toggleStatus'])
                        ->where('id', '[0-9]+')
                        ->name('toggle-status');
                });

            /*
            |--------------------------------------------------------------------------
            | Admin Cancellation Policy Routes
            |--------------------------------------------------------------------------
            */
            Route::prefix('cancellation-policies')
                ->name('cancellation-policies.')
                ->group(function () {
                    // Get active policies
                    Route::get('active', [AdminCancellationPolicyController::class, 'active'])
                        ->name('active');

                    // List all policies
                    Route::get('/', [AdminCancellationPolicyController::class, 'index'])
                        ->name('index');

                    // Create policy
                    Route::post('/', [AdminCancellationPolicyController::class, 'store'])
                        ->name('store');

                    // Get policy
                    Route::get('{id}', [AdminCancellationPolicyController::class, 'show'])
                        ->where('id', '[0-9]+')
                        ->name('show');

                    // Update policy
                    Route::put('{id}', [AdminCancellationPolicyController::class, 'update'])
                        ->where('id', '[0-9]+')
                        ->name('update');

                    // Delete policy
                    Route::delete('{id}', [AdminCancellationPolicyController::class, 'destroy'])
                        ->where('id', '[0-9]+')
                        ->name('destroy');

                    // Toggle policy status
                    Route::post('{id}/toggle-status', [AdminCancellationPolicyController::class, 'toggleStatus'])
                        ->where('id', '[0-9]+')
                        ->name('toggle-status');

                    // Clone policy
                    Route::post('{id}/clone', [AdminCancellationPolicyController::class, 'clone'])
                        ->where('id', '[0-9]+')
                        ->name('clone');

                    // Get policy usage
                    Route::get('{id}/usage', [AdminCancellationPolicyController::class, 'usage'])
                        ->where('id', '[0-9]+')
                        ->name('usage');
                });
        });
});
