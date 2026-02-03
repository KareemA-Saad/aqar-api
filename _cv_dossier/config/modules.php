<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Module Plan Limits
    |--------------------------------------------------------------------------
    |
    | Maps module names to their corresponding limit columns in the price_plans table.
    | These limits control how many items a tenant can create per module.
    |
    | Values in price_plans table:
    | - Positive integer: Maximum allowed items
    | - 0 or null: Unlimited
    | - -1: Feature disabled (not applicable here, handled by plan features)
    |
    */
    'limits' => [
        'blog' => 'blog_permission_feature',
        'product' => 'product_create_permission',
        'service' => 'service_permission_feature',
        'portfolio' => 'portfolio_permission_feature',
        'job' => 'job_permission_feature',
        'event' => 'event_permission_feature',
        'donation' => 'donation_permission_feature',
        'knowledgebase' => 'knowledgebase_permission_feature',
        'appointment' => 'appointment_permission_feature',
        'campaign' => 'campaign_create_permission',
        'page' => 'page_permission_feature',
        'storage' => 'storage_permission_feature', // in MB

        // RealEstate Module Limits
        'property' => 'property_permission_feature',
        'compound' => 'compound_permission_feature',
        'inquiry' => 'inquiry_permission_feature',
        'saved_property' => 'saved_property_permission_feature',
    ],

    /*
    |--------------------------------------------------------------------------
    | Module Table Mapping
    |--------------------------------------------------------------------------
    |
    | Maps module names to their database table names for counting current usage.
    | These tables exist in the tenant database.
    |
    */
    'tables' => [
        'blog' => 'blogs',
        'product' => 'products',
        'service' => 'services',
        'portfolio' => 'portfolios',
        'job' => 'jobs',
        'event' => 'events',
        'donation' => 'donations',
        'knowledgebase' => 'knowledgebases',
        'appointment' => 'appointments',
        'campaign' => 'campaigns',
        'page' => 'pages',

        // RealEstate Module Tables
        'property' => 're_properties',
        'compound' => 're_compounds',
        'inquiry' => 're_property_inquiries',
        'saved_property' => 're_saved_properties',
    ],

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
    | - Feature prefixes are also supported (e.g., "properties" matches "Properties 25")
    | - Module names must match directory names in Modules/
    | - Set to null for features that use only base tables
    |
    */
    'feature_module_map' => [
        // Real Estate Module
        'properties' => 'RealEstate',
        'compounds' => 'RealEstate',
        'realestate' => 'RealEstate',
        'property' => 'RealEstate', // Alias
        'compound' => 'RealEstate', // Alias

        // Content Modules
        'blog' => 'Blog',
        'portfolio' => 'Portfolio',
        'service' => 'Service',
        'knowledgebase' => 'Knowledgebase',
        'article' => 'Knowledgebase', // Alias (Article X maps to Knowledgebase)

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
