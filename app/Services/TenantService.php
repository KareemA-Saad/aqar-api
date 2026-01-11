<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\CreateTenantDatabase;
use App\Models\Domain;
use App\Models\PaymentLog;
use App\Models\PricePlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\ManagesDatabaseUsers;
use Stancl\Tenancy\Tenancy;

/**
 * Tenant Service
 *
 * Handles all tenant-related business logic including:
 * - Tenant CRUD operations
 * - Database creation and management
 * - Migration and seeding
 * - Tenant switching
 */
final class TenantService
{
    public function __construct(
        private readonly Tenancy $tenancy,
    ) {}

    /**
     * Create a new tenant for a user.
     *
     * @param User $user The user who will own the tenant
     * @param PricePlan $plan The subscription plan
     * @param array{subdomain?: string, theme?: string, theme_code?: string} $data Additional tenant data
     * @param bool $async Whether to create database asynchronously
     * @return Tenant
     */
    public function createTenant(User $user, PricePlan $plan, array $data = [], bool $async = true): Tenant
    {
        return DB::transaction(function () use ($user, $plan, $data, $async) {
            // Generate tenant ID (subdomain or UUID)
            $tenantId = $this->generateTenantId($data['subdomain'] ?? null);

            // Create tenant record
            $tenant = Tenant::create([
                'id' => $tenantId,
                'user_id' => $user->id,
                'theme_slug' => $data['theme'] ?? null,
                'theme_code' => $data['theme_code'] ?? null,
                'instruction_status' => false,
            ]);

            // Create domain if subdomain provided
            if (!empty($data['subdomain'])) {
                $this->createDomain($tenant, $data['subdomain']);
            }

            // Create initial payment log
            $this->createPaymentLog($tenant, $user, $plan, $data);

            // Update user's subdomain flag
            $user->update(['has_subdomain' => true]);

            // Create database (sync or async)
            if ($async) {
                CreateTenantDatabase::dispatch($tenant);
            } else {
                $this->setupTenantDatabase($tenant);
            }

            Log::info('Tenant created', [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'async' => $async,
            ]);

            return $tenant;
        });
    }

    /**
     * Update tenant settings.
     *
     * @param Tenant $tenant
     * @param array{theme?: string, theme_code?: string, instruction_status?: bool} $data
     * @return Tenant
     */
    public function updateTenant(Tenant $tenant, array $data): Tenant
    {
        $tenant->update([
            'theme_slug' => $data['theme'] ?? $tenant->theme_slug,
            'theme_code' => $data['theme_code'] ?? $tenant->theme_code,
            'instruction_status' => $data['instruction_status'] ?? $tenant->instruction_status,
        ]);

        Log::info('Tenant updated', ['tenant_id' => $tenant->id]);

        return $tenant->fresh();
    }

    /**
     * Delete a tenant and its resources.
     *
     * @param Tenant $tenant
     * @return bool
     */
    public function deleteTenant(Tenant $tenant): bool
    {
        return DB::transaction(function () use ($tenant) {
            $userId = $tenant->user_id;
            $tenantId = $tenant->id;

            // Delete related records
            PaymentLog::where('tenant_id', $tenantId)->delete();

            // Delete domains
            $tenant->domains()->delete();

            // Delete tenant (will also delete database if auto_delete is enabled)
            $tenant->delete();

            // Update user's subdomain flag if no more tenants
            $remainingTenants = Tenant::where('user_id', $userId)->count();
            if ($remainingTenants === 0) {
                User::where('id', $userId)->update(['has_subdomain' => false]);
            }

            Log::info('Tenant deleted', ['tenant_id' => $tenantId, 'user_id' => $userId]);

            return true;
        });
    }

    /**
     * Setup tenant database (create, migrate, seed).
     *
     * @param Tenant $tenant
     * @return void
     */
    public function setupTenantDatabase(Tenant $tenant): void
    {
        $this->createDatabase($tenant);
        $this->runTenantMigrations($tenant);

        if (config('tenancy.database.auto_seed', false)) {
            $this->seedTenantData($tenant);
        }
    }

    /**
     * Create tenant database.
     *
     * @param Tenant $tenant
     * @return void
     * @throws \Exception
     */
    public function createDatabase(Tenant $tenant): void
    {
        $database = $tenant->database();
        $manager = $database->manager();

        if ($manager->databaseExists($database->getName())) {
            Log::warning('Database already exists', [
                'tenant_id' => $tenant->id,
                'database' => $database->getName(),
            ]);
            return;
        }

        // Check permissions
        if ($manager instanceof ManagesDatabaseUsers) {
            if ($manager->userExists($database->getUsername())) {
                throw new \Exception("Database user already exists: {$database->getUsername()}");
            }
        }

        $manager->createDatabase($tenant);

        Log::info('Tenant database created', [
            'tenant_id' => $tenant->id,
            'database' => $database->getName(),
        ]);
    }

    /**
     * Check if tenant database exists.
     *
     * @param Tenant $tenant
     * @return bool
     */
    public function databaseExists(Tenant $tenant): bool
    {
        try {
            $database = $tenant->database();
            return $database->manager()->databaseExists($database->getName());
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Run migrations for a tenant (base + enabled modules only).
     *
     * @param Tenant $tenant
     * @return void
     */
    public function runTenantMigrations(Tenant $tenant): void
    {
        if (!$this->databaseExists($tenant)) {
            Log::warning('Cannot migrate: database does not exist', ['tenant_id' => $tenant->id]);
            return;
        }

        // Base migrations always run
        $migrationPaths = [
            database_path('migrations/tenant'),
        ];

        // Get enabled modules from plan features
        $enabledModules = $this->getEnabledModulesForTenant($tenant);

        if (config('modules.log_enabled_modules', true)) {
            Log::info('Running migrations for tenant', [
                'tenant_id' => $tenant->id,
                'enabled_modules' => $enabledModules,
            ]);
        }

        // Add module migration paths
        foreach ($enabledModules as $moduleName) {
            $module = \Nwidart\Modules\Facades\Module::find($moduleName);
            if (!$module) {
                continue;
            }
            $modulePath = module_path($moduleName, 'Database/Migrations');

            if (is_dir($modulePath)) {
                $migrationPaths[] = $modulePath;

                if (config('modules.log_migration_paths', false)) {
                    Log::debug('Added module migrations', [
                        'module' => $moduleName,
                        'path' => $modulePath,
                    ]);
                }
            } else {
                if (config('modules.validate_migration_paths', true)) {
                    Log::warning('Module migration path not found', [
                        'module' => $moduleName,
                        'expected_path' => $modulePath,
                    ]);
                }
            }
        }

        // Run migrations with all paths
        Artisan::call('tenants:migrate', [
            '--tenants' => [$tenant->id],
            '--force' => true,
            '--path' => $migrationPaths,
            '--realpath' => true,
        ]);

        Log::info('Tenant migrations completed', [
            'tenant_id' => $tenant->id,
            'modules' => $enabledModules,
            'total_paths' => count($migrationPaths),
        ]);
    }

    /**
     * Seed initial data for a tenant.
     *
     * @param Tenant $tenant
     * @return void
     */
    public function seedTenantData(Tenant $tenant): void
    {
        if (!$this->databaseExists($tenant)) {
            Log::warning('Cannot seed: database does not exist', ['tenant_id' => $tenant->id]);
            return;
        }

        Artisan::call('tenants:seed', [
            '--tenants' => [$tenant->id],
            '--force' => true,
        ]);

        Log::info('Tenant seeding completed', ['tenant_id' => $tenant->id]);
    }

    /**
     * Switch to tenant database context.
     *
     * @param Tenant $tenant
     * @return void
     */
    public function switchTenantDatabase(Tenant $tenant): void
    {
        $this->tenancy->initialize($tenant);

        Log::debug('Switched to tenant database', ['tenant_id' => $tenant->id]);
    }

    /**
     * End tenant database context and return to central.
     *
     * @return void
     */
    public function endTenantContext(): void
    {
        $this->tenancy->end();

        Log::debug('Ended tenant context');
    }

    /**
     * Get current tenant.
     *
     * @return Tenant|null
     */
    public function getCurrentTenant(): ?Tenant
    {
        return $this->tenancy->tenant;
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
     * Get all tenants for a user.
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserTenants(User $user)
    {
        return $user->tenants()
            ->with(['domains', 'paymentLog'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get tenant by ID with access check.
     *
     * @param string $tenantId
     * @param User|null $user If provided, checks ownership
     * @return Tenant|null
     */
    public function getTenant(string $tenantId, ?User $user = null): ?Tenant
    {
        $query = Tenant::with(['domains', 'paymentLog', 'user']);

        if ($user) {
            $query->where('user_id', $user->id);
        }

        return $query->find($tenantId);
    }

    /**
     * Generate new token with tenant context.
     *
     * @param User $user
     * @param Tenant $tenant
     * @return array{token: string, expires_at: string}
     */
    public function generateTenantToken(User $user, Tenant $tenant): array
    {
        // Verify user owns tenant
        if ($tenant->user_id !== $user->id) {
            throw new \InvalidArgumentException('User does not own this tenant');
        }

        $expiresAt = now()->addDays(7);

        $abilities = [
            'user:read',
            'user:write',
            'tenants:read',
            'tenants:write',
            "tenant:{$tenant->id}",
        ];

        $token = $user->createToken(
            name: "tenant-{$tenant->id}-token",
            abilities: $abilities,
            expiresAt: $expiresAt
        );

        return [
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toISOString(),
        ];
    }

    /**
     * Generate tenant ID from subdomain or UUID.
     *
     * @param string|null $subdomain
     * @return string
     */
    private function generateTenantId(?string $subdomain): string
    {
        if ($subdomain) {
            return Str::slug($subdomain, '-');
        }

        return (string) Str::uuid();
    }

    /**
     * Create domain for tenant.
     *
     * @param Tenant $tenant
     * @param string $subdomain
     * @return Domain
     */
    private function createDomain(Tenant $tenant, string $subdomain): Domain
    {
        $centralDomain = config('tenancy.central_domains.0', 'localhost');
        $domain = $subdomain . '.' . $centralDomain;

        return $tenant->domains()->create(['domain' => $domain]);
    }

    /**
     * Create initial payment log for tenant.
     *
     * @param Tenant $tenant
     * @param User $user
     * @param PricePlan $plan
     * @param array $data
     * @return PaymentLog
     */
    private function createPaymentLog(Tenant $tenant, User $user, PricePlan $plan, array $data): PaymentLog
    {
        $startDate = now();
        $expireDate = $this->calculateExpireDate($plan, $startDate);

        return PaymentLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'package_id' => $plan->id,
            'package_name' => $plan->title,
            'package_price' => $plan->price,
            'status' => $plan->price == 0 ? 'complete' : 'pending',
            'payment_status' => $plan->price == 0 ? 'complete' : 'pending',
            'is_renew' => false,
            'track' => Str::random(20),
            'start_date' => $startDate,
            'expire_date' => $expireDate,
            'theme' => $data['theme'] ?? null,
            'assign_status' => true,
        ]);
    }

    /**
     * Calculate subscription expiration date.
     *
     * @param PricePlan $plan
     * @param \Carbon\Carbon $startDate
     * @return \Carbon\Carbon|null
     */
    private function calculateExpireDate(PricePlan $plan, $startDate): ?\Carbon\Carbon
    {
        // Plan types: 0 = monthly, 1 = yearly, 2 = lifetime, 3 = custom
        return match ($plan->type) {
            0 => $startDate->copy()->addMonth(),
            1 => $startDate->copy()->addYear(),
            2 => null, // Lifetime
            default => $startDate->copy()->addMonth(),
        };
    }

    /**
     * Get enabled modules for a tenant based on plan features.
     *
     * @param Tenant $tenant
     * @return array<string> Module names
     */
    private function getEnabledModulesForTenant(Tenant $tenant): array
    {
        // Get payment log with package and features
        $paymentLog = $tenant->paymentLog()
            ->with(['package.planFeatures'])
            ->first();

        if (!$paymentLog || !$paymentLog->package) {
            Log::warning('No payment log or package found for tenant', [
                'tenant_id' => $tenant->id,
            ]);
            // Return only core modules if no plan
            return config('modules.core_modules', []);
        }

        // Handle trial plans
        if (in_array($paymentLog->status, ['trial', 'pending'])) {
            return $this->getTrialModules();
        }

        // Get feature names from plan (only active features)
        $features = $paymentLog->package
            ->planFeatures()
            ->where('status', true)
            ->pluck('feature_name')
            ->toArray();

        // Map features to modules
        $modules = $this->mapFeaturesToModules($features);

        if (config('modules.log_enabled_modules', true)) {
            Log::info('Enabled modules determined for tenant', [
                'tenant_id' => $tenant->id,
                'plan_id' => $paymentLog->package->id,
                'features' => $features,
                'modules' => $modules,
            ]);
        }

        return $modules;
    }

    /**
     * Get modules for trial plans.
     *
     * @return array<string>
     */
    private function getTrialModules(): array
    {
        $behavior = config('modules.trial_modules', 'all');

        return match ($behavior) {
            'all' => array_merge(
                config('modules.core_modules', []),
                array_values(array_filter(
                    config('modules.feature_module_map', []),
                    fn($module) => $module !== null
                ))
            ),
            'core' => config('modules.core_modules', []),
            default => config('modules.core_modules', []),
        };
    }

    /**
     * Map feature names to module names.
     *
     * @param array<string> $features
     * @return array<string>
     */
    private function mapFeaturesToModules(array $features): array
    {
        $featureMap = config('modules.feature_module_map', []);
        $modules = config('modules.core_modules', []);

        foreach ($features as $feature) {
            $featureLower = strtolower(trim($feature));

            if (isset($featureMap[$featureLower])) {
                $moduleName = $featureMap[$featureLower];

                // Skip null values (features that use base tables only)
                if ($moduleName !== null && !in_array($moduleName, $modules, true)) {
                    $modules[] = $moduleName;
                }
            }
        }

        // Remove duplicates and re-index
        return array_values(array_unique($modules));
    }

    /**
     * Run migrations for newly enabled modules (plan upgrade).
     *
     * @param Tenant $tenant
     * @param array<string> $newModules Module names to migrate
     * @return void
     */
    public function runModuleMigrationsForUpgrade(Tenant $tenant, array $newModules): void
    {
        if (!$this->databaseExists($tenant)) {
            Log::warning('Cannot migrate for upgrade: database does not exist', [
                'tenant_id' => $tenant->id,
            ]);
            return;
        }

        if (empty($newModules)) {
            Log::info('No new modules to migrate for upgrade', ['tenant_id' => $tenant->id]);
            return;
        }

        $migrationPaths = [];

        foreach ($newModules as $moduleName) {
            $modulePath = module_path($moduleName, 'Database/Migrations');

            if (is_dir($modulePath)) {
                $migrationPaths[] = $modulePath;
            } else {
                Log::warning('Module migration path not found for upgrade', [
                    'tenant_id' => $tenant->id,
                    'module' => $moduleName,
                    'expected_path' => $modulePath,
                ]);
            }
        }

        if (!empty($migrationPaths)) {
            Artisan::call('tenants:migrate', [
                '--tenants' => [$tenant->id],
                '--force' => true,
                '--path' => $migrationPaths,
                '--realpath' => true,
            ]);

            Log::info('Module migrations run for plan upgrade', [
                'tenant_id' => $tenant->id,
                'new_modules' => $newModules,
                'paths_count' => count($migrationPaths),
            ]);
        }
    }

    /**
     * Get list of modules that would be enabled for a given plan.
     *
     * @param PricePlan $plan
     * @return array<string> Module names
     */
    public function getModulesForPlan(PricePlan $plan): array
    {
        $features = $plan->planFeatures()
            ->where('status', true)
            ->pluck('feature_name')
            ->toArray();

        return $this->mapFeaturesToModules($features);
    }
}

