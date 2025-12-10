<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;

/**
 * Auth Service Provider
 *
 * Registers authentication and authorization services.
 * Defines token abilities/scopes for Sanctum.
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Token abilities/scopes for different user types.
     *
     * These define what actions tokens can perform.
     */
    public const TOKEN_ABILITIES = [
        // Admin abilities - Full platform access
        'admin:full-access' => 'Full administrative access to all platform features',
        'admin:read' => 'Read administrative data',
        'admin:write' => 'Create and update administrative data',
        'admin:delete' => 'Delete administrative data',
        'admin:users-manage' => 'Manage platform users',
        'admin:tenants-manage' => 'Manage all tenants',
        'admin:settings-manage' => 'Manage platform settings',
        'admin:reports-view' => 'View platform reports and analytics',

        // User abilities - Tenant owner access
        'user:manage-tenant' => 'Full access to manage owned tenants',
        'user:read' => 'Read user data and tenant information',
        'user:write' => 'Create and update user data',
        'user:tenants-read' => 'View tenant information',
        'user:tenants-write' => 'Create and modify tenants',
        'user:subscriptions-manage' => 'Manage subscription and billing',
        'user:support-access' => 'Access support system',

        // Tenant user abilities - End user access
        'tenant-user:basic-access' => 'Basic access within tenant context',
        'tenant:read' => 'Read tenant data',
        'tenant:write' => 'Create and update tenant data',
        'tenant:profile-manage' => 'Manage own profile',
        'tenant:orders-manage' => 'Manage orders (if applicable)',
        'tenant:content-view' => 'View tenant content',
    ];

    /**
     * Ability groups for easy assignment.
     */
    public const ABILITY_GROUPS = [
        'admin' => [
            'admin:full-access',
            'admin:read',
            'admin:write',
            'admin:delete',
            'admin:users-manage',
            'admin:tenants-manage',
            'admin:settings-manage',
            'admin:reports-view',
        ],

        'user' => [
            'user:manage-tenant',
            'user:read',
            'user:write',
            'user:tenants-read',
            'user:tenants-write',
            'user:subscriptions-manage',
            'user:support-access',
        ],

        'tenant_user' => [
            'tenant-user:basic-access',
            'tenant:read',
            'tenant:write',
            'tenant:profile-manage',
            'tenant:orders-manage',
            'tenant:content-view',
        ],
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->defineGates();
    }

    /**
     * Define authorization gates.
     */
    protected function defineGates(): void
    {
        // Admin gates
        Gate::define('admin-access', function ($user) {
            return $user instanceof \App\Models\Admin;
        });

        Gate::define('manage-users', function ($user) {
            if (!$user instanceof \App\Models\Admin) {
                return false;
            }
            $token = $user->currentAccessToken();
            return $token && ($token->can('admin:full-access') || $token->can('admin:users-manage'));
        });

        Gate::define('manage-tenants', function ($user) {
            if (!$user instanceof \App\Models\Admin) {
                return false;
            }
            $token = $user->currentAccessToken();
            return $token && ($token->can('admin:full-access') || $token->can('admin:tenants-manage'));
        });

        Gate::define('manage-settings', function ($user) {
            if (!$user instanceof \App\Models\Admin) {
                return false;
            }
            $token = $user->currentAccessToken();
            return $token && ($token->can('admin:full-access') || $token->can('admin:settings-manage'));
        });

        // User gates
        Gate::define('user-access', function ($user) {
            return $user instanceof \App\Models\User;
        });

        Gate::define('manage-own-tenant', function ($user, $tenantId = null) {
            if (!$user instanceof \App\Models\User) {
                return false;
            }

            if ($tenantId) {
                return $user->tenants()->where('id', $tenantId)->exists();
            }

            return $user->tenants()->exists();
        });

        Gate::define('manage-subscription', function ($user) {
            if (!$user instanceof \App\Models\User) {
                return false;
            }
            $token = $user->currentAccessToken();
            return $token && ($token->can('user:manage-tenant') || $token->can('user:subscriptions-manage'));
        });

        // Tenant user gates
        Gate::define('tenant-user-access', function ($user) {
            return $user instanceof \App\Models\TenantUser;
        });

        Gate::define('tenant-read', function ($user) {
            if (!$user instanceof \App\Models\TenantUser) {
                return false;
            }
            $token = $user->currentAccessToken();
            return $token && ($token->can('tenant-user:basic-access') || $token->can('tenant:read'));
        });

        Gate::define('tenant-write', function ($user) {
            if (!$user instanceof \App\Models\TenantUser) {
                return false;
            }
            $token = $user->currentAccessToken();
            return $token && ($token->can('tenant-user:basic-access') || $token->can('tenant:write'));
        });
    }

    /**
     * Get abilities for a specific user type.
     *
     * @param string $type 'admin', 'user', or 'tenant_user'
     * @return array<string>
     */
    public static function getAbilitiesForType(string $type): array
    {
        return self::ABILITY_GROUPS[$type] ?? [];
    }

    /**
     * Get all available abilities with descriptions.
     *
     * @return array<string, string>
     */
    public static function getAllAbilities(): array
    {
        return self::TOKEN_ABILITIES;
    }
}
