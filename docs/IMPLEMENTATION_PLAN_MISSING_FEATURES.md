# Implementation Plan: Missing Features & Enhancements

**Date**: January 19, 2026  
**Status**: Planning Phase  
**Priority**: High

---

## 📋 Overview

This document outlines **critical missing features** required to complete the multi-tenant SaaS platform. Focus is on **essential functionality only** to minimize frontend development effort. Payment integration is excluded as per client requirements.

---

## 🎯 Key Findings

### Current System Architecture
1. **Plans Control Modules, NOT Themes**
   - Each plan has permission columns: `blog_permission_feature`, `product_create_permission`, `service_permission_feature`, etc.
   - These are INTEGER limits (e.g., `20` = max 20 blog POSTS, `50` = max 50 products)
   - **Important**: Limits apply to CONTENT within ONE tenant, not number of tenants
   - Themes are **independent** - users choose ANY active theme regardless of plan

2. **Two Types of Plan Features**
   - **Module Permissions** (stored as columns): Control actual module limits
   - **Display Features** (PlanFeatures table): UI/marketing features shown to users

3. **Available Themes** (4 themes to seed)
   - `Theme-hotel-booking` - Hotel/accommodation businesses
   - `Theme-eCommerce` - Product/shop businesses  
   - `Theme-blog` - Blog/content businesses
   - `Theme-realestate` - Real estate businesses (coming soon)

---

## ✅ Priority 1: Subscription Enforcement

### Current State
- Plans have `has_trial`, `trial_days` fields
- PaymentLog tracks subscription start/end dates
- **No automatic expiration/suspension logic**

### Required Implementation

#### 1.1 Subscription Status Checker
**File**: `app/Services/SubscriptionService.php` (create new)

```php
class SubscriptionService
{
    public function isSubscriptionActive(Tenant $tenant): bool;
    public function getSubscriptionStatus(Tenant $tenant): string; // active|expired|trial|suspended
    public function getDaysUntilExpiry(Tenant $tenant): int;
    public function suspendTenant(Tenant $tenant, string $reason): void;
    public function reactivateTenant(Tenant $tenant): void;
}
```

#### 1.2 Middleware: CheckTenantSubscription
**File**: `app/Http/Middleware/CheckTenantSubscription.php` (create new)

**Purpose**: Block access to suspended/expired tenants

**Apply to**: All `/api/v1/tenant/{tenant}/*` routes

**Logic**:
- Check PaymentLog for active subscription
- If expired → Return 402 Payment Required
- If suspended → Return 403 Forbidden
- Allow grace period (configurable, e.g., 7 days)

#### 1.3 Scheduled Job: CheckExpiredSubscriptions
**File**: `app/Console/Commands/CheckExpiredSubscriptions.php` (create new)

**Schedule**: Daily at midnight (register in `app/Console/Kernel.php`)

**Actions**:
- Query all tenants with expired subscriptions (check PaymentLog end_date)
- Update `tenants.subscription_status` = 'expired'
- Optionally send warning email notifications
- Log suspension events to activity log

**Register in Kernel.php**:
```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('tenants:check-expired')
        ->daily()
        ->at('00:00');
}
```

#### 1.4 Database Changes
Add to `tenants` table:
```php
$table->enum('subscription_status', ['active', 'trial', 'expired', 'suspended'])->default('active');
$table->timestamp('suspended_at')->nullable();
```

---

## ✅ Priority 2: Usage Limits Enforcement

### Current State
- Plans have module limits stored as integers
- **No validation during CRUD operations**
- Users can exceed limits without restriction

### Required Implementation

#### 2.1 Limit Checker Service
**File**: `app/Services/PlanLimitService.php` (create new)

```php
class PlanLimitService
{
    public function canCreateBlog(Tenant $tenant): bool;
    public function canCreateProduct(Tenant $tenant): bool;
    public function canCreateService(Tenant $tenant): bool;
    // ... for each module
    
    public function getRemainingLimit(Tenant $tenant, string $module): int;
    public function getCurrentUsage(Tenant $tenant, string $module): int;
    public function getPlanLimit(PricePlan $plan, string $module): int;
}
```

#### 2.2 Middleware: CheckModuleLimit
**File**: `app/Http/Middleware/CheckModuleLimit.php` (create new)

**Purpose**: Validate limits before create operations

**Apply to**: Module creation endpoints
- `/api/v1/tenant/{tenant}/admin/blogs` POST
- `/api/v1/tenant/{tenant}/admin/products` POST
- `/api/v1/tenant/{tenant}/admin/services` POST
- etc.

**Logic**:
```php
// Extract module from route
$module = $this->getModuleFromRoute($request);

// Check limit
if (!$limitService->canCreate($tenant, $module)) {
    return response()->json([
        'error' => 'Plan limit reached',
        'module' => $module,
        'limit': $plan->getLimit($module),
        'current': $usage,
        'message': 'Upgrade your plan to create more items'
    ], 403);
}
```

#### 2.3 Module Limit Mapping
**File**: `config/modules.php` (enhance existing)

```php
'limits' => [
    'blog' => 'blog_permission_feature',
    'product' => 'product_create_permission',
    'service' => 'service_permission_feature',
    'donation' => 'donation_permission_feature',
    'job' => 'job_permission_feature',
    'event' => 'event_permission_feature',
    'knowledgebase' => 'knowledgebase_permission_feature',
    'campaign' => 'campaign_create_permission',
    'appointment' => 'appointment_permission_feature',
    'portfolio' => 'portfolio_permission_feature',
    'storage' => 'storage_permission_feature', // in bytes/MB
],
```

#### 2.4 Storage Limit Enforcement
**Special handling for media uploads**

**Enhance**: `app/Http/Controllers/Api/V1/MediaController.php`

```php
public function upload(Request $request): JsonResponse
{
    // Check storage limit BEFORE upload
    $tenant = tenancy()->tenant;
    $plan = $tenant->paymentLog->plan;
    $storageLimit = $plan->storage_permission_feature; // in MB
    $currentUsage = $this->mediaService->getTenantStorageUsage($tenant);
    
    if ($currentUsage + $fileSize > $storageLimit) {
        return $this->error('Storage limit exceeded. Upgrade plan.', 403);
    }
    
    // Proceed with upload...
}
```

#### 2.5 Add Usage Stats Endpoint
**File**: `app/Http/Controllers/Api/V1/Tenant/Admin/UsageStatsController.php` (create new)

```
GET /api/v1/tenant/{tenant}/admin/usage-stats

Response:
{
  "plan": {
    "name": "Basic Monthly",
    "price": 50
  },
  "limits": {
    "blogs": { "used": 15, "limit": 20, "percentage": 75 },
    "products": { "used": 45, "limit": 50, "percentage": 90 },
    "storage": { "used": 850, "limit": 1024, "unit": "MB", "percentage": 83 }
  },
  "warnings": [
    "Products limit almost reached (90%)",
    "Storage limit almost reached (83%)"
  ]
}
```

---

## ✅ Priority 3: Tenant Creation Abuse Prevention

### Current Risk
**Users can create unlimited tenants with same plan**

### Required Implementation

#### 5.1 Tenant Creation Limit
Add to `price_plans` table:
```php
$table->integer('max_tenants')->default(1); // How many tenants per subscription
```

#### 5.2 Validation in CreateTenantRequest
**Enhance**: `app/Http/Controllers/Api/V1/Landlord/UserDashboardController.php`

```php
public function createTenant(CreateTenantRequest $request): JsonResponse
{
    $user = auth('api_user')->user();
    $plan = PricePlan::find($request->plan_id);
    
    // Count user's active tenants with this plan
    $activeTenants = $user->tenants()
        ->whereHas('paymentLog', fn($q) => $q->where('package_id', $plan->id))
        ->where('subscription_status', 'active')
        ->count();
    
    if ($activeTenants >= $plan->max_tenants) {
        return $this->error(
            "You have reached the maximum number of tenants ({$plan->max_tenants}) for this plan.",
            403
        );
    }
    
    // Proceed with creation...
}
```

#### 5.3 Rate Limiting
**Add to**: `app/Providers/RouteServiceProvider.php`

```php
RateLimiter::for('tenant-creation', function (Request $request) {
    return Limit::perDay(5)->by($request->user()->id); // Max 5 tenants per day
});
```

Apply to route:
```php
Route::post('my-tenants', [UserDashboardController::class, 'createTenant'])
    ->middleware('throttle:tenant-creation');
```

---

## ✅ Priority 4: Theme Browsing Endpoints

### Current State
- Themes table exists
- No endpoint for users to browse available themes during tenant creation
- Need public endpoint to list active themes

### Required Implementation

#### 4.1 List Active Themes Endpoint
**File**: `app/Http/Controllers/Api/V1/Landlord/ThemeController.php` (create/enhance)

```
GET /api/v1/themes
Authentication: Required (api_user guard)

Response:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Hotel Booking Theme",
      "slug": "Theme-hotel-booking",
      "description": "Perfect for hotels, resorts, and accommodation businesses",
      "theme_code": "hotel-booking",
      "preview_image": "https://...",
      "is_available": true
    },
    {
      "id": 2,
      "title": "eCommerce Theme",
      "slug": "Theme-eCommerce",
      "description": "Ideal for online shops and product-based businesses",
      "theme_code": "ecommerce",
      "preview_image": "https://...",
      "is_available": true
    },
    // ... other themes
  ]
}
```

**Controller Method**:
```php
public function index(): JsonResponse
{
    $themes = Theme::where('status', 1)
        ->where('is_available', 1)
        ->select('id', 'title', 'slug', 'description', 'theme_code', 'preview_image')
        ->get();
    
    return $this->success($themes);
}
```

#### 4.2 Add Route
**File**: `routes/api.php`

```php
Route::middleware('auth:api_user')->group(function () {
    Route::get('themes', [ThemeController::class, 'index']);
});
```

#### 4.3 Database Enhancement (Optional)
Add preview image column to themes table:
```php
$table->string('preview_image')->nullable()->comment('Theme preview/screenshot URL');
```

---

## ✅ Priority 5: Theme Seeder

### Current State
- Themes table exists but may not be populated
- Need to seed 4 core themes for production

### Required Implementation

#### 5.1 Theme Seeder
**File**: `database/seeders/ThemeSeeder.php` (create/update)

```php
DB::table('themes')->insert([
    [
        'title' => 'Hotel Booking Theme',
        'slug' => 'Theme-hotel-booking',
        'description' => 'Perfect for hotels, resorts, and accommodation businesses',
        'status' => 1,
        'is_available' => 1,
        'theme_code' => 'hotel-booking',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'title' => 'eCommerce Theme',
        'slug' => 'Theme-eCommerce',
        'description' => 'Ideal for online shops and product-based businesses',
        'status' => 1,
        'is_available' => 1,
        'theme_code' => 'ecommerce',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'title' => 'Blog Theme',
        'slug' => 'Theme-blog',
        'description' => 'Clean design for blogs and content creators',
        'status' => 1,
        'is_available' => 1,
        'theme_code' => 'blog',
        'created_at' => now(),
        'updated_at' => now(),
    ],
    [
        'title' => 'Real Estate Theme',
        'slug' => 'Theme-realestate',
        'description' => 'Designed for property listings and real estate agencies',
        'status' => 1,
        'is_available' => 1,
        'theme_code' => 'realestate',
        'created_at' => now(),
        'updated_at' => now(),
    ],
]);
```

Run with: `php artisan db:seed --class=ThemeSeeder`

---

## 📊 Implementation Timeline (Revised - Minimal MVP)

### Phase 1: Critical Enforcement (Week 1)
- [ ] Add `max_tenants` column to `price_plans` table
- [ ] Update existing plans with max_tenants values (1, 1, 3, 5, 10)
- [ ] Add tenant creation validation in UserDashboardController
- [ ] Subscription expiration middleware (CheckTenantSubscription)
- [ ] Add `subscription_status` column to `tenants` table
- [ ] Create CheckExpiredSubscriptions command and schedule daily

### Integration Tests
- [ ] Create blog when at limit → 403 error with upgrade message
- [ ] Create 2nd tenant when max_tenants=1 → 403 error
- [ ] Upload file exceeding storage → 403 error
- [ ] Access expired tenant → 402 error
- [ ] Theme validation for non-existent theme → validation error

### Manual Testing Checklist
- [ ] Create tenant with each of the 4 themes
- [ ] Attempt to exceed blog post limit → Blocked with clear message
- [ ] Attempt to create 2nd tenant on Basic plan → Blocked
- [ ] Upgrade plan to Premium → Can now create up to 5 tenants
- [ ] Check usage stats endpoint shows correct percentages

---

## 📝 Implementation Notes

1. **Payment Integration**: Excluded - manual plan activation/upgrade assumed
2. **Max Tenants Logic**: Counts ALL active tenants for user, not per-plan
3. **Theme Seeding**: Run seeder after deployment to production
4. **Migration Strategy**: Add new columns with sensible defaults, no backfill needed initially
5. **Frontend Impact**: Minimal - only 2 new endpoints (usage-stats, themes list), existing endpoints enhanced
6. **Automated Suspension**: Scheduled command runs daily at midnight to check/update expired subscriptions

---

## 🚫 Removed from Original Plan (Non-Essential)

- ~~Public tenant discovery endpoint~~ (not needed for MVP - end customers access tenant directly by domain)
- ~~Theme recommendation endpoint~~ (simple theme list is sufficient)
- ~~Rate limiting on tenant creation~~ (replaced with max_tenants column)
- ~~Grace period complexity~~ (expired = blocked immediately)
- ~~Public listing flags on tenants~~ (not needed without public discovery)

---

## 🆕 New Endpoints Required

### Critical New Endpoints (Frontend Must Implement)

#### 1. Usage Stats Dashboard
```
GET /api/v1/tenant/{tenant}/admin/usage-stats
```
**Purpose**: Show tenant admin their current usage vs plan limits

**Response**:
```json
{
  "plan": {
    "name": "Basic Monthly",
    "max_tenants": 1
  },
  "limits": {
    "blogs": { "used": 15, "limit": 20, "percentage": 75 },
    "products": { "used": 45, "limit": 50, "percentage": 90 },
    "storage": { "used": 850, "limit": 1024, "unit": "MB", "percentage": 83 }
  }
}
```

#### 2. Browse Available Themes
```
GET /api/v1/themes
```
**Purpose**: List all active themes for user to choose during tenant creation

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Hotel Booking Theme",
      "slug": "Theme-hotel-booking",
      "description": "Perfect for hotels, resorts, and accommodation businesses",
      "preview_image": "https://..."
    }
  ]
}
```

---

## 🔗 Related Files

- **Models**: PricePlan, Tenant, PaymentLog, Theme
- **Services**: TenantService, UserService (create: PlanLimitService, SubscriptionService)
- **Middleware**: Create: CheckTenantSubscription, CheckModuleLimit
- **Seeders**: Create: ThemeSeeder
- **Config**: config/modules.php (enhance with limits mapping)

---

## ✅ Database Changes Summary

```sql
-- price_plans table
ALTER TABLE price_plans ADD COLUMN max_tenants INT DEFAULT 1 COMMENT 'Max tenants per plan';

-- tenants table  
ALTER TABLE tenants ADD COLUMN subscription_status ENUM('active','trial','expired','suspended') DEFAULT 'active';
ALTER TABLE tenants ADD COLUMN suspended_at TIMESTAMP NULL;

-- Update existing plans with suggested values
UPDATE price_plans SET max_tenants = 1 WHERE id IN (1, 2);  -- Free/Basic
UPDATE price_plans SET max_tenants = 3 WHERE id = 3;         -- Standard
-- Add more as needed for Premium/Enterprise plans
```

---

**Next Steps**: 
1. ✅ Review this updated plan
2. ⏭️ Approve changes
3. 🚀 Begin Phase 1 implementation (Week 1: Critical Enforcement)

---

## 📊 Implementation Timeline (Revised - Minimal MVP)

### Phase 1: Critical Enforcement (Week 1)
- [ ] Add `max_tenants` column to `price_plans` table
- [ ] Update existing plans with max_tenants values (1, 1, 3, 5, 10)
- [ ] Add tenant creation validation in UserDashboardController
- [ ] Subscription expiration middleware (CheckTenantSubscription)
- [ ] Add `subscription_status` column to `tenants` table
- [ ] Create CheckExpiredSubscriptions command and schedule daily
- [ ] Create CheckExpiredSubscriptions command and schedule daily

### Phase 2: Usage Limits (Week 2)
- [ ] Create PlanLimitService (canCreate, getRemainingLimit, getCurrentUsage)
- [ ] Create CheckModuleLimit middleware
- [ ] Add module limit mapping to config/modules.php
- [ ] Apply middleware to top 5 modules (Blog, Product, Service, Job, Event)
- [ ] Usage stats endpoint for tenant admin dashboard

### Phase 3: Data Setup & Theme Management (Week 3)
- [ ] Create ThemeController with index endpoint (GET /api/v1/themes)
- [ ] Add theme browsing route for authenticated users
- [ ] Create/update ThemeSeeder with 4 themes
- [ ] Add preview_image column to themes table (optional)
- [ ] Add theme validation in CreateTenantRequest
- [ ] Storage limit enforcement in MediaController
- [ ] Test all limits and enforcement

---

## 🧪 Testing Requirements

### Unit Tests
- [ ] PlanLimitService::canCreate() for each module
- [ ] SubscriptionService::isActive() with various scenarios
- [ ] Theme validation logic

### Integration Tests
- [ ] Create blog when at limit → 403 error with upgrade message
- [ ] Create 2nd tenant when max_tenants=1 → 403 error
- [ ] Upload file exceeding storage → 403 error
- [ ] Access expired tenant → 402 error
- [ ] Theme validation for non-existent theme → validation error

### Manual Testing Checklist
- [ ] Create tenant with each of the 4 themes
- [ ] Attempt to exceed blog post limit → Blocked with clear message
- [ ] Attempt to create 2nd tenant on Basic plan → Blocked
- [ ] Upgrade plan to Premium → Can now create up to 5 tenants
- [ ] Check usage stats endpoint shows correct percentages
- [ ] Automated suspension job runs and updates expired tenants

---

## 📝 Notes

1. **Payment Integration**: Excluded per client requirement - manual plan activation assumed
2. **Grace Period**: Configurable via `config('subscription.grace_period_days', 7)`
3. **Notifications**: Email templates needed for suspension warnings
4. **Migration Strategy**: Add new columns with default values, backfill existing data

---

## 🔗 Related Files

- **Models**: PricePlan, PlanFeature, Tenant, PaymentLog
- **Services**: TenantService, UserService
- **Middleware**: ResolveTenantFromToken, CheckTenantContext
- **Config**: config/modules.php, config/tenancy.php

---

**Next Steps**: Review plan with team → Prioritize phases → Begin Phase 1 implementation
**Users can create unlimited tenants - need to restrict based on plan**

### Required Implementation

#### 3.1 Tenant Creation Limit per Plan
Add to `price_plans` table:
```php
$table->integer('max_tenants')->default(1)->comment('Maximum tenants allowed per plan subscription');
```

**Recommended Values by Plan Tier**:
- **Free/Trial Plan**: `max_tenants = 1` (one test tenant)
- **Basic Plan**: `max_tenants = 1` (one production tenant)
- **Standard Plan**: `max_tenants = 3` (multiple business branches)
- **Premium Plan**: `max_tenants = 5` (enterprise with multiple properties)
- **Enterprise Plan**: `max_tenants = 10` (large organizations)

#### 3.2 Validation in CreateTenant::createTenant()`

```php
public function createTenant(CreateTenantRequest $request): JsonResponse
{
    $user = auth('api_user')->user();
    $plan = PricePlan::find($request->plan_id);
    
    // Count user's active tenants (across ALL their subscriptions)
    $activeTenants = $user->tenants()
        ->where('subscription_status', '!=', 'expired')
        ->count();
    
    // Check against plan's max_tenants limit
    if ($activeTenants >= $plan->max_tenants) {
        return $this->error(
            "You have reached the maximum number of tenants ({$plan->max_tenants}) for this plan. Upgrade to create more.",
            403
        );
    }
    
    // Proceed with creation...
}
```

**Note**: Rate limiting removed - max_tenants column is sufficient control