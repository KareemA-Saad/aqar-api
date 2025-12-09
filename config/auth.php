<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. For API-only apps, we default to
    | the api_user guard with Sanctum token authentication.
    |
    */

    'defaults' => [
        'guard' => 'api_user',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | AQAR API uses three distinct guards for different user types:
    |
    | - api_admin: Landlord/platform administrators (central database)
    | - api_user: Landlord users / Tenant owners (central database)
    | - api_tenant_user: Tenant end-users (tenant database)
    |
    | All guards use Sanctum for stateless API token authentication.
    |
    */

    'guards' => [
        /**
         * Platform administrators guard.
         * Used for: System admins, support staff, platform management.
         * Database: Central (landlord) database.
         */
        'api_admin' => [
            'driver' => 'sanctum',
            'provider' => 'admins',
        ],

        /**
         * Landlord users / Tenant owners guard.
         * Used for: Users who own/manage tenants, subscription holders.
         * Database: Central (landlord) database.
         */
        'api_user' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],

        /**
         * Tenant end-users guard.
         * Used for: End customers within a specific tenant context.
         * Database: Tenant-specific database (requires tenant initialization).
         */
        'api_tenant_user' => [
            'driver' => 'sanctum',
            'provider' => 'tenant_users',
        ],

        /**
         * Web guard (kept for compatibility with packages).
         * Not used in API-only context but some packages require it.
         */
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | Each guard has an associated user provider that defines how users
    | are retrieved from the database. Different providers point to
    | different models and potentially different database connections.
    |
    */

    'providers' => [
        /**
         * Platform administrators (central database).
         */
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],

        /**
         * Landlord users / Tenant owners (central database).
         */
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        /**
         * Tenant end-users (tenant database).
         * Model uses tenant connection automatically when tenancy is initialized.
         */
        'tenant_users' => [
            'driver' => 'eloquent',
            'model' => App\Models\TenantUser::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | Password reset configuration for each user type.
    | Tokens expire after the specified number of minutes.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'admins' => [
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],

        'tenant_users' => [
            'provider' => 'tenant_users',
            'table' => 'password_reset_tokens', // In tenant database
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | The amount of seconds before a password confirmation times out.
    | Default: 3 hours (10800 seconds).
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
