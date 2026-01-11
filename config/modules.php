<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Feature to Module Mapping
    |--------------------------------------------------------------------------
    |
    | Maps plan feature names (from plan_features.feature_name) to module names.
    | When a tenant subscribes to a plan, only modules with enabled features
    | will have their migrations run.
    |
    | - Feature names are case-insensitive (converted to lowercase)
    | - Module names must match directory names in Modules/
    | - Set to null for features that use only base tables
    |
    */
    'feature_module_map' => [
        // Content Modules
        'blog' => 'Blog',
        'portfolio' => 'Portfolio',
        'service' => 'Service',
        'knowledgebase' => 'Knowledgebase',
        
        // E-Commerce
        'ecommerce' => 'Product',
        'product' => 'Product', // Alias
        
        // Booking & Events
        'appointment' => 'Appointment',
        'event' => 'Event',
        'job' => 'Job',
        
        // Fundraising
        'donation' => 'Donation',
        
        // Hotel Booking
        'hotelbooking' => 'HotelBooking',
        'hotel' => 'HotelBooking', // Alias
        
        // Features that are part of other modules
        'advertisement' => 'Blog', // Advertisement is part of Blog module
        'gallery' => 'Blog', // Gallery often bundled with Blog
        
        // Features that use only base tables (no module)
        'brand' => null, // Uses base tables
        'testimonial' => null, // Uses base tables
        'faq' => null, // Uses base tables
        'wedding_price_plan' => null, // Uses base tables
        'newsletter' => null, // Uses base tables
    ],

    /*
    |--------------------------------------------------------------------------
    | Core Modules
    |--------------------------------------------------------------------------
    |
    | These modules are always enabled for all tenants regardless of plan.
    | They provide essential functionality for the platform.
    |
    | Core modules are typically infrastructure/utility modules that don't
    | represent billable features.
    |
    */
    'core_modules' => [
        'Attributes',      // Product attributes (colors, sizes, brands)
        'Badge',           // Badge system
        'Campaign',        // Campaign management
        'CouponManage',    // Coupon system
        'CountryManage',   // Countries, states, cities
        'Inventory',       // Inventory management
        'ShippingModule',  // Shipping options
        'Wallet',          // Digital wallet
    ],

    /*
    |--------------------------------------------------------------------------
    | Trial Plan Behavior
    |--------------------------------------------------------------------------
    |
    | Controls which modules are enabled for trial plans.
    |
    | Options:
    | - 'all': Include all available modules
    | - 'core': Only core modules
    | - 'plan': Follow the plan's features (same as paid plans)
    |
    */
    'trial_modules' => 'all',

    /*
    |--------------------------------------------------------------------------
    | Module Migration Validation
    |--------------------------------------------------------------------------
    |
    | Enable/disable validation that module migration directories exist
    | before attempting to run migrations.
    |
    */
    'validate_migration_paths' => true,

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Control logging verbosity for module migration operations.
    |
    */
    'log_enabled_modules' => true,
    'log_migration_paths' => env('APP_DEBUG', false),
];
