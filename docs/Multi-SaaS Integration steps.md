
# 🚀 AQAR API - Frontend Integration Guide

> **Multi-Tenant SaaS Platform** - Complete API Integration Reference

## 📋 Overview

This guide provides step-by-step instructions for frontend teams to integrate with the AQAR Multi-Tenant SaaS API. 

**⚠️ IMPORTANT:** This is a Multi-Tenant SaaS system. Build the **Main Platform (Landlord)** first, then the **Tenant Storefronts**.

| Environment | Base URL |
|-------------|----------|
| Development | `http://localhost:8000/api/v1` |
| Production | `https://api.aqar.com/api/v1` |
| Swagger Docs | `/api/documentation` |

---

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    MAIN PLATFORM (Landlord)                      │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │ Landing Page│  │ Admin Panel │  │ User Dashboard          │  │
│  │ (Public)    │  │ (Super Admin)│  │ (Tenant Owners/Users)   │  │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    TENANT STOREFRONTS                            │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐  │
│  │ Tenant A Store  │  │ Tenant B Store  │  │ Tenant C Store  │  │
│  │ (E-commerce)    │  │ (E-commerce)    │  │ (E-commerce)    │  │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 🔑 Authentication System

### Three User Types

| Guard | User Type | Where They Login | Purpose |
|-------|-----------|------------------|---------|
| `sanctum_admin` | Platform Admins | Main Admin Panel | Manage entire platform |
| `sanctum_user` | Users/Landlords | Main Platform | Own & manage tenants |
| `sanctum_tenant_user` | Customers | Tenant Storefronts | Shop within a tenant |

---

# 📦 PART 1: MAIN PLATFORM (Build First)

> Build these features for the main SaaS platform before any tenant features.

---

## 🏥 Step 1: Health Check & Public Endpoints

**Start here to verify API connectivity.**

### Health Check
**Tag:** `Health Check`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/health` | GET | ❌ | API health status |

### Public Languages
**Tag:** `Public Languages`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/languages` | GET | ❌ | List available languages |
| `/languages/current` | GET | ❌ | Get current language |
| `/languages/{code}/translations` | GET | ❌ | Get translations for language |

### Public Price Plans
**Tag:** `Price Plans (Public)`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/plans` | GET | ❌ | List all active plans |
| `/plans/{slug}` | GET | ❌ | Get plan details |
| `/plans/compare` | GET | ❌ | Plan comparison matrix |

---

## 🔐 Step 2: User Authentication (Landlord/Tenant Owners)

**Tag:** `User Authentication`

> Users who register here will own/manage tenants (storefronts).

### Public Endpoints (No Auth)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/auth/register` | POST | Register new user |
| `/auth/login` | POST | Login with email/password |
| `/auth/forgot-password` | POST | Request password reset email |
| `/auth/reset-password` | POST | Reset password with token |
| `/auth/social-login` | POST | Social login (Google/Facebook) |
| `/auth/2fa/verify` | POST | Verify 2FA code during login |

### Protected Endpoints (Requires Auth)

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/auth/logout` | POST | Logout (revoke token) |
| `/auth/me` | GET | Get current user profile |
| `/auth/refresh-token` | POST | Refresh authentication token |
| `/auth/verify-email` | POST | Verify email address |
| `/auth/resend-verification` | POST | Resend verification email |

### Login Flow

```json
// Request
POST /api/v1/auth/login
{
  "email": "user@example.com",
  "password": "yourpassword"
}

// Response (Success)
{
  "success": true,
  "data": {
    "token": "1|abc123...",
    "user": { "id": 1, "name": "John", "email": "..." },
    "requires_2fa": false
  }
}

// Response (2FA Required)
{
  "success": true,
  "data": {
    "requires_2fa": true,
    "two_factor_token": "temp-token-for-2fa"
  }
}
```

---

## 🔒 Step 3: Two-Factor Authentication (2FA)

**Tag:** `Two-Factor Authentication`

> Optional security feature for users.

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/2fa/status` | GET | ✅ | Check if 2FA is enabled |
| `/2fa/setup` | POST | ✅ | Get QR code for setup |
| `/2fa/enable` | POST | ✅ | Enable 2FA with OTP code |
| `/2fa/disable` | POST | ✅ | Disable 2FA |
| `/2fa/devices` | GET | ✅ | List trusted devices |
| `/2fa/devices/{deviceId}` | DELETE | ✅ | Remove trusted device |

### 2FA Setup Flow
1. User calls `/2fa/setup` → Gets QR code
2. User scans QR with Google Authenticator
3. User calls `/2fa/enable` with OTP code
4. On future logins, after password → call `/auth/2fa/verify` with OTP

---

## 👤 Step 4: User Dashboard & Profile

**Tag:** `User Dashboard`

> Main dashboard for logged-in users (tenant owners).

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/dashboard` | GET | ✅ | Dashboard statistics |
| `/profile` | GET | ✅ | Get user profile |
| `/profile` | PUT | ✅ | Update user profile |
| `/profile/change-password` | POST | ✅ | Change password |
| `/my-tenants` | GET | ✅ | List user's tenants |
| `/my-payments` | GET | ✅ | Payment history |
| `/my-tickets` | GET | ✅ | User's support tickets |

---

## 🏢 Step 5: Tenant Management

**Tag:** `Tenant Management`

> Users create and manage their tenants (storefronts) here.

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenants` | GET | ✅ | List user's tenants |
| `/tenants` | POST | ✅ | Create new tenant |
| `/tenants/{id}` | GET | ✅ | Get tenant details |
| `/tenants/{id}` | PUT | ✅ | Update tenant |
| `/tenants/{id}` | DELETE | ✅ | Delete tenant |
| `/tenants/{id}/switch` | POST | ✅ | Switch to tenant context |
| `/tenants/{id}/database-status` | GET | ✅ | Check database status |
| `/tenants/{id}/setup-database` | POST | ✅ | Initialize tenant database |

### Create Tenant Flow
```json
// 1. Create Tenant
POST /api/v1/tenants
{
  "name": "My Store",
  "domain": "mystore"
}

// 2. Setup Database
POST /api/v1/tenants/{id}/setup-database

// 3. Switch to Tenant Context
POST /api/v1/tenants/{id}/switch
// Returns tenant-specific token
```

---

## 💰 Step 6: Subscriptions & Payments

**Tag:** `Subscriptions`

> Users subscribe to plans to activate their tenants.

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/plans/check-coupon` | POST | ✅ | Validate coupon code |
| `/subscriptions/current` | GET | ✅ | Get current subscription |
| `/subscriptions/history` | GET | ✅ | Subscription history |
| `/subscriptions/initiate` | POST | ✅ | Start subscription flow |
| `/subscriptions/complete` | POST | ✅ | Complete subscription |
| `/subscriptions/upgrade` | POST | ✅ | Upgrade to new plan |
| `/subscriptions/{id}/cancel` | POST | ✅ | Cancel subscription |
| `/subscriptions/{id}/renew` | POST | ✅ | Renew subscription |

### Subscription Flow
```
1. User views /plans (public)
2. User selects plan → /subscriptions/initiate
3. User completes payment → /subscriptions/complete
4. Tenant is activated
```

---

## 🎫 Step 7: User Support Tickets

**Tag:** `User Support Tickets`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/support-departments` | GET | ✅ | List departments |
| `/support-tickets` | GET | ✅ | List user's tickets |
| `/support-tickets` | POST | ✅ | Create new ticket |
| `/support-tickets/{id}` | GET | ✅ | View ticket details |
| `/support-tickets/{id}/reply` | POST | ✅ | Reply to ticket |
| `/support-tickets/{id}/close` | POST | ✅ | Close ticket |

---

## 📸 Step 8: Media Upload

**Tag:** `Media Management`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/media` | GET | ✅ | List media library |
| `/media` | POST | ✅ | Upload file(s) - `multipart/form-data` |
| `/media/{id}` | GET | ✅ | Get media details |
| `/media/{id}` | PUT | ✅ | Update media metadata |
| `/media/{id}` | DELETE | ✅ | Delete media |
| `/media/bulk-delete` | POST | ✅ | Bulk delete media |
| `/media/storage-info` | GET | ✅ | Storage usage info |

---

# 🛡️ PART 2: PLATFORM ADMIN PANEL

> Super admin panel to manage the entire platform.

---

## 🔐 Step 9: Admin Authentication

**Tag:** `Admin Authentication`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/admin/auth/login` | POST | ❌ | Admin login |
| `/admin/auth/forgot-password` | POST | ❌ | Forgot password |
| `/admin/auth/reset-password` | POST | ❌ | Reset password |
| `/admin/auth/logout` | POST | ✅ | Admin logout |
| `/admin/auth/me` | GET | ✅ | Get admin profile |
| `/admin/auth/refresh-token` | POST | ✅ | Refresh token |

---

## 👥 Step 10: Admin Management

**Tag:** `Admin Management`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/admin/profile` | PUT | ✅ | Update own profile |
| `/admin/admins` | GET | ✅ | List all admins |
| `/admin/admins` | POST | ✅ | Create admin |
| `/admin/admins/{admin}` | GET | ✅ | Get admin details |
| `/admin/admins/{admin}` | PUT | ✅ | Update admin |
| `/admin/admins/{admin}` | DELETE | ✅ | Delete admin |
| `/admin/admins/{admin}/password` | PUT | ✅ | Update admin password |

---

## 🎭 Step 11: Role & Permission Management

**Tag:** `Role Management`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/admin/roles` | GET | ✅ | List all roles |
| `/admin/roles` | POST | ✅ | Create role |
| `/admin/roles/{role}` | GET | ✅ | Get role details |
| `/admin/roles/{role}` | PUT | ✅ | Update role |
| `/admin/roles/{role}` | DELETE | ✅ | Delete role |
| `/admin/permissions` | GET | ✅ | List all permissions |

---

## 👤 Step 12: User Management (Admin)

**Tag:** `User Management (Admin)`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/admin/users` | GET | ✅ | List all users |
| `/admin/users/{user}` | GET | ✅ | Get user details |
| `/admin/users/{user}` | PUT | ✅ | Update user |
| `/admin/users/{user}` | DELETE | ✅ | Delete user |
| `/admin/users/{user}/payments` | GET | ✅ | User's payments |
| `/admin/users/{user}/impersonate` | POST | ✅ | Impersonate user |

---

## 💳 Step 13: Price Plan Management (Admin)

**Tag:** `Price Plan Management (Admin)`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/admin/price-plans` | GET | ✅ | List all plans |
| `/admin/price-plans` | POST | ✅ | Create plan |
| `/admin/price-plans/{id}` | GET | ✅ | Get plan details |
| `/admin/price-plans/{id}` | PUT | ✅ | Update plan |
| `/admin/price-plans/{id}` | DELETE | ✅ | Delete plan |
| `/admin/price-plans/{id}/toggle-status` | POST | ✅ | Toggle status |
| `/admin/price-plans/{id}/reorder-features` | POST | ✅ | Reorder features |

---

## 🎫 Step 14: Support Ticket Management (Admin)

**Tags:** `Admin Support Tickets`, `Admin Support Departments`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/admin/support-tickets` | GET | ✅ | List all tickets |
| `/admin/support-tickets/stats` | GET | ✅ | Ticket statistics |
| `/admin/support-tickets/{id}` | GET | ✅ | View ticket |
| `/admin/support-tickets/{id}/reply` | POST | ✅ | Reply to ticket |
| `/admin/support-tickets/{id}/close` | POST | ✅ | Close ticket |
| `/admin/support-departments` | GET | ✅ | List departments |
| `/admin/support-departments` | POST | ✅ | Create department |
| `/admin/support-departments/{department}` | GET | ✅ | Get department |
| `/admin/support-departments/{department}` | PUT | ✅ | Update department |
| `/admin/support-departments/{department}` | DELETE | ✅ | Delete department |

---

## 🌐 Step 15: Language Management (Admin)

**Tag:** `Languages`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/admin/languages` | GET | ✅ | List all languages |
| `/admin/languages` | POST | ✅ | Create language |
| `/admin/languages/{code}` | GET | ✅ | Get language |
| `/admin/languages/{code}` | PUT | ✅ | Update language |
| `/admin/languages/{code}` | DELETE | ✅ | Delete language |
| `/admin/languages/{code}/toggle-status` | POST | ✅ | Toggle status |
| `/admin/languages/{code}/set-default` | POST | ✅ | Set as default |
| `/admin/languages/{code}/sync` | POST | ✅ | Sync translations |
| `/admin/languages/{code}/export` | GET | ✅ | Export translations |
| `/admin/languages/{code}/import` | POST | ✅ | Import translations |

**Tag:** `Translations`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/admin/languages/{code}/translations` | GET | ✅ | List translations |
| `/admin/languages/{code}/translations/{key}` | PUT | ✅ | Update translation |
| `/admin/languages/{code}/translations/{key}` | DELETE | ✅ | Delete translation |
| `/admin/languages/{code}/translations/search` | GET | ✅ | Search translations |
| `/admin/languages/{code}/translations/missing` | GET | ✅ | Missing translations |

---

## ⚙️ Step 16: Settings Management (Admin)

**Tag:** `Settings`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/admin/settings` | GET | ✅ | Get settings |
| `/admin/settings` | PUT | ✅ | Update settings |
| `/admin/settings/all` | GET | ✅ | Get all settings |
| `/admin/settings/{group}` | GET | ✅ | Get settings by group |
| `/admin/settings/{group}` | PUT | ✅ | Update settings group |
| `/admin/settings/key/{key}` | GET | ✅ | Get single setting |
| `/admin/settings/key/{key}` | PUT | ✅ | Update single setting |
| `/admin/settings/key/{key}` | DELETE | ✅ | Delete setting |
| `/admin/settings/search` | GET | ✅ | Search settings |
| `/admin/settings/clear-cache` | POST | ✅ | Clear cache |

**Tag:** `Email Settings`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/admin/email-settings/smtp` | GET | ✅ | Get SMTP settings |
| `/admin/email-settings/smtp` | PUT | ✅ | Update SMTP settings |
| `/admin/email-settings/templates` | GET | ✅ | List email templates |
| `/admin/email-settings/templates/{template}` | GET | ✅ | Get template |
| `/admin/email-settings/templates/{template}` | PUT | ✅ | Update template |
| `/admin/email-settings/test` | POST | ✅ | Send test email |

---

# 🏪 PART 3: TENANT ADMIN PANEL

> Admin panel for tenant owners to manage their storefront.

**Note:** All endpoints require `{tenant}` parameter (tenant ID/slug).

---

## 🔑 Tenant Context

**Tag:** `Tenant Context`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/info` | GET | ❌ | Public tenant info |

---

## 📊 Step 17: Tenant Dashboard

**Tag:** `Tenant Admin Dashboard`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/dashboard` | GET | ✅ | Dashboard stats |
| `/tenant/{tenant}/admin/dashboard/recent-orders` | GET | ✅ | Recent orders |
| `/tenant/{tenant}/admin/dashboard/recent-activity` | GET | ✅ | Recent activity |
| `/tenant/{tenant}/admin/dashboard/top-products` | GET | ✅ | Top products |
| `/tenant/{tenant}/admin/dashboard/low-stock` | GET | ✅ | Low stock items |
| `/tenant/{tenant}/admin/dashboard/revenue-chart` | GET | ✅ | Revenue chart data |
| `/tenant/{tenant}/admin/dashboard/orders-chart` | GET | ✅ | Orders chart data |

---

## 👤 Step 18: Tenant Admin Profile

**Tag:** `Tenant Admin Profile`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/profile` | GET | ✅ | Get profile |
| `/tenant/{tenant}/admin/profile` | PUT | ✅ | Update profile |
| `/tenant/{tenant}/admin/profile/avatar` | POST | ✅ | Update avatar |
| `/tenant/{tenant}/admin/profile/change-password` | POST | ✅ | Change password |
| `/tenant/{tenant}/admin/profile/2fa` | GET | ✅ | 2FA status |
| `/tenant/{tenant}/admin/profile/2fa/enable` | POST | ✅ | Enable 2FA |
| `/tenant/{tenant}/admin/profile/2fa/disable` | POST | ✅ | Disable 2FA |

---

## ⚙️ Step 19: Tenant Settings

**Tag:** `Tenant Admin Settings`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/settings` | GET | ✅ | Get all settings |
| `/tenant/{tenant}/admin/settings` | PUT | ✅ | Update settings |
| `/tenant/{tenant}/admin/settings/general` | GET/PUT | ✅ | General settings |
| `/tenant/{tenant}/admin/settings/appearance` | GET/PUT | ✅ | Appearance settings |
| `/tenant/{tenant}/admin/settings/seo` | GET/PUT | ✅ | SEO settings |
| `/tenant/{tenant}/admin/settings/social` | GET/PUT | ✅ | Social links |
| `/tenant/{tenant}/admin/settings/email` | GET/PUT | ✅ | Email settings |

---

## 🏷️ Step 20: Product Attributes

### Categories
**Tag:** `Admin - Categories`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/categories` | GET | ✅ | List categories |
| `/tenant/{tenant}/admin/categories` | POST | ✅ | Create category |
| `/tenant/{tenant}/admin/categories/{id}` | GET | ✅ | Get category |
| `/tenant/{tenant}/admin/categories/{id}` | PUT | ✅ | Update category |
| `/tenant/{tenant}/admin/categories/{id}` | DELETE | ✅ | Delete category |
| `/tenant/{tenant}/admin/categories/bulk-delete` | POST | ✅ | Bulk delete |

### Sub-Categories
**Tag:** `Admin - Sub-Categories`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/sub-categories` | GET | ✅ | List |
| `/tenant/{tenant}/admin/sub-categories` | POST | ✅ | Create |
| `/tenant/{tenant}/admin/sub-categories/{id}` | GET/PUT/DELETE | ✅ | Manage |

### Child-Categories
**Tag:** `Admin - Child-Categories`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/child-categories` | GET | ✅ | List |
| `/tenant/{tenant}/admin/child-categories` | POST | ✅ | Create |
| `/tenant/{tenant}/admin/child-categories/{id}` | GET/PUT/DELETE | ✅ | Manage |

### Brands
**Tag:** `Admin - Brands`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/brands` | GET | ✅ | List brands |
| `/tenant/{tenant}/admin/brands` | POST | ✅ | Create brand |
| `/tenant/{tenant}/admin/brands/{id}` | GET/PUT/DELETE | ✅ | Manage brand |

### Colors
**Tag:** `Admin - Colors`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/colors` | GET | ✅ | List colors |
| `/tenant/{tenant}/admin/colors` | POST | ✅ | Create color |
| `/tenant/{tenant}/admin/colors/{id}` | GET/PUT/DELETE | ✅ | Manage color |

### Sizes
**Tag:** `Admin - Sizes`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/sizes` | GET | ✅ | List sizes |
| `/tenant/{tenant}/admin/sizes` | POST | ✅ | Create size |
| `/tenant/{tenant}/admin/sizes/{id}` | GET/PUT/DELETE | ✅ | Manage size |

### Tags
**Tag:** `Admin - Tags`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/tags` | GET | ✅ | List tags |
| `/tenant/{tenant}/admin/tags` | POST | ✅ | Create tag |
| `/tenant/{tenant}/admin/tags/{id}` | GET/PUT/DELETE | ✅ | Manage tag |

---

## 🛍️ Step 21: Product Management

**Tag:** `Tenant Admin - Products`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/products` | GET | ✅ | List products |
| `/tenant/{tenant}/admin/products` | POST | ✅ | Create product |
| `/tenant/{tenant}/admin/products/{id}` | GET | ✅ | Get product |
| `/tenant/{tenant}/admin/products/{id}` | PUT | ✅ | Update product |
| `/tenant/{tenant}/admin/products/{id}` | DELETE | ✅ | Delete product |
| `/tenant/{tenant}/admin/products/{id}/toggle-status` | POST | ✅ | Toggle status |
| `/tenant/{tenant}/admin/products/{id}/stock` | PUT | ✅ | Update stock |
| `/tenant/{tenant}/admin/products/{id}/variants` | GET | ✅ | List variants |
| `/tenant/{tenant}/admin/products/{id}/variants` | POST | ✅ | Create variant |
| `/tenant/{tenant}/admin/products/{id}/variants/{variantId}` | PUT | ✅ | Update variant |
| `/tenant/{tenant}/admin/products/{id}/variants/{variantId}` | DELETE | ✅ | Delete variant |
| `/tenant/{tenant}/admin/products/bulk-delete` | POST | ✅ | Bulk delete |

---

## 📦 Step 22: Order Management

**Tag:** `Tenant Admin - Orders`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/orders` | GET | ✅ | List orders |
| `/tenant/{tenant}/admin/orders/statistics` | GET | ✅ | Order statistics |
| `/tenant/{tenant}/admin/orders/{id}` | GET | ✅ | Get order details |
| `/tenant/{tenant}/admin/orders/{id}/status` | PUT | ✅ | Update order status |
| `/tenant/{tenant}/admin/orders/{id}/payment-status` | PUT | ✅ | Update payment status |
| `/tenant/{tenant}/admin/orders/{id}/cancel` | POST | ✅ | Cancel order |

---

## 👥 Step 23: Customer Management

**Tag:** `Tenant Admin - Customers`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/customers` | GET | ✅ | List customers |
| `/tenant/{tenant}/admin/customers/statistics` | GET | ✅ | Customer statistics |
| `/tenant/{tenant}/admin/customers/export` | GET | ✅ | Export customers |
| `/tenant/{tenant}/admin/customers/{id}` | GET | ✅ | Get customer |
| `/tenant/{tenant}/admin/customers/{id}` | PUT | ✅ | Update customer |
| `/tenant/{tenant}/admin/customers/{id}` | DELETE | ✅ | Delete customer |
| `/tenant/{tenant}/admin/customers/{id}/orders` | GET | ✅ | Customer's orders |
| `/tenant/{tenant}/admin/customers/{id}/toggle-status` | POST | ✅ | Toggle status |
| `/tenant/{tenant}/admin/customers/{id}/change-password` | POST | ✅ | Change password |
| `/tenant/{tenant}/admin/customers/{id}/resend-verification` | POST | ✅ | Resend email |
| `/tenant/{tenant}/admin/customers/{id}/send-email` | POST | ✅ | Send email |

---

## 🎟️ Step 24: Coupon Management

**Tag:** `Admin - Coupons`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/coupons` | GET | ✅ | List coupons |
| `/tenant/{tenant}/admin/coupons` | POST | ✅ | Create coupon |
| `/tenant/{tenant}/admin/coupons/{id}` | GET | ✅ | Get coupon |
| `/tenant/{tenant}/admin/coupons/{id}` | PUT | ✅ | Update coupon |
| `/tenant/{tenant}/admin/coupons/{id}` | DELETE | ✅ | Delete coupon |
| `/tenant/{tenant}/admin/coupons/{id}/toggle-status` | POST | ✅ | Toggle status |

---

## 🚚 Step 25: Shipping Management

**Tag:** `Admin - Shipping Zones`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/shipping/zones` | GET | ✅ | List zones |
| `/tenant/{tenant}/admin/shipping/zones` | POST | ✅ | Create zone |
| `/tenant/{tenant}/admin/shipping/zones/{id}` | GET/PUT/DELETE | ✅ | Manage zone |

**Tag:** `Admin - Shipping Methods`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/shipping/methods` | GET | ✅ | List methods |
| `/tenant/{tenant}/admin/shipping/methods` | POST | ✅ | Create method |
| `/tenant/{tenant}/admin/shipping/methods/{id}` | GET/PUT/DELETE | ✅ | Manage method |
| `/tenant/{tenant}/admin/shipping/methods/{id}/set-default` | POST | ✅ | Set default |

---

## 📝 Step 26: Blog Management

**Tag:** `Tenant Admin - Blog`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/blog/posts` | GET | ✅ | List posts |
| `/tenant/{tenant}/admin/blog/posts` | POST | ✅ | Create post |
| `/tenant/{tenant}/admin/blog/posts/{id}` | GET | ✅ | Get post |
| `/tenant/{tenant}/admin/blog/posts/{id}` | PUT | ✅ | Update post |
| `/tenant/{tenant}/admin/blog/posts/{id}` | DELETE | ✅ | Delete post |
| `/tenant/{tenant}/admin/blog/posts/{id}/toggle-status` | POST | ✅ | Toggle status |
| `/tenant/{tenant}/admin/blog/posts/bulk-action` | POST | ✅ | Bulk actions |

**Tag:** `Tenant Admin - Blog Categories`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/admin/blog/categories` | GET | ✅ | List categories |
| `/tenant/{tenant}/admin/blog/categories` | POST | ✅ | Create category |
| `/tenant/{tenant}/admin/blog/categories/{id}` | GET/PUT/DELETE | ✅ | Manage |
| `/tenant/{tenant}/admin/blog/categories/{id}/toggle-status` | POST | ✅ | Toggle status |

---

# 🛒 PART 4: TENANT STOREFRONT (Customer-Facing)

> Public and customer endpoints for the tenant storefront.

---

## 🔐 Step 27: Tenant User Authentication

**Tag:** `Tenant User Authentication`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/auth/register` | POST | ❌ | Customer registration |
| `/tenant/{tenant}/auth/login` | POST | ❌ | Customer login |
| `/tenant/{tenant}/auth/forgot-password` | POST | ❌ | Forgot password |
| `/tenant/{tenant}/auth/reset-password` | POST | ❌ | Reset password |
| `/tenant/{tenant}/auth/logout` | POST | ✅ | Logout |
| `/tenant/{tenant}/auth/me` | GET | ✅ | Get profile |
| `/tenant/{tenant}/auth/refresh-token` | POST | ✅ | Refresh token |
| `/tenant/{tenant}/auth/verify-email` | POST | ✅ | Verify email |
| `/tenant/{tenant}/auth/resend-verification` | POST | ✅ | Resend verification |

---

## 🏷️ Step 28: Public Attributes

**Tag:** `Attributes`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/attributes/categories` | GET | ❌ | List categories |
| `/tenant/{tenant}/attributes/brands` | GET | ❌ | List brands |
| `/tenant/{tenant}/attributes/colors` | GET | ❌ | List colors |
| `/tenant/{tenant}/attributes/sizes` | GET | ❌ | List sizes |
| `/tenant/{tenant}/attributes/tags` | GET | ❌ | List tags |
| `/tenant/{tenant}/categories` | GET | ❌ | Categories tree |
| `/tenant/{tenant}/categories/{id}/products` | GET | ❌ | Products by category |

---

## 🛍️ Step 29: Product Browsing

**Tag:** `Tenant Frontend - Products`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/products` | GET | ❌ | List products |
| `/tenant/{tenant}/products/{slug}` | GET | ❌ | Product details |
| `/tenant/{tenant}/products/search` | GET | ❌ | Search products |
| `/tenant/{tenant}/products/filters` | GET | ❌ | Available filters |
| `/tenant/{tenant}/products/{id}/related` | GET | ❌ | Related products |
| `/tenant/{tenant}/products/{id}/reviews` | GET | ❌ | Product reviews |
| `/tenant/{tenant}/products/{id}/reviews` | POST | ✅ | Add review |

---

## 🛒 Step 30: Shopping Cart

**Tag:** `Tenant Frontend - Cart`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/cart` | GET | ❌/✅ | View cart |
| `/tenant/{tenant}/cart/items` | POST | ❌/✅ | Add item to cart |
| `/tenant/{tenant}/cart/items/{itemId}` | PUT | ❌/✅ | Update item quantity |
| `/tenant/{tenant}/cart/items/{itemId}` | DELETE | ❌/✅ | Remove item |
| `/tenant/{tenant}/cart/summary` | GET | ❌/✅ | Cart summary |
| `/tenant/{tenant}/cart/coupon` | POST | ❌/✅ | Apply coupon |
| `/tenant/{tenant}/cart/coupon` | DELETE | ❌/✅ | Remove coupon |
| `/tenant/{tenant}/cart/addresses` | GET | ✅ | Saved addresses |
| `/tenant/{tenant}/cart/merge` | POST | ✅ | Merge guest cart |

**Tag:** `Coupons`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/coupons/validate` | POST | ❌ | Validate coupon |

---

## 💳 Step 31: Checkout

**Tag:** `Tenant Frontend - Checkout`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/checkout` | POST | ✅ | Process checkout |
| `/tenant/{tenant}/checkout/calculate` | POST | ❌/✅ | Calculate totals |
| `/tenant/{tenant}/checkout/shipping-methods` | GET | ❌/✅ | Available shipping |
| `/tenant/{tenant}/checkout/payment-methods` | GET | ❌/✅ | Available payments |
| `/tenant/{tenant}/checkout/verify/{orderNumber}` | GET | ✅ | Verify order |
| `/tenant/{tenant}/checkout/webhook/{gateway}` | POST | ❌ | Payment webhook |

---

## 👤 Step 32: Customer Dashboard

**Tag:** `Tenant Customer - Dashboard`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/user/dashboard` | GET | ✅ | Dashboard stats |
| `/tenant/{tenant}/user/profile` | GET | ✅ | Get profile |
| `/tenant/{tenant}/user/profile` | PUT | ✅ | Update profile |
| `/tenant/{tenant}/user/change-password` | POST | ✅ | Change password |
| `/tenant/{tenant}/user/orders` | GET | ✅ | Order history |
| `/tenant/{tenant}/user/addresses` | GET | ✅ | List addresses |
| `/tenant/{tenant}/user/addresses` | POST | ✅ | Add address |
| `/tenant/{tenant}/user/addresses/{addressId}` | PUT | ✅ | Update address |
| `/tenant/{tenant}/user/addresses/{addressId}` | DELETE | ✅ | Delete address |
| `/tenant/{tenant}/user/addresses/{addressId}/set-default` | POST | ✅ | Set default |
| `/tenant/{tenant}/user/wishlist` | GET | ✅ | View wishlist |
| `/tenant/{tenant}/user/wishlist/{productId}` | POST | ✅ | Add to wishlist |
| `/tenant/{tenant}/user/wishlist/{productId}` | DELETE | ✅ | Remove from wishlist |

---

## 📖 Step 33: Blog (Public)

**Tag:** `Tenant Frontend - Blog`

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/tenant/{tenant}/blog` | GET | ❌ | List posts |
| `/tenant/{tenant}/blog/{slug}` | GET | ❌ | Post details |
| `/tenant/{tenant}/blog/categories` | GET | ❌ | Blog categories |
| `/tenant/{tenant}/blog/category/{slug}` | GET | ❌ | Posts by category |
| `/tenant/{tenant}/blog/tag/{tag}` | GET | ❌ | Posts by tag |
| `/tenant/{tenant}/blog/search` | GET | ❌ | Search posts |
| `/tenant/{tenant}/blog/recent` | GET | ❌ | Recent posts |
| `/tenant/{tenant}/blog/popular` | GET | ❌ | Popular posts |
| `/tenant/{tenant}/blog/{postId}/comments` | GET | ❌ | Get comments |
| `/tenant/{tenant}/blog/{postId}/comments` | POST | ✅ | Add comment |

---

# 📐 API Reference

## Authentication Headers

```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

## Standard Response Format

**Success:**
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

**Error:**
```json
{
  "success": false,
  "message": "Error description",
  "errors": { "field": ["error message"] }
}
```

**Paginated:**
```json
{
  "success": true,
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

---

# ✅ Integration Checklist

## Phase 1: Main Platform Foundation
- [ ] Health check & API connectivity
- [ ] Public languages & translations
- [ ] Public price plans display
- [ ] User registration & login
- [ ] 2FA setup flow
- [ ] User dashboard
- [ ] Tenant creation flow
- [ ] Subscription & payment flow
- [ ] Support tickets
- [ ] Media uploads

## Phase 2: Platform Admin Panel
- [ ] Admin authentication
- [ ] Admin management (CRUD)
- [ ] Role & permission management
- [ ] User management
- [ ] Price plan management
- [ ] Support ticket management
- [ ] Language & translation management
- [ ] Platform settings

## Phase 3: Tenant Admin Panel
- [ ] Tenant dashboard
- [ ] Tenant profile & settings
- [ ] Product attributes (categories, brands, etc.)
- [ ] Product management
- [ ] Order management
- [ ] Customer management
- [ ] Coupon management
- [ ] Shipping configuration
- [ ] Blog management

## Phase 4: Tenant Storefront
- [ ] Customer registration & login
- [ ] Product browsing & search
- [ ] Shopping cart (guest & authenticated)
- [ ] Checkout flow
- [ ] Customer dashboard
- [ ] Order history
- [ ] Wishlist
- [ ] Blog

---

# 📊 Endpoint Summary

| Section | Endpoints |
|---------|-----------|
| Health & Public | ~10 |
| User Auth & 2FA | ~20 |
| User Dashboard | ~10 |
| Tenant Management | ~8 |
| Subscriptions | ~10 |
| Admin Auth | ~6 |
| Admin Management | ~20 |
| Admin Settings | ~25 |
| Tenant Admin | ~80 |
| Tenant Storefront | ~50 |
| **Total** | **~240 endpoints** |

---

> 📚 **Swagger Documentation:** Access interactive API docs at `/api/documentation` for detailed request/response schemas and live testing.