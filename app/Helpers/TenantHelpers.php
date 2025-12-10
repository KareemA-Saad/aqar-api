<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Services\TenantContextService;
use Stancl\Tenancy\Tenancy;

if (!function_exists('tenant')) {
    /**
     * Get the current tenant instance.
     *
     * @return Tenant|null
     */
    function tenant(): ?Tenant
    {
        /** @var Tenancy $tenancy */
        $tenancy = app(Tenancy::class);

        if (!$tenancy->initialized) {
            return null;
        }

        $tenant = $tenancy->tenant;

        return $tenant instanceof Tenant ? $tenant : null;
    }
}

if (!function_exists('tenant_id')) {
    /**
     * Get the current tenant ID.
     *
     * @return string|null
     */
    function tenant_id(): ?string
    {
        $tenant = tenant();

        return $tenant?->id;
    }
}

if (!function_exists('tenant_asset')) {
    /**
     * Generate an asset path for the current tenant.
     *
     * @param string $path Asset path relative to tenant's storage
     * @return string Full URL to the asset
     */
    function tenant_asset(string $path): string
    {
        $tenant = tenant();

        if (!$tenant) {
            return asset($path);
        }

        $basePath = config('tenancy.filesystem.asset_helper_tenancy', true)
            ? "tenant/{$tenant->id}"
            : '';

        return asset(trim("{$basePath}/{$path}", '/'));
    }
}

if (!function_exists('tenant_storage_path')) {
    /**
     * Get the storage path for the current tenant.
     *
     * @param string $path Path relative to tenant's storage
     * @return string Full storage path
     */
    function tenant_storage_path(string $path = ''): string
    {
        $tenant = tenant();

        if (!$tenant) {
            return storage_path($path);
        }

        $basePath = storage_path("tenant/{$tenant->id}");

        return $path ? "{$basePath}/{$path}" : $basePath;
    }
}

if (!function_exists('tenant_public_path')) {
    /**
     * Get the public storage path for the current tenant.
     *
     * @param string $path Path relative to tenant's public storage
     * @return string Full public path
     */
    function tenant_public_path(string $path = ''): string
    {
        $tenant = tenant();

        if (!$tenant) {
            return public_path($path);
        }

        $basePath = public_path("tenant/{$tenant->id}");

        return $path ? "{$basePath}/{$path}" : $basePath;
    }
}

if (!function_exists('get_tenant_option')) {
    /**
     * Get a tenant option value.
     *
     * @param string $key Option key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    function get_tenant_option(string $key, mixed $default = null): mixed
    {
        /** @var TenantContextService $service */
        $service = app(TenantContextService::class);

        return $service->getTenantOption($key, $default);
    }
}

if (!function_exists('set_tenant_option')) {
    /**
     * Set a tenant option value.
     *
     * @param string $key Option key
     * @param mixed $value Option value
     * @return bool
     */
    function set_tenant_option(string $key, mixed $value): bool
    {
        /** @var TenantContextService $service */
        $service = app(TenantContextService::class);

        return $service->setTenantOption($key, $value);
    }
}

if (!function_exists('tenant_has_feature')) {
    /**
     * Check if the current tenant has access to a feature.
     *
     * @param string $feature Feature name
     * @return bool
     */
    function tenant_has_feature(string $feature): bool
    {
        /** @var TenantContextService $service */
        $service = app(TenantContextService::class);

        return $service->checkFeatureAccess($feature);
    }
}

if (!function_exists('tenant_package_info')) {
    /**
     * Get the current tenant's package information.
     *
     * @return array<string, mixed>
     */
    function tenant_package_info(): array
    {
        /** @var TenantContextService $service */
        $service = app(TenantContextService::class);

        return $service->getPackageInfo();
    }
}

if (!function_exists('tenant_remaining_days')) {
    /**
     * Get the number of days remaining on tenant's subscription.
     *
     * @return int -1 for lifetime, 0 for expired, positive for remaining days
     */
    function tenant_remaining_days(): int
    {
        /** @var TenantContextService $service */
        $service = app(TenantContextService::class);

        return $service->getRemainingDays();
    }
}

if (!function_exists('tenant_is_active')) {
    /**
     * Check if the current tenant's subscription is active.
     *
     * @return bool
     */
    function tenant_is_active(): bool
    {
        /** @var TenantContextService $service */
        $service = app(TenantContextService::class);

        return $service->isSubscriptionActive();
    }
}

if (!function_exists('tenant_features')) {
    /**
     * Get all features allowed for the current tenant.
     *
     * @return array<string>
     */
    function tenant_features(): array
    {
        /** @var TenantContextService $service */
        $service = app(TenantContextService::class);

        return $service->getAllowedFeatures();
    }
}

if (!function_exists('tenant_settings')) {
    /**
     * Get all settings for the current tenant.
     *
     * @return array<string, mixed>
     */
    function tenant_settings(): array
    {
        /** @var TenantContextService $service */
        $service = app(TenantContextService::class);

        return $service->getTenantSettings();
    }
}

if (!function_exists('is_tenant_context')) {
    /**
     * Check if we are currently in a tenant context.
     *
     * @return bool
     */
    function is_tenant_context(): bool
    {
        /** @var Tenancy $tenancy */
        $tenancy = app(Tenancy::class);

        return $tenancy->initialized;
    }
}

