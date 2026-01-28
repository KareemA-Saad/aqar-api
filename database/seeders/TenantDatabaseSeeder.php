<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Tenant\AdminSeed;
use Database\Seeders\Tenant\RolePermissionSeed;
use Database\Seeders\Tenant\ModuleData\LanguageSeed;
use Database\Seeders\Tenant\PaymentGatewayFieldsSeed;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * TenantDatabaseSeeder - Seeds base data and module-specific data for tenant databases
 *
 * Seeding Strategy:
 * - Core seeders (roles, admin, language) always run for all tenants
 * - Module seeders only run if the plan enables the corresponding feature
 * - Only base lookup data is seeded (no demo content/listings)
 *
 * @package Database\Seeders
 */
class TenantDatabaseSeeder extends Seeder
{
    /**
     * Map of feature prefixes to module seeder classes.
     * Features like "Properties 25" will match "properties" prefix.
     */
    private const MODULE_SEEDER_MAP = [
        // RealEstate Module
        'properties' => [
            \Database\Seeders\Tenant\ModuleData\RealEstate\PropertyTypeSeed::class,
            \Database\Seeders\Tenant\ModuleData\RealEstate\AmenitySeed::class,
            \Database\Seeders\Tenant\ModuleData\RealEstate\DeveloperSeed::class,
        ],
        'compounds' => [
            \Database\Seeders\Tenant\ModuleData\RealEstate\PropertyTypeSeed::class,
            \Database\Seeders\Tenant\ModuleData\RealEstate\AmenitySeed::class,
            \Database\Seeders\Tenant\ModuleData\RealEstate\DeveloperSeed::class,
        ],
        'realestate' => [
            \Database\Seeders\Tenant\ModuleData\RealEstate\PropertyTypeSeed::class,
            \Database\Seeders\Tenant\ModuleData\RealEstate\AmenitySeed::class,
            \Database\Seeders\Tenant\ModuleData\RealEstate\AreaSeed::class,
            \Database\Seeders\Tenant\ModuleData\RealEstate\DeveloperSeed::class,
        ],
        // Event Module
        'event' => [
            \Database\Seeders\Tenant\ModuleData\Event\EventCategorySeed::class,
        ],
        // Blog Module
        'blog' => [
            \Database\Seeders\Tenant\ModuleData\Blog\BlogCategorySeed::class,
        ],
        // Job Module
        'job' => [
            \Database\Seeders\Tenant\ModuleData\Job\JobCategorySeed::class,
        ],
        // Knowledgebase Module  
        'knowledgebase' => [
            \Database\Seeders\Tenant\ModuleData\Knowledgebase\KnowledgebaseCategorySeed::class,
        ],
        'article' => [
            \Database\Seeders\Tenant\ModuleData\Knowledgebase\KnowledgebaseCategorySeed::class,
        ],
        // Portfolio Module
        'portfolio' => [
            \Database\Seeders\Tenant\ModuleData\Portfolio\PortfolioCategorySeed::class,
        ],
        // Service Module
        'service' => [
            \Database\Seeders\Tenant\ModuleData\Service\ServiceCategorySeed::class,
        ],
        // Donation Module
        'donation' => [
            \Database\Seeders\Tenant\ModuleData\Donation\DonationCategorySeed::class,
            \Database\Seeders\Tenant\ModuleData\Donation\DonationActivityCategorySeed::class,
        ],
        // Gallery Module
        'gallery' => [
            \Database\Seeders\Tenant\ModuleData\Gallery\GalleryCategorySeed::class,
        ],
        // Appointment Module
        'appointment' => [
            \Database\Seeders\Tenant\ModuleData\Appointment\AppointmentDataSeed::class,
        ],
    ];

    /**
     * Run the tenant database seeder.
     */
    public function run(): void
    {
        $tenant = tenant();
        
        if (!$tenant) {
            Log::warning('TenantDatabaseSeeder: No tenant context found, skipping');
            return;
        }

        Log::info('TenantDatabaseSeeder: Starting seeding', ['tenant_id' => $tenant->id]);

        // Get plan features for this tenant
        $features = $this->getPlanFeatures($tenant);
        
        Log::info('TenantDatabaseSeeder: Plan features', [
            'tenant_id' => $tenant->id,
            'features' => $features,
        ]);

        // 1. Always run core seeders
        $this->runCoreSeeders();

        // 2. Run module seeders based on enabled features
        $this->runModuleSeeders($features);

        // 3. Run payment gateway seed if any payment gateway features enabled
        $this->runPaymentGatewaySeed($features);

        Log::info('TenantDatabaseSeeder: Completed', ['tenant_id' => $tenant->id]);
    }

    /**
     * Get plan features for the current tenant.
     *
     * @param mixed $tenant
     * @return array<string> Feature names (lowercase)
     */
    private function getPlanFeatures($tenant): array
    {
        $paymentLog = $tenant->paymentLog()->with(['package.planFeatures'])->first();

        // For trial or pending status with no features, allow all
        if (!$paymentLog) {
            Log::warning('TenantDatabaseSeeder: No payment log found', ['tenant_id' => $tenant->id]);
            return [];
        }

        // Check if trial - trials get all features
        if (in_array($paymentLog->status, ['trial', 'pending'])) {
            Log::info('TenantDatabaseSeeder: Trial/pending status, enabling all features', [
                'tenant_id' => $tenant->id,
                'status' => $paymentLog->status,
            ]);
            return array_keys(self::MODULE_SEEDER_MAP);
        }

        if (!$paymentLog->package) {
            return [];
        }

        // Get active features from plan
        $features = $paymentLog->package
            ->planFeatures()
            ->where('status', true)
            ->pluck('feature_name')
            ->map(fn($f) => strtolower(trim($f)))
            ->toArray();

        return $features;
    }

    /**
     * Run core seeders that apply to all tenants.
     */
    private function runCoreSeeders(): void
    {
        Log::info('TenantDatabaseSeeder: Running core seeders');

        // Roles and Permissions
        if (Schema::hasTable('permissions') && Schema::hasTable('roles')) {
            try {
                RolePermissionSeed::process_seeding();
                Log::info('TenantDatabaseSeeder: RolePermissionSeed completed');
            } catch (\Throwable $e) {
                Log::error('TenantDatabaseSeeder: RolePermissionSeed failed', ['error' => $e->getMessage()]);
            }
        }

        // Admin User
        if (Schema::hasTable('admins')) {
            try {
                AdminSeed::run();
                Log::info('TenantDatabaseSeeder: AdminSeed completed');
            } catch (\Throwable $e) {
                Log::error('TenantDatabaseSeeder: AdminSeed failed', ['error' => $e->getMessage()]);
            }
        }

        // Language
        try {
            LanguageSeed::run();
            Log::info('TenantDatabaseSeeder: LanguageSeed completed');
        } catch (\Throwable $e) {
            Log::error('TenantDatabaseSeeder: LanguageSeed failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Run module seeders based on enabled features.
     *
     * @param array<string> $features
     */
    private function runModuleSeeders(array $features): void
    {
        $seededClasses = [];

        foreach ($features as $feature) {
            // Extract feature prefix (e.g., "properties" from "Properties 25")
            $featurePrefix = $this->extractFeaturePrefix($feature);

            if (!isset(self::MODULE_SEEDER_MAP[$featurePrefix])) {
                continue;
            }

            foreach (self::MODULE_SEEDER_MAP[$featurePrefix] as $seederClass) {
                // Avoid running same seeder twice
                if (in_array($seederClass, $seededClasses, true)) {
                    continue;
                }

                if (!class_exists($seederClass)) {
                    Log::warning('TenantDatabaseSeeder: Seeder class not found', [
                        'class' => $seederClass,
                        'feature' => $feature,
                    ]);
                    continue;
                }

                try {
                    Log::info('TenantDatabaseSeeder: Running module seeder', [
                        'class' => $seederClass,
                        'feature' => $feature,
                    ]);

                    // Call static execute method if exists, otherwise instantiate and run
                    if (method_exists($seederClass, 'execute')) {
                        $seederClass::execute();
                    } elseif (method_exists($seederClass, 'run')) {
                        $seederClass::run();
                    } else {
                        $seeder = new $seederClass();
                        $seeder->run();
                    }

                    $seededClasses[] = $seederClass;
                    Log::info('TenantDatabaseSeeder: Module seeder completed', ['class' => $seederClass]);
                } catch (\Throwable $e) {
                    Log::error('TenantDatabaseSeeder: Module seeder failed', [
                        'class' => $seederClass,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        }
    }

    /**
     * Extract feature prefix from feature name.
     * "Properties 25" -> "properties"
     * "Blog" -> "blog"
     *
     * @param string $feature
     * @return string
     */
    private function extractFeaturePrefix(string $feature): string
    {
        // Split by space and take first word
        $parts = explode(' ', trim($feature));
        return strtolower($parts[0]);
    }

    /**
     * Run payment gateway seeder if needed.
     *
     * @param array<string> $features
     */
    private function runPaymentGatewaySeed(array $features): void
    {
        $paymentFeatures = ['stripe', 'paypal', 'razorpay', 'paystack', 'mollie', 'cashfree', 'payment'];

        $hasPaymentFeature = false;
        foreach ($features as $feature) {
            $prefix = $this->extractFeaturePrefix($feature);
            if (in_array($prefix, $paymentFeatures, true)) {
                $hasPaymentFeature = true;
                break;
            }
        }

        if ($hasPaymentFeature && Schema::hasTable('payment_gateways')) {
            try {
                PaymentGatewayFieldsSeed::execute();
                Log::info('TenantDatabaseSeeder: PaymentGatewayFieldsSeed completed');
            } catch (\Throwable $e) {
                Log::error('TenantDatabaseSeeder: PaymentGatewayFieldsSeed failed', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
