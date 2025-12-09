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
     * Domain model (kept for compatibility, but we use token-based identification).
     */
    'domain_model' => Domain::class,

    /**
     * Central domains configuration.
     * Used when domain identification middleware is active.
     * For token-based auth, this is primarily for fallback/admin access.
     */
    'central_domains' => [
        env('CENTRAL_DOMAIN', 'localhost'),
        env('APP_DOMAIN', 'api.aqar.local'),
    ],

    /**
     * Tenant identification configuration.
     * We use header-based (token) identification instead of domain-based.
     */
    'identification' => [
        /**
         * Header name for tenant identification.
         * Clients must send: X-Tenant-ID: {tenant_uuid}
         */
        'header' => env('TENANT_HEADER', 'X-Tenant-ID'),

        /**
         * Fallback identification methods (in order of priority).
         */
        'fallback' => [
            'header',    // Primary: X-Tenant-ID header
            'query',     // Secondary: ?tenant={uuid} query parameter
        ],

        /**
         * Query parameter name for tenant identification.
         */
        'query_parameter' => 'tenant',
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
        // Stancl\Tenancy\Bootstrappers\RedisTenancyBootstrapper::class, // Requires phpredis extension
    ],

    /**
     * Database tenancy configuration.
     * Used by DatabaseTenancyBootstrapper.
     */
    'database' => [
        /**
         * The connection name for the central (landlord) database.
         * This stores tenants, plans, subscriptions, etc.
         */
        'central_connection' => env('DB_CONNECTION', 'central'),

        /**
         * Template connection for tenant databases.
         * If null, uses the central connection as template.
         */
        'template_tenant_connection' => null,

        /**
         * Tenant database naming pattern.
         * Final name: prefix + tenant_id + suffix
         * Example: tenant_abc123def456
         */
        'prefix' => 'tenant_',
        'suffix' => '',

        /**
         * Database managers handle creation & deletion of tenant databases.
         * Choose based on your database driver.
         */
        'managers' => [
            'sqlite' => Stancl\Tenancy\TenantDatabaseManagers\SQLiteDatabaseManager::class,
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,

            /**
             * MySQL with per-tenant DB user (enhanced security).
             * Uncomment to enable permission-controlled access.
             */
            // 'mysql' => Stancl\Tenancy\TenantDatabaseManagers\PermissionControlledMySQLDatabaseManager::class,

            /**
             * PostgreSQL schema-based separation.
             * Uses schemas instead of separate databases.
             */
            // 'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLSchemaManager::class,
        ],
    ],

    /**
     * Cache tenancy configuration.
     * Used by CacheTenancyBootstrapper.
     *
     * Each cache key is tagged with tenant identifier for isolation.
     */
    'cache' => [
        'tag_base' => 'tenant', // Results in tags like: tenant_abc123
    ],

    /**
     * Filesystem tenancy configuration.
     * Used by FilesystemTenancyBootstrapper.
     */
    'filesystem' => [
        /**
         * Suffix base for tenant-specific disk paths.
         */
        'suffix_base' => 'tenant',

        /**
         * Disks that should be tenant-aware.
         */
        'disks' => [
            'local',
            'public',
            // 's3', // Uncomment for S3 multi-tenancy
        ],

        /**
         * Root path overrides for local disks.
         * %storage_path% is replaced with the tenant's storage path.
         */
        'root_override' => [
            'local' => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],

        /**
         * Whether to suffix storage_path() with tenant identifier.
         * Required for local disk tenancy. Disable only for external storage (S3).
         */
        'suffix_storage_path' => true,

        /**
         * Make asset() helper tenant-aware.
         * Use global_asset() for non-tenant assets when enabled.
         */
        'asset_helper_tenancy' => true,
    ],

    /**
     * Redis tenancy configuration.
     * Used by RedisTenancyBootstrapper.
     *
     * Note: Requires phpredis extension.
     * Not needed if Redis is only used for cache (CacheTenancyBootstrapper handles that).
     */
    'redis' => [
        'prefix_base' => 'tenant_',
        'prefixed_connections' => [
            // 'default',
        ],
    ],

    /**
     * Additional features for tenancy.
     * Enable based on your requirements.
     */
    'features' => [
        // Stancl\Tenancy\Features\UserImpersonation::class,
        // Stancl\Tenancy\Features\TelescopeTags::class,
        // Stancl\Tenancy\Features\UniversalRoutes::class,
        Stancl\Tenancy\Features\TenantConfig::class,
        Stancl\Tenancy\Features\CrossDomainRedirect::class,
    ],

    /**
     * Whether to register tenancy routes (tenant asset routes).
     * Disable if using external storage or custom asset controller.
     */
    'routes' => true,

    /**
     * Parameters for tenants:migrate command.
     */
    'migration_parameters' => [
        '--force' => true, // Required for production migrations
        '--path' => [
            database_path('migrations/tenant'),
        ],
        '--realpath' => true,
    ],

    /**
     * Parameters for tenants:seed command.
     */
    'seeder_parameters' => [
        '--class' => 'Database\\Seeders\\TenantDatabaseSeeder',
        // '--force' => true,
    ],
];

