<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PaymentLog;
use App\Models\PlanFeature;
use App\Models\PricePlan;
use App\Models\StaticOption;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Stancl\Tenancy\Tenancy;

/**
 * Tenant Context Service
 *
 * Provides methods for accessing and managing tenant context information.
 * Handles tenant settings, feature access, and package information.
 */
final class TenantContextService
{
    /**
     * Cache TTL in seconds for tenant settings.
     */
    private const CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private readonly Tenancy $tenancy,
    ) {}

    /**
     * Get the current tenant.
     *
     * @return Tenant|null
     */
    public function getCurrentTenant(): ?Tenant
    {
        if (!$this->tenancy->initialized) {
            return null;
        }

        $tenant = $this->tenancy->tenant;

        return $tenant instanceof Tenant ? $tenant : null;
    }

    /**
     * Check if tenancy is initialized.
     *
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->tenancy->initialized;
    }

    /**
     * Get tenant settings as array.
     *
     * @param Tenant|null $tenant
     * @return array<string, mixed>
     */
    public function getTenantSettings(?Tenant $tenant = null): array
    {
        $tenant = $tenant ?? $this->getCurrentTenant();

        if (!$tenant) {
            return [];
        }

        $cacheKey = "tenant_settings_{$tenant->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenant) {
            return [
                'id' => $tenant->id,
                'user_id' => $tenant->user_id,
                'theme' => $tenant->theme,
                'theme_code' => $tenant->theme_code,
                'instruction_status' => $tenant->instruction_status,
                'data' => $tenant->data ?? [],
                'created_at' => $tenant->created_at?->toISOString(),
            ];
        });
    }

    /**
     * Check if tenant has access to a specific feature.
     *
     * @param string $feature Feature name to check
     * @param Tenant|null $tenant
     * @return bool
     */
    public function checkFeatureAccess(string $feature, ?Tenant $tenant = null): bool
    {
        $tenant = $tenant ?? $this->getCurrentTenant();

        if (!$tenant) {
            return false;
        }

        $packageInfo = $this->getPackageInfo($tenant);

        if (empty($packageInfo['features'])) {
            return false;
        }

        // Check direct feature match
        if (in_array($feature, $packageInfo['features'], true)) {
            return true;
        }

        // Check case-insensitive match
        $lowerFeature = strtolower($feature);
        foreach ($packageInfo['features'] as $allowedFeature) {
            if (strtolower($allowedFeature) === $lowerFeature) {
                return true;
            }
        }

        // Check permission fields
        $permissionFields = [
            'page' => $packageInfo['permissions']['page'] ?? 0,
            'blog' => $packageInfo['permissions']['blog'] ?? 0,
            'product' => $packageInfo['permissions']['product'] ?? 0,
            'portfolio' => $packageInfo['permissions']['portfolio'] ?? 0,
            'storage' => $packageInfo['permissions']['storage'] ?? 0,
            'appointment' => $packageInfo['permissions']['appointment'] ?? 0,
        ];

        if (isset($permissionFields[$lowerFeature])) {
            return $permissionFields[$lowerFeature] > 0;
        }

        return false;
    }

    /**
     * Get package information for tenant.
     *
     * @param Tenant|null $tenant
     * @return array<string, mixed>
     */
    public function getPackageInfo(?Tenant $tenant = null): array
    {
        $tenant = $tenant ?? $this->getCurrentTenant();

        if (!$tenant) {
            return [];
        }

        $cacheKey = "tenant_package_{$tenant->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($tenant) {
            $paymentLog = $tenant->paymentLog;

            if (!$paymentLog) {
                return [
                    'has_subscription' => false,
                    'package' => null,
                    'features' => [],
                    'permissions' => [],
                    'expire_date' => null,
                    'is_expired' => true,
                    'is_lifetime' => false,
                ];
            }

            $package = $paymentLog->package;
            $features = [];
            $permissions = [];

            if ($package) {
                // Get features from plan_features table
                $features = PlanFeature::where('plan_id', $package->id)
                    ->where('status', true)
                    ->pluck('feature_name')
                    ->toArray();

                // Get numeric permissions
                $permissions = [
                    'page' => $package->page_permission_feature ?? 0,
                    'blog' => $package->blog_permission_feature ?? 0,
                    'product' => $package->product_permission_feature ?? 0,
                    'portfolio' => $package->portfolio_permission_feature ?? 0,
                    'storage' => $package->storage_permission_feature ?? 0,
                    'appointment' => $package->appointment_permission_feature ?? 0,
                ];
            }

            $expireDate = $paymentLog->expire_date;
            $isLifetime = $expireDate === null;
            $isExpired = !$isLifetime && Carbon::parse($expireDate)->isPast();

            return [
                'has_subscription' => true,
                'package' => $package ? [
                    'id' => $package->id,
                    'title' => $package->title,
                    'type' => $package->type,
                    'price' => $package->price,
                ] : null,
                'features' => $features,
                'permissions' => $permissions,
                'expire_date' => $expireDate?->toISOString(),
                'is_expired' => $isExpired,
                'is_lifetime' => $isLifetime,
                'start_date' => $paymentLog->start_date?->toISOString(),
                'payment_status' => $paymentLog->payment_status,
            ];
        });
    }

    /**
     * Get remaining days until subscription expires.
     *
     * @param Tenant|null $tenant
     * @return int -1 for lifetime, 0 for expired, positive for remaining days
     */
    public function getRemainingDays(?Tenant $tenant = null): int
    {
        $tenant = $tenant ?? $this->getCurrentTenant();

        if (!$tenant) {
            return 0;
        }

        $paymentLog = $tenant->paymentLog;

        if (!$paymentLog) {
            return 0;
        }

        $expireDate = $paymentLog->expire_date;

        // Lifetime subscription
        if ($expireDate === null) {
            return -1;
        }

        $expireDateCarbon = Carbon::parse($expireDate);

        if ($expireDateCarbon->isPast()) {
            return 0;
        }

        return (int) Carbon::now()->diffInDays($expireDateCarbon, false);
    }

    /**
     * Get tenant option value.
     *
     * @param string $key
     * @param mixed $default
     * @param Tenant|null $tenant
     * @return mixed
     */
    public function getTenantOption(string $key, mixed $default = null, ?Tenant $tenant = null): mixed
    {
        $tenant = $tenant ?? $this->getCurrentTenant();

        if (!$tenant) {
            return $default;
        }

        // First check tenant's data column
        $tenantData = $tenant->data ?? [];
        if (isset($tenantData[$key])) {
            return $tenantData[$key];
        }

        // Then check static_options table (in tenant database)
        if ($this->tenancy->initialized) {
            try {
                $option = StaticOption::where('option_name', $key)->first();
                return $option?->option_value ?? $default;
            } catch (\Exception $e) {
                // Table might not exist in tenant database
                return $default;
            }
        }

        return $default;
    }

    /**
     * Set tenant option value.
     *
     * @param string $key
     * @param mixed $value
     * @param Tenant|null $tenant
     * @return bool
     */
    public function setTenantOption(string $key, mixed $value, ?Tenant $tenant = null): bool
    {
        $tenant = $tenant ?? $this->getCurrentTenant();

        if (!$tenant) {
            return false;
        }

        // Store in tenant's data column for persistence
        $tenantData = $tenant->data ?? [];
        $tenantData[$key] = $value;

        $tenant->update(['data' => $tenantData]);

        // Also store in static_options if tenancy is initialized
        if ($this->tenancy->initialized) {
            try {
                StaticOption::updateOrCreate(
                    ['option_name' => $key],
                    ['option_value' => is_array($value) ? json_encode($value) : (string) $value]
                );
            } catch (\Exception $e) {
                // Table might not exist
            }
        }

        // Clear cache
        $this->clearTenantCache($tenant);

        return true;
    }

    /**
     * Get all allowed features for tenant.
     *
     * @param Tenant|null $tenant
     * @return array<string>
     */
    public function getAllowedFeatures(?Tenant $tenant = null): array
    {
        $packageInfo = $this->getPackageInfo($tenant);

        return $packageInfo['features'] ?? [];
    }

    /**
     * Check if tenant subscription is active.
     *
     * @param Tenant|null $tenant
     * @return bool
     */
    public function isSubscriptionActive(?Tenant $tenant = null): bool
    {
        $packageInfo = $this->getPackageInfo($tenant);

        if (!$packageInfo['has_subscription']) {
            return false;
        }

        // Lifetime subscriptions are always active
        if ($packageInfo['is_lifetime']) {
            return true;
        }

        return !$packageInfo['is_expired'];
    }

    /**
     * Clear tenant cache.
     *
     * @param Tenant|null $tenant
     * @return void
     */
    public function clearTenantCache(?Tenant $tenant = null): void
    {
        $tenant = $tenant ?? $this->getCurrentTenant();

        if (!$tenant) {
            return;
        }

        Cache::forget("tenant_settings_{$tenant->id}");
        Cache::forget("tenant_package_{$tenant->id}");
    }
}

