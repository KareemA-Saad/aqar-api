<?php


use Illuminate\Support\Facades\Route;
use Modules\HotelBooking\Http\Controllers\Tenant\Admin\AmenityController;
use Modules\HotelBooking\Http\Controllers\Tenant\Admin\BedTypeController;
use Modules\HotelBooking\Http\Controllers\Tenant\Admin\HotelBookingController;
use Modules\HotelBooking\Http\Controllers\Tenant\Admin\RoomBookInventoryController;
use Modules\HotelBooking\Http\Controllers\Tenant\Admin\HotelController;
use Modules\HotelBooking\Http\Controllers\Tenant\Admin\NewsLetterController;
use Modules\HotelBooking\Http\Controllers\Tenant\Admin\RoomController;
use Modules\HotelBooking\Http\Controllers\Tenant\Admin\RoomTypeController;
use Modules\HotelBooking\Http\Controllers\Tenant\Frontend\HotelBookingPaymentLogController;
use Modules\HotelBooking\Http\Controllers\Tenant\Frontend\HotelReviewController;
use Modules\HotelBooking\Http\Controllers\Tenant\Frontend\AttractionController;
use Modules\HotelBooking\Http\Controllers\Tenant\Admin\AdminReportController;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;


Route::middleware([
    'web',
    \App\Http\Middleware\Tenant\InitializeTenancyByDomainCustomisedMiddleware::class,
    PreventAccessFromCentralDomains::class,
    'auth:admin',
    'tenant_admin_glvar',
    'package_expire',
    'tenantAdminPanelMailVerify',
    'setlang'
])->prefix('admin-home')->name('tenant.')->group(function () {
    Route::group(['as' => 'admin.'], function (){
        /*-----------------------------------
        Bed type  ROUTES
        ------------------------------------*/
        Route::post('bed-type/delete/{id}',[BedTypeController::class,'destroy'])->name('bed_type.delete');

        Route::resource('bed-type',BedTypeController::class);

        /*-----------------------------------
        Amenities ROUTES
        ------------------------------------*/
        Route::post('amenities/delete/{id}',[AmenityController::class,'destroy'])->name('amenities.delete');
        Route::resource('amenities',AmenityController::class);

        /*-----------------------------------
         Hotel ROUTES
        ------------------------------------*/
        Route::post('hotels/delete/{id}',[HotelController::class,'destroy'])->name('hotels.delete');
        Route::get('hotels/country-state/{country_id}','Tenant\Admin\HotelController@country_state')->name('hotels.country_state');
        Route::get('all/hotel-reviews','Tenant\Admin\HotelController@all_hotel_review')->name('all_hotel_review');
        Route::resource('hotels',HotelController::class);

        /*-----------------------------------
        Room Type ROUTES
        ------------------------------------*/
        Route::post('room-types/delete/{room_type}',[RoomTypeController::class,'destroy'])->name('room-types.delete');
        Route::resource('room-types',RoomTypeController::class);

        /*-----------------------------------
        Room ROUTES
        ------------------------------------*/
        Route::post('rooms/delete/{id}',[RoomController::class,'destroy'])->name('rooms.delete');
        Route::resource('rooms',RoomController::class);

        /*-----------------------------------
        Admin Report ROUTES
        ------------------------------------*/
        Route::get('reports','Tenant\Admin\AdminReportController@index')->name('reports');
        Route::post('search','Tenant\Admin\AdminReportController@search')->name('report.search');

        /*-----------------------------------
        HotelBooking ROUTES
         ------------------------------------*/
        Route::controller(HotelBookingController::class)->prefix('hotel-bookings')
            ->name('hotel-bookings.')->group(function() {
            Route::post('/calculate-amount','calculate_amount')->name("calculate_amount");
            Route::get("/room_type/{id}", 'hotel_room_type')->name("room_type");
            Route::get("/rooms/{id}", 'hotel_rooms')->name("hotel_rooms");
            Route::post('/delete/{id}','destroy')->name('delete');
            Route::post('/payment/status/update','payment_status_updated')->name('payment-status-update');
            Route::get("/approved", 'approved_booking')->name("approved");
            Route::get("/cancel-requested", 'cancel_requested_booking')->name("cancel-requested");
            Route::get("/canceled", 'canceled_booking')->name("canceled");
            Route::resource('/',HotelBookingController::class);
        });

         /*-----------------------------------
         RoomTypeInventory ROUTES
         ------------------------------------*/
         Route::controller(RoomBookInventoryController::class)->prefix('room-book-inventories')
            ->name('room-book-inventories.')->group(function() {
            Route::post("/update", 'inventory_update')->name("updated");
            Route::post("/bulk-update", 'inventory_bulk_update')->name("bulk.update");
            Route::post("/inventory_search", 'search_inventories')->name("inventory_search");
            Route::post("/search", 'search')->name("search");
            Route::post("/price_search", 'price_search')->name("price.search");
            Route::post("/price-update", 'inventory_price_update')->name("bulk.price.update");
            Route::post('delete/{id}','destroy')->name('delete');
            Route::get('index','index')->name('index');
        });
    });

});

Route::middleware([
    'web',
    \App\Http\Middleware\Tenant\InitializeTenancyByDomainCustomisedMiddleware::class,
    PreventAccessFromCentralDomains::class,
    'tenant_glvar',
    'setlang'
])->group(function () {


    /*----------------------------------------------------------------------------------------------------------------------------
    |                                                      FRONTEND ROUTES (Tenants)
    |----------------------------------------------------------------------------------------------------------------------------*/
    Route::middleware('package_expire')->controller(\Modules\HotelBooking\Http\Controllers\Tenant\Frontend\RoomBookingController::class)
        ->prefix('bookings')->name('tenant.frontend.')->group(function () {
            Route::get('/hotel-details/{slug}', 'hotel_details')->name('hotel-details');
            Route::any('/search/rooms', 'search_room')->name('search_room');
            Route::any('/room/details/{slug}', 'room_details')->name('room_details');
            Route::any('/confirmation', 'confirmation')->name('confirmation');
            Route::any('/checkout', 'checkout')->name('checkout');
            Route::any('/checkout-view', 'checkout_view')->name('checkout-view');
            Route::any('/price-filter', 'price_filter')->name('price-filter');
            Route::get("/states/{country_id}", 'country_state')->name("country_state");
            Route::any("/checkout.sync-total-price.ajax", 'sync_total_price')->name("checkout.sync-total-price.ajax");
        });

  /*-----------------------------------
   RoomTypeInventory ROUTES
   ------------------------------------*/
    Route::middleware('package_expire')->prefix('hotel-reviews')->name('tenant.frontend.')->group(function () {
        Route::post('/', 'Tenant\Frontend\HotelReviewController@hotel_review')->name('hotel-reviews');
    });

    /*-----------------------------------
   Attraction ROUTES
   ----------------------- -------------*/
    Route::name('tenant.frontend.')->group(function () {
        Route::post('/single-attractions', [AttractionController::class, 'single_attraction'])->name('single-attraction');
    });

    // hotel-booking Payment
    Route::middleware('package_expire')->controller(HotelBookingPaymentLogController::class)->prefix('hotel-booking-payment')->name('tenant.')->group(function () {
        Route::get('/payment/success/{id}', 'hotel_booking_payment_success')->name('frontend.hotel-booking.payment.success');
        Route::get('/static/payment/cancel', 'hotel_booking_payment_cancel')->name('frontend.hotel-booking.payment.cancel');
        Route::post('/store', 'hotel_booking_store')->name('frontend.hotel-booking.payment.store');
        Route::post('/paypal-ipn', 'paypal_ipn')->name('frontend.hotel-booking.paypal.ipn');
        Route::post('/paytm-ipn', 'paytm_ipn')->name('frontend.hotel-booking.paytm.ipn');
        Route::get('/mollie-ipn', 'mollie_ipn')->name('frontend.hotel-booking.mollie.ipn');
        Route::get('/stripe-ipn', 'stripe_ipn')->name('frontend.hotel-booking.stripe.ipn');
        Route::post('/razorpay-ipn', 'razorpay_ipn')->name('frontend.hotel-booking.razorpay.ipn');
        Route::post('/payfast-ipn', 'payfast_ipn')->name('frontend.hotel-booking.payfast.ipn');
        Route::get('/flutterwave/ipn', 'flutterwave_ipn')->name('frontend.hotel-booking.flutterwave.ipn');
        Route::get('/paystack-ipn', 'paystack_ipn')->name('frontend.hotel-booking.paystack.ipn');
        Route::get('/midtrans-ipn', 'midtrans_ipn')->name('frontend.hotel-booking.midtrans.ipn');
        Route::post('/cashfree-ipn', 'cashfree_ipn')->name('frontend.hotel-booking.cashfree.ipn');
        Route::get('/instamojo-ipn', 'instamojo_ipn')->name('frontend.hotel-booking.instamojo.ipn');
        Route::get('/paypal-ipn', 'paypal_ipn')->name('frontend.paypal.hotel-booking.ipn');
        Route::get('/marcadopago-ipn', 'marcadopago_ipn')->name('frontend.hotel-booking.marcadopago.ipn');
        Route::get('/squareup-ipn', 'squareup_ipn')->name('frontend.hotel-booking.squareup.ipn');
        Route::post('/cinetpay-ipn', 'cinetpay_ipn')->name('frontend.hotel-booking.cinetpay.ipn');
        Route::post('/paytabs-ipn', 'paytabs_ipn')->name('frontend.hotel-booking.paytabs.ipn');
        Route::post('/billplz-ipn', 'billplz_ipn')->name('frontend.hotel-booking.billplz.ipn');
        Route::post('/zitopay-ipn', 'zitopay_ipn')->name('frontend.hotel-booking.zitopay.ipn');
        Route::post('/toyyibpay-ipn', 'toyyibpay_ipn')->name('frontend.hotel-booking.toyyibpay.ipn');
        Route::post('/pagali-ipn', 'pagali_ipn')->name('frontend.hotel-booking.pagali.ipn');
        Route::get('/authorizenet-ipn', 'authorizenet_ipn')->name('frontend.hotel-booking.authorizenet.ipn');
        Route::get('/sitesway-ipn', 'sitesway_ipn')->name('frontend.hotel-booking.sitesway.ipn');
        Route::post('/kinetic-ipn', 'kinetic_ipn')->name('frontend.hotel-booking.kinetic.ipn')->excludedMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    });

});
