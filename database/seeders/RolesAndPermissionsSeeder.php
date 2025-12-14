<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Roles and Permissions Seeder
 *
 * Seeds default roles and permissions for the landlord admin panel.
 * Uses the 'api_admin' guard for all permissions and roles.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * The guard name for admin permissions.
     */
    private const GUARD_NAME = 'api_admin';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create all permissions
        $this->createPermissions();

        // Create roles and assign permissions
        $this->createRoles();

        $this->command->info('Roles and permissions seeded successfully.');
    }

    /**
     * Create all permissions organized by module.
     */
    private function createPermissions(): void
    {
        $modules = $this->getModulePermissions();

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$module}-{$action}",
                    'guard_name' => self::GUARD_NAME,
                ]);
            }
        }

        // Create special permissions
        $specialPermissions = $this->getSpecialPermissions();
        foreach ($specialPermissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => self::GUARD_NAME,
            ]);
        }

        $this->command->info('Permissions created: ' . Permission::where('guard_name', self::GUARD_NAME)->count());
    }

    /**
     * Get module-based permissions.
     *
     * @return array<string, array<string>>
     */
    private function getModulePermissions(): array
    {
        $standardActions = ['list', 'create', 'edit', 'delete'];

        return [
            // Admin Management
            'admin' => $standardActions,

            // Role Management
            'role' => $standardActions,

            // User Management
            'user' => $standardActions,

            // Tenant Management
            'tenant' => [...$standardActions, 'view', 'manage'],

            // Page Management
            'page' => $standardActions,

            // Price Plan Management
            'price-plan' => $standardActions,

            // Blog Module
            'blog' => [...$standardActions, 'settings', 'comments'],
            'blog-category' => $standardActions,

            // Service Module
            'service' => [...$standardActions, 'settings', 'comments'],
            'service-category' => $standardActions,

            // Donation Module
            'donation' => $standardActions,
            'donation-category' => $standardActions,
            'donation-activities' => $standardActions,

            // Event Module
            'event' => $standardActions,
            'event-category' => $standardActions,

            // Job Module
            'job' => $standardActions,
            'job-category' => $standardActions,

            // Knowledgebase Module
            'knowledgebase' => $standardActions,
            'knowledgebase-category' => $standardActions,

            // Portfolio Module
            'portfolio' => $standardActions,
            'portfolio-category' => $standardActions,

            // Image Gallery
            'image-gallery' => $standardActions,
            'image-gallery-category' => $standardActions,

            // eCommerce Module
            'product' => $standardActions,
            'product-category' => $standardActions,
            'attribute' => $standardActions,
            'inventory' => $standardActions,
            'campaign' => $standardActions,
            'coupon' => $standardActions,
            'tax' => $standardActions,
            'badge' => $standardActions,
            'shipping' => $standardActions,
            'country' => $standardActions,

            // Order Management
            'package-order' => [
                'list',
                'view',
                'edit',
                'delete',
                'pending',
                'progress',
                'complete',
                'cancel',
                'report',
            ],
            'product-order' => ['list', 'view', 'edit', 'delete', 'report'],

            // Testimonial
            'testimonial' => $standardActions,

            // Brand
            'brand' => $standardActions,

            // Newsletter
            'newsletter' => $standardActions,

            // Support Ticket
            'support-ticket' => $standardActions,
            'support-ticket-department' => $standardActions,

            // Appointment Module
            'appointment' => [...$standardActions, 'settings', 'report'],
            'appointment-category' => $standardActions,
            'appointment-sub-category' => $standardActions,
            'sub-appointment' => $standardActions,

            // Language
            'language' => $standardActions,

            // FAQ
            'faq' => $standardActions,
            'faq-category' => $standardActions,

            // Media
            'media' => ['list', 'create', 'delete'],

            // Payment
            'payment-log' => ['list', 'view', 'report'],
        ];
    }

    /**
     * Get special (non-CRUD) permissions.
     *
     * @return array<string>
     */
    private function getSpecialPermissions(): array
    {
        return [
            // Form & Widget Builders
            'form-builder',
            'form-submission',
            'widget-builder',

            // General Settings
            'general-settings-page-settings',
            'general-settings-global-navbar-settings',
            'general-settings-global-footer-settings',
            'general-settings-site-identity',
            'general-settings-application-settings',
            'general-settings-basic-settings',
            'general-settings-color-settings',
            'general-settings-typography-settings',
            'general-settings-seo-settings',
            'general-settings-payment-settings',
            'general-settings-third-party-script-settings',
            'general-settings-smtp-settings',
            'general-settings-custom-css-settings',
            'general-settings-custom-js-settings',
            'general-settings-database-upgrade-settings',
            'general-settings-cache-clear-settings',
            'general-settings-license-settings',

            // Other Settings
            'menu-manage',
            'topbar-manage',
            'other-settings',

            // Appointment specific
            'appointment-day-type',
            'appointment-days',
            'appointment-schedule',
            'appointment-payment-log',
        ];
    }

    /**
     * Create roles and assign permissions.
     */
    private function createRoles(): void
    {
        // Super Admin - has all permissions (wildcard)
        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => self::GUARD_NAME,
        ]);
        // Super admin gets all permissions
        $superAdmin->syncPermissions(Permission::where('guard_name', self::GUARD_NAME)->get());

        // Admin - has most permissions except sensitive ones
        $admin = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => self::GUARD_NAME,
        ]);
        $adminPermissions = Permission::where('guard_name', self::GUARD_NAME)
            ->whereNotIn('name', [
                'admin-delete',
                'role-create',
                'role-edit',
                'role-delete',
                'general-settings-database-upgrade-settings',
                'general-settings-license-settings',
            ])
            ->pluck('name')
            ->toArray();
        $admin->syncPermissions($adminPermissions);

        // Editor - content management only
        $editor = Role::firstOrCreate([
            'name' => 'editor',
            'guard_name' => self::GUARD_NAME,
        ]);
        $editorPermissions = Permission::where('guard_name', self::GUARD_NAME)
            ->where(function ($query) {
                $query->where('name', 'like', 'blog%')
                    ->orWhere('name', 'like', 'page%')
                    ->orWhere('name', 'like', 'service%')
                    ->orWhere('name', 'like', 'portfolio%')
                    ->orWhere('name', 'like', 'testimonial%')
                    ->orWhere('name', 'like', 'faq%')
                    ->orWhere('name', 'like', 'image-gallery%')
                    ->orWhere('name', 'like', 'media%')
                    ->orWhere('name', 'like', 'knowledgebase%');
            })
            ->pluck('name')
            ->toArray();
        $editor->syncPermissions($editorPermissions);

        $this->command->info('Roles created: super-admin, admin, editor');
    }
}
