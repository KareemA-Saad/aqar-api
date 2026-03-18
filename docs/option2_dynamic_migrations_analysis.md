# Option 2: Dynamic Feature-Based Module Migrations - Analysis

## Current State Assessment

### ✅ What's Already in Place

1. **nwidart/laravel-modules** (v10.0) - Installed and configured
   - `module_path()` helper function available
   - Modules directory structure exists
   - 18 modules with migrations

2. **Tenant Model** - Has required relationships
   - `paymentLog()` - HasOne relationship to latest payment
   - `paymentLogs()` - HasMany relationship to all payments
   - Properly configured with Stancl Tenancy

3. **PaymentLog Model** - Has package relationship
   - `package()` - BelongsTo PricePlan
   - Contains all subscription data

4. **PricePlan Model** - Has features relationship
   - `planFeatures` - HasMany relationship
   - Feature columns: blog_permission_feature, product_permission_feature, etc.

5. **PlanFeature Model** - Stores feature names
   - `feature_name` column stores feature identifiers
   - Linked to price plans

6. **TenantService** - Migration method exists
   - `runTenantMigrations()` method ready to be enhanced
   - Database management methods in place

### ❌ What's Missing for Option 2

1. **Feature-to-Module Mapping** - Not implemented
2. **Dynamic Migration Path Building** - Not implemented
3. **Plan Upgrade Handler** - Not implemented
4. **Module Migration Tracking** - Not implemented

---

## Implementation Plan for Option 2

### Phase 1: Add Feature-to-Module Mapping

Create a configuration file to map plan features to module names:

```php
// config/modules.php
return [
    'feature_module_map' => [
        'blog' => 'Blog',
        'donation' => 'Donation',
        'event' => 'Event',
        'job' => 'Job',
        'appointment' => 'Appointment',
        'eCommerce' => 'Product',
        'knowledgebase' => 'Knowledgebase',
        'portfolio' => 'Portfolio',
        'service' => 'Service',
        'gallery' => 'ImageGallery',
        'advertisement' => 'Blog', // Part of Blog module
        'brand' => 'Attributes',
        'testimonial' => null, // Uses base tables
        'faq' => null, // Uses base tables
        'wedding_price_plan' => null, // Uses base tables
    ],
    
    // Always-enabled modules (core functionality)
    'core_modules' => [
        'Attributes',
        'Badge',
        'Campaign',
        'CouponManage',
        'CountryManage',
        'Inventory',
        'ShippingModule',
        'Wallet',
    ],
];
```

### Phase 2: Enhance TenantService

Add new methods to `app/Services/TenantService.php`:

```php
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
        return config('modules.core_modules', []);
    }
    
    // Get feature names from plan
    $features = $paymentLog->package
        ->planFeatures
        ->where('status', true)
        ->pluck('feature_name')
        ->toArray();
    
    // Map features to modules
    $featureMap = config('modules.feature_module_map', []);
    $modules = config('modules.core_modules', []);
    
    foreach ($features as $feature) {
        $featureLower = strtolower($feature);
        
        if (isset($featureMap[$featureLower])) {
            $moduleName = $featureMap[$featureLower];
            
            // Skip null values (features that use base tables)
            if ($moduleName !== null && !in_array($moduleName, $modules)) {
                $modules[] = $moduleName;
            }
        }
    }
    
    Log::info('Enabled modules determined for tenant', [
        'tenant_id' => $tenant->id,
        'features' => $features,
        'modules' => $modules,
    ]);
    
    return $modules;
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
    
    Log::info('Running migrations for tenant', [
        'tenant_id' => $tenant->id,
        'enabled_modules' => $enabledModules,
    ]);
    
    // Add module migration paths
    foreach ($enabledModules as $moduleName) {
        $modulePath = module_path($moduleName, 'Database/Migrations');
        
        if (is_dir($modulePath)) {
            $migrationPaths[] = $modulePath;
            Log::debug('Added module migrations', [
                'module' => $moduleName,
                'path' => $modulePath,
            ]);
        } else {
            Log::warning('Module migration path not found', [
                'module' => $moduleName,
                'expected_path' => $modulePath,
            ]);
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
 * Run migrations for newly enabled modules (plan upgrade).
 *
 * @param Tenant $tenant
 * @param array<string> $newModules Module names to migrate
 * @return void
 */
public function runModuleMigrationsForUpgrade(Tenant $tenant, array $newModules): void
{
    if (!$this->databaseExists($tenant)) {
        Log::warning('Cannot migrate: database does not exist', ['tenant_id' => $tenant->id]);
        return;
    }

    if (empty($newModules)) {
        Log::info('No new modules to migrate', ['tenant_id' => $tenant->id]);
        return;
    }

    $migrationPaths = [];
    
    foreach ($newModules as $moduleName) {
        $modulePath = module_path($moduleName, 'Database/Migrations');
        
        if (is_dir($modulePath)) {
            $migrationPaths[] = $modulePath;
        } else {
            Log::warning('Module migration path not found for upgrade', [
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
    $features = $plan->planFeatures
        ->where('status', true)
        ->pluck('feature_name')
        ->toArray();
    
    $featureMap = config('modules.feature_module_map', []);
    $modules = config('modules.core_modules', []);
    
    foreach ($features as $feature) {
        $featureLower = strtolower($feature);
        
        if (isset($featureMap[$featureLower])) {
            $moduleName = $featureMap[$featureLower];
            
            if ($moduleName !== null && !in_array($moduleName, $modules)) {
                $modules[] = $moduleName;
            }
        }
    }
    
    return $modules;
}
```

### Phase 3: Update SubscriptionService for Plan Upgrades

Add method to handle plan changes in `app/Services/SubscriptionService.php`:

```php
/**
 * Handle plan upgrade/change and run new module migrations.
 *
 * @param Tenant $tenant
 * @param PricePlan $oldPlan
 * @param PricePlan $newPlan
 * @return void
 */
private function handlePlanChange(Tenant $tenant, PricePlan $oldPlan, PricePlan $newPlan): void
{
    $tenantService = app(TenantService::class);
    
    // Get modules for both plans
    $oldModules = $tenantService->getModulesForPlan($oldPlan);
    $newModules = $tenantService->getModulesForPlan($newPlan);
    
    // Find newly enabled modules
    $addedModules = array_diff($newModules, $oldModules);
    
    if (!empty($addedModules)) {
        Log::info('Plan upgrade detected, running new module migrations', [
            'tenant_id' => $tenant->id,
            'old_plan' => $oldPlan->id,
            'new_plan' => $newPlan->id,
            'added_modules' => $addedModules,
        ]);
        
        $tenantService->runModuleMigrationsForUpgrade($tenant, $addedModules);
    }
}
```

---

## Comparison: Current vs Option 2

### Current Implementation (Static)
```
Subscription Flow:
User Subscribes → Tenant Created → Database Created
    ↓
Artisan::call('tenants:migrate', [
    '--tenants' => [$tenant->id],
    '--force' => true,
])
    ↓
Only runs: database/migrations/tenant/ (47 files)
Module migrations: SKIPPED (130+ files)
```

### Option 2 Implementation (Dynamic)
```
Subscription Flow:
User Subscribes → Tenant Created → Database Created
    ↓
Get Plan Features → Map to Modules
    ↓
Build Migration Paths:
  - database/migrations/tenant/ (always)
  - Core modules (always)
  - Feature-based modules (conditional)
    ↓
Artisan::call('tenants:migrate', [
    '--tenants' => [$tenant->id],
    '--force' => true,
    '--path' => $dynamicPaths,
])
    ↓
Only relevant tables created
```

---

## Benefits of Option 2 for Production

### 1. Resource Efficiency
- **Current**: Creates 177 tables (47 base + 130 modules) for ALL tenants
- **Option 2**: Creates only needed tables (e.g., 60-80 tables for basic plan)
- **Savings**: 50-60% reduction in database size per tenant

### 2. Security
- **Current**: All module tables exist, blocked only by middleware
- **Option 2**: Tables don't exist if feature not in plan
- **Benefit**: Physical data isolation, not just logical

### 3. Scalability
- **Current**: Every tenant = full database overhead
- **Option 2**: Database size scales with plan tier
- **Impact**: 1000 tenants = significant storage/performance difference

### 4. Auditability
- **Current**: Hard to know which modules are "active"
- **Option 2**: Database schema reflects plan features
- **Benefit**: Clear audit trail, easier debugging

### 5. Plan Management
- **Current**: Plan upgrades don't affect database
- **Option 2**: Plan upgrades run new migrations automatically
- **Benefit**: True feature enablement, not just access control

---

## Edge Cases Handled

### 1. Trial Plans
```php
// In getEnabledModulesForTenant()
if ($paymentLog->status === 'trial') {
    // Include all modules for trial
    return array_merge(
        config('modules.core_modules', []),
        array_filter(config('modules.feature_module_map', []))
    );
}
```

### 2. No Payment Log
```php
// Return only core modules
return config('modules.core_modules', []);
```

### 3. Plan Downgrade
```php
// Tables remain (can't drop safely)
// Access blocked by CheckFeaturePermission middleware
// Future: Add table archiving/cleanup job
```

### 4. Module Not Found
```php
// Log warning, skip gracefully
Log::warning('Module migration path not found', [
    'module' => $moduleName,
]);
```

---

## Testing Strategy

### 1. Unit Tests
- Test feature-to-module mapping
- Test module path building
- Test plan comparison logic

### 2. Integration Tests
- Create tenant with basic plan → verify tables
- Upgrade plan → verify new tables created
- Downgrade plan → verify access blocked

### 3. Performance Tests
- Compare database size: static vs dynamic
- Measure migration time differences
- Test with 100+ tenants

---

## Recommendation: Implement Option 2

### Why?
1. ✅ All dependencies in place (nwidart/laravel-modules, relationships)
2. ✅ Professional SaaS pattern
3. ✅ Significant resource savings
4. ✅ Better security posture
5. ✅ Easier to maintain and audit

### Implementation Effort
- **Time**: 4-6 hours
- **Risk**: Low (additive changes, no breaking changes)
- **Testing**: 2-3 hours

### Rollout Plan
1. Create config/modules.php
2. Add methods to TenantService
3. Test with new tenant creation
4. Test with plan upgrades
5. Monitor logs for any issues
6. Deploy to production

---

## Next Steps

1. **Review this analysis** - Confirm approach
2. **Create config/modules.php** - Define mappings
3. **Update TenantService** - Add dynamic logic
4. **Test thoroughly** - Multiple scenarios
5. **Deploy** - Monitor closely

Ready to proceed with implementation?
