<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Domain;

return [
    /**
     * The tenant model used by the application.
     * Should extend Stancl\Tenancy\Database\Models\Tenant.
     */
    'tenant_model' => \App\Models\Tenant::class,

    /**
     * ID generator for tenants.
     * UUID provides secure, non-sequential identifiers.
     */
    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,

    /**
     * Domain model (kept for compatibility, optional for API-first approach).
     */
    'domain_model' => Domain::class,

    /**
     * Central domains configuration.
     * These domains are NEVER treated as tenant domains.
     */
    'central_domains' => [
        env('CENTRAL_DOMAIN', 'localhost'),
        env('APP_DOMAIN', 'api.aqar.local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token-Based Tenant Identification (API-First)
    |--------------------------------------------------------------------------
    |
    | For API authentication, tenants are identified via:
    | 1. Sanctum token abilities: tenant:{tenant_id}
    | 2. X-Tenant-ID header
    | 3. Route parameter: /tenant/{tenant}/...
    | 4. Query parameter: ?tenant={tenant_id}
    |
    | Domain-based identification is disabled by default for API.
    |
    */
    'identification' => [
        /**
         * Header name for tenant identification.
         * Clients should send: X-Tenant-ID: {tenant_uuid}
         */
        'header' => env('TENANT_HEADER', 'X-Tenant-ID'),

        /**
         * Identification methods in order of priority.
         */
        'resolvers' => [
            'token',     // Primary: Extract from Sanctum token abilities
            'header',    // Secondary: X-Tenant-ID header
            'route',     // Tertiary: Route parameter
            'query',     // Fallback: Query parameter
        ],

        /**
         * Query parameter name for tenant identification.
         */
        'query_parameter' => 'tenant',

        /**
         * Route parameter name for tenant identification.
         */
        'route_parameter' => 'tenant',
    ],

    /**
     * Tenancy bootstrappers are executed when tenancy is initialized.
     * Their responsibility is making Laravel features tenant-aware.
     */
    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        // Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class, // Requires phpredis
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for tenant database creation and management.
    |
    */
    'database' => [
        /**
         * The connection name for the central (landlord) database.
         */
        'central_connection' => env('DB_CONNECTION', 'central'),

        /**
         * Template connection for tenant databases.
         */
        'template_tenant_connection' => 'tenant',

        /**
         * Tenant database naming pattern.
         * Final name: prefix + tenant_id + suffix
         * Example: tenant_abc123def456
         */
        'prefix' => env('TENANT_DB_PREFIX', 'tenant_'),
        'suffix' => '',

        /**
         * Database managers for different drivers.
         */
        'managers' => [
            'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],

        /**
         * Automatically create database when tenant is created.
         * Set to false to use queued job instead.
         */
        'auto_create' => env('TENANT_AUTO_CREATE_DB', false),

        /**
         * Automatically delete database when tenant is deleted.
         */
        'auto_delete' => env('TENANT_AUTO_DELETE_DB', true),

        /**
         * Run migrations automatically after database creation.
         */
        'auto_migrate' => env('TENANT_AUTO_MIGRATE', false),

        /**
         * Run seeders automatically after migrations.
         */
        'auto_seed' => env('TENANT_AUTO_SEED', false),
    ],

    /**
     * Cache tenancy configuration.
     */
    'cache' => [
        'tag_base' => 'tenant',
    ],

    /**
     * Filesystem tenancy configuration.
     */
    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
        ],
        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
        'suffix_storage_path' => true,
        'asset_helper_tenancy' => true,
    ],

    /**
     * Redis tenancy configuration.
     */
    'redis' => [
        'prefix_base' => 'tenant_',
        'prefixed_connections' => [],
    ],

    /**
     * Features for tenancy.
     */
    'features' => [
        Stancl\Tenancy\Features\TenantConfig::class,
        Stancl\Tenancy\Features\CrossDomainRedirect::class,
    ],

    /**
     * Register tenancy routes (tenant asset routes).
     */
    'routes' => false, // Disabled for API-only

    /*
    |--------------------------------------------------------------------------
    | Migration & Seeding Configuration
    |--------------------------------------------------------------------------
    */
    'migration_parameters' => [
        '--force' => true,
        '--path' => [
            database_path('migrations/tenant'),
        ],
        '--realpath' => true,
    ],

    'seeder_parameters' => [
        '--class' => 'Database\\Seeders\\TenantDatabaseSeeder',
        '--force' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Events Configuration
    |--------------------------------------------------------------------------
    |
    | Events dispatched during tenant lifecycle.
    |
    */
    'events' => [
        // Stancl\Tenancy\Events\TenantCreated::class => [],
        // Stancl\Tenancy\Events\TenantDeleted::class => [],
        // Stancl\Tenancy\Events\DatabaseCreated::class => [],
        // Stancl\Tenancy\Events\DatabaseDeleted::class => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration for Tenant Operations
    |--------------------------------------------------------------------------
    */
    'queue' => [
        /**
         * Queue name for tenant database operations.
         */
        'connection' => env('TENANT_QUEUE_CONNECTION', 'sync'),

        /**
         * Queue name for tenant jobs.
         */
        'queue' => env('TENANT_QUEUE_NAME', 'tenant-operations'),

        /**
         * Number of retry attempts for failed jobs.
         */
        'tries' => 3,

        /**
         * Timeout in seconds for tenant jobs.
         */
        'timeout' => 300,
    ],
];
