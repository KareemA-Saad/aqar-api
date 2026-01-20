<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PaymentLog;
use App\Models\PricePlan;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PlanLimitService
 *
 * Handles plan-based usage limits for tenant modules.
 * Validates content creation against plan limits and provides usage statistics.
 */
final class PlanLimitService
{
    /**
     * Module limit column mapping.
     * Maps module names to their corresponding limit columns in price_plans table.
     *
     * @var array<string, string>
     */
    private const MODULE_LIMIT_MAP = [
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
    ];

    /**
     * Module table mapping.
     * Maps module names to their database table names for counting.
     *
     * @var array<string, string>
     */
    private const MODULE_TABLE_MAP = [
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
    ];

    /**
     * Unlimited limit value.
     * A value of -1 or 0 means unlimited (depending on context).
     */
    private const UNLIMITED = -1;

    /**
     * Check if tenant can create a new item for the specified module.
     */
    public function canCreate(Tenant $tenant, string $module): bool
    {
        $module = strtolower($module);
        $limit = $this->getPlanLimit($tenant, $module);

        // Unlimited
        if ($limit === self::UNLIMITED || $limit === 0) {
            return true;
        }

        $currentUsage = $this->getCurrentUsage($tenant, $module);

        return $currentUsage < $limit;
    }

    /**
     * Check if tenant can create a blog post.
     */
    public function canCreateBlog(Tenant $tenant): bool
    {
        return $this->canCreate($tenant, 'blog');
    }

    /**
     * Check if tenant can create a product.
     */
    public function canCreateProduct(Tenant $tenant): bool
    {
        return $this->canCreate($tenant, 'product');
    }

    /**
     * Check if tenant can create a service.
     */
    public function canCreateService(Tenant $tenant): bool
    {
        return $this->canCreate($tenant, 'service');
    }

    /**
     * Check if tenant can create a portfolio item.
     */
    public function canCreatePortfolio(Tenant $tenant): bool
    {
        return $this->canCreate($tenant, 'portfolio');
    }

    /**
     * Check if tenant can create a job listing.
     */
    public function canCreateJob(Tenant $tenant): bool
    {
        return $this->canCreate($tenant, 'job');
    }

    /**
     * Check if tenant can create an event.
     */
    public function canCreateEvent(Tenant $tenant): bool
    {
        return $this->canCreate($tenant, 'event');
    }

    /**
     * Check if tenant can create a donation.
     */
    public function canCreateDonation(Tenant $tenant): bool
    {
        return $this->canCreate($tenant, 'donation');
    }

    /**
     * Check if tenant can create a knowledgebase article.
     */
    public function canCreateKnowledgebase(Tenant $tenant): bool
    {
        return $this->canCreate($tenant, 'knowledgebase');
    }

    /**
     * Check if tenant can create an appointment.
     */
    public function canCreateAppointment(Tenant $tenant): bool
    {
        return $this->canCreate($tenant, 'appointment');
    }

    /**
     * Check if tenant can create a campaign.
     */
    public function canCreateCampaign(Tenant $tenant): bool
    {
        return $this->canCreate($tenant, 'campaign');
    }

    /**
     * Get the remaining limit for a module.
     *
     * @return int Remaining count, or -1 for unlimited
     */
    public function getRemainingLimit(Tenant $tenant, string $module): int
    {
        $module = strtolower($module);
        $limit = $this->getPlanLimit($tenant, $module);

        // Unlimited
        if ($limit === self::UNLIMITED || $limit === 0) {
            return self::UNLIMITED;
        }

        $currentUsage = $this->getCurrentUsage($tenant, $module);

        return max(0, $limit - $currentUsage);
    }

    /**
     * Get current usage count for a module.
     */
    public function getCurrentUsage(Tenant $tenant, string $module): int
    {
        $module = strtolower($module);

        // Storage is handled differently (in bytes)
        if ($module === 'storage') {
            return $this->getStorageUsageMB($tenant);
        }

        $table = self::MODULE_TABLE_MAP[$module] ?? null;

        if ($table === null) {
            Log::warning('Unknown module for usage count', ['module' => $module]);
            return 0;
        }

        try {
            // Use tenant database connection
            return DB::connection('tenant')
                ->table($table)
                ->count();
        } catch (\Exception $e) {
            Log::error('Failed to get module usage count', [
                'tenant_id' => $tenant->id,
                'module' => $module,
                'table' => $table,
                'error' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Get plan limit for a specific module.
     *
     * @return int Limit count, or -1 for unlimited
     */
    public function getPlanLimit(Tenant $tenant, string $module): int
    {
        $module = strtolower($module);
        $plan = $this->getTenantPlan($tenant);

        if ($plan === null) {
            // No plan = no limit (allow everything) or block everything
            // Defaulting to allow for backward compatibility
            return self::UNLIMITED;
        }

        $column = self::MODULE_LIMIT_MAP[$module] ?? null;

        if ($column === null) {
            Log::warning('Unknown module for plan limit', ['module' => $module]);
            return self::UNLIMITED;
        }

        $limit = $plan->{$column} ?? null;

        // Null, 0, or negative values mean unlimited
        if ($limit === null || $limit <= 0) {
            return self::UNLIMITED;
        }

        return (int) $limit;
    }

    /**
     * Get storage usage in MB.
     */
    public function getStorageUsageMB(Tenant $tenant): int
    {
        $mediaService = app(MediaService::class);
        $usageBytes = $mediaService->getTenantStorageUsage($tenant);

        return (int) ceil($usageBytes / (1024 * 1024));
    }

    /**
     * Get comprehensive usage statistics for a tenant.
     *
     * @return array<string, array{used: int, limit: int, percentage: int, unlimited: bool}>
     */
    public function getUsageStats(Tenant $tenant): array
    {
        $stats = [];
        $modules = ['blog', 'product', 'service', 'portfolio', 'job', 'event', 'donation', 'knowledgebase', 'appointment', 'campaign'];

        foreach ($modules as $module) {
            $limit = $this->getPlanLimit($tenant, $module);
            $used = $this->getCurrentUsage($tenant, $module);
            $unlimited = $limit === self::UNLIMITED;

            $stats[$module] = [
                'used' => $used,
                'limit' => $unlimited ? null : $limit,
                'percentage' => $unlimited ? 0 : ($limit > 0 ? min(100, (int) round(($used / $limit) * 100)) : 0),
                'unlimited' => $unlimited,
            ];
        }

        // Add storage stats
        $storageLimit = $this->getPlanLimit($tenant, 'storage');
        $storageUsed = $this->getStorageUsageMB($tenant);
        $storageUnlimited = $storageLimit === self::UNLIMITED;

        $stats['storage'] = [
            'used' => $storageUsed,
            'limit' => $storageUnlimited ? null : $storageLimit,
            'unit' => 'MB',
            'percentage' => $storageUnlimited ? 0 : ($storageLimit > 0 ? min(100, (int) round(($storageUsed / $storageLimit) * 100)) : 0),
            'unlimited' => $storageUnlimited,
        ];

        return $stats;
    }

    /**
     * Get usage warnings for modules approaching their limits.
     *
     * @param int $warningThreshold Percentage threshold (default 80%)
     * @return array<string>
     */
    public function getUsageWarnings(Tenant $tenant, int $warningThreshold = 80): array
    {
        $warnings = [];
        $stats = $this->getUsageStats($tenant);

        foreach ($stats as $module => $stat) {
            if ($stat['unlimited']) {
                continue;
            }

            if ($stat['percentage'] >= 100) {
                $warnings[] = ucfirst($module) . ' limit reached (100%)';
            } elseif ($stat['percentage'] >= $warningThreshold) {
                $warnings[] = ucfirst($module) . ' limit almost reached (' . $stat['percentage'] . '%)';
            }
        }

        return $warnings;
    }

    /**
     * Get limit exceeded error response data.
     *
     * @return array{error: string, module: string, limit: int, current: int, message: string}
     */
    public function getLimitExceededData(Tenant $tenant, string $module): array
    {
        $limit = $this->getPlanLimit($tenant, $module);
        $current = $this->getCurrentUsage($tenant, $module);

        return [
            'error' => 'Plan limit reached',
            'module' => $module,
            'limit' => $limit,
            'current' => $current,
            'message' => "You have reached the maximum number of {$module} items ({$limit}) allowed by your plan. Upgrade your plan to create more.",
        ];
    }

    /**
     * Get the tenant's current plan.
     */
    private function getTenantPlan(Tenant $tenant): ?PricePlan
    {
        $paymentLog = $tenant->paymentLog;

        if ($paymentLog === null) {
            return null;
        }

        return $paymentLog->package;
    }

    /**
     * Get list of supported modules.
     *
     * @return array<string>
     */
    public static function getSupportedModules(): array
    {
        return array_keys(self::MODULE_LIMIT_MAP);
    }

    /**
     * Check if a module is supported for limit checking.
     */
    public static function isModuleSupported(string $module): bool
    {
        return isset(self::MODULE_LIMIT_MAP[strtolower($module)]);
    }
}
