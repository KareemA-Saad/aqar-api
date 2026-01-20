<?php

return [
    'name' => 'RealEstate',
    
    /*
    |--------------------------------------------------------------------------
    | Package Limits
    |--------------------------------------------------------------------------
    |
    | Default limits for tenant packages
    |
    */
    'default_property_limit' => 50,
    'default_compound_limit' => 10,
    'default_area_limit' => 20,
    
    /*
    |--------------------------------------------------------------------------
    | Image Settings
    |--------------------------------------------------------------------------
    */
    'image' => [
        'max_images_per_property' => 30,
        'max_images_per_compound' => 20,
        'thumbnail_sizes' => [
            'small' => [150, 150],
            'medium' => [300, 200],
            'large' => [800, 600],
        ],
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'max_file_size' => 5120, // KB
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Currency Settings
    |--------------------------------------------------------------------------
    */
    'default_currency' => 'USD',
    'supported_currencies' => ['USD', 'EUR', 'EGP', 'SAR', 'AED'],
    
    /*
    |--------------------------------------------------------------------------
    | Inquiry Settings
    |--------------------------------------------------------------------------
    */
    'inquiry' => [
        'statuses' => ['new', 'contacted', 'qualified', 'converted', 'closed'],
        'expected_response_hours' => 24,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'property_types_ttl' => 3600, // 1 hour
        'amenities_ttl' => 3600,
        'areas_ttl' => 1800, // 30 minutes
        'developers_ttl' => 3600,
    ],
];
