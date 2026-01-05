<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hotel Booking Module Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure various settings for the hotel booking module.
    |
    */

    'name' => 'HotelBooking',

    /*
    |--------------------------------------------------------------------------
    | Check-in / Check-out Times
    |--------------------------------------------------------------------------
    |
    | Default check-in and check-out times for hotels.
    | Format: 24-hour time (HH:MM)
    |
    */
    'default_check_in_time' => '15:00',  // 3:00 PM
    'default_check_out_time' => '11:00', // 11:00 AM

    /*
    |--------------------------------------------------------------------------
    | Room Hold Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for room holds during checkout process.
    |
    */
    'hold' => [
        // Duration in minutes
        'duration' => 15,

        // Maximum extensions allowed
        'max_extensions' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Booking Settings
    |--------------------------------------------------------------------------
    |
    | Various booking-related settings.
    |
    */
    'booking' => [
        // Maximum rooms per booking
        'max_rooms' => 10,

        // Maximum guests per room
        'max_guests_per_room' => 6,

        // Minimum advance booking days
        'min_advance_days' => 0,

        // Maximum advance booking days
        'max_advance_days' => 365,

        // Booking code prefix
        'code_prefix' => 'HB',

        // Auto-confirm bookings after payment
        'auto_confirm_on_payment' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cancellation Settings
    |--------------------------------------------------------------------------
    |
    | Default cancellation policy settings.
    |
    */
    'cancellation' => [
        // Allow cancellation up to X hours before check-in
        'deadline_hours' => 24,

        // Default refund percentage
        'default_refund_percentage' => 100,

        // Process refunds automatically
        'auto_process_refunds' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | Payment-related configuration.
    |
    */
    'payment' => [
        // Available payment methods
        'methods' => ['stripe', 'paypal', 'cod'],

        // Default currency
        'currency' => 'USD',

        // Require full payment upfront
        'require_full_payment' => false,

        // Deposit percentage (if not full payment)
        'deposit_percentage' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Meal Plans
    |--------------------------------------------------------------------------
    |
    | Available meal plan options and their default prices (per person/day).
    |
    */
    'meal_plans' => [
        'room_only' => [
            'name' => 'Room Only',
            'description' => 'No meals included',
            'price_per_person' => 0,
        ],
        'breakfast' => [
            'name' => 'Breakfast',
            'description' => 'Breakfast included',
            'price_per_person' => 15,
        ],
        'half_board' => [
            'name' => 'Half Board',
            'description' => 'Breakfast and dinner included',
            'price_per_person' => 35,
        ],
        'full_board' => [
            'name' => 'Full Board',
            'description' => 'All meals included',
            'price_per_person' => 50,
        ],
        'all_inclusive' => [
            'name' => 'All Inclusive',
            'description' => 'All meals and drinks included',
            'price_per_person' => 75,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Extras / Add-ons
    |--------------------------------------------------------------------------
    |
    | Available extra services and their default prices.
    |
    */
    'extras' => [
        'airport_transfer' => [
            'name' => 'Airport Transfer',
            'description' => 'Round-trip airport transfer',
            'price' => 50,
            'per_booking' => true,
        ],
        'late_checkout' => [
            'name' => 'Late Checkout',
            'description' => 'Checkout at 3 PM instead of 11 AM',
            'price' => 30,
            'per_booking' => true,
        ],
        'early_checkin' => [
            'name' => 'Early Check-in',
            'description' => 'Check-in at 11 AM instead of 3 PM',
            'price' => 30,
            'per_booking' => true,
        ],
        'extra_bed' => [
            'name' => 'Extra Bed',
            'description' => 'Extra bed in room',
            'price' => 25,
            'per_night' => true,
        ],
        'parking' => [
            'name' => 'Parking',
            'description' => 'On-site parking',
            'price' => 15,
            'per_night' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Inventory Settings
    |--------------------------------------------------------------------------
    |
    | Settings for inventory management.
    |
    */
    'inventory' => [
        // Days to generate inventory ahead
        'generate_days_ahead' => 365,

        // Minimum availability to show room
        'min_available' => 1,

        // Default pricing type
        'default_pricing_type' => 'per_room',
    ],

    /*
    |--------------------------------------------------------------------------
    | Review Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the review system.
    |
    */
    'reviews' => [
        // Require approval before publishing
        'require_approval' => true,

        // Allow reviews only from verified guests
        'verified_guests_only' => true,

        // Minimum rating
        'min_rating' => 1,

        // Maximum rating
        'max_rating' => 5,
    ],
];
