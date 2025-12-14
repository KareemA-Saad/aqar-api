<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Admin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Admin Service
 *
 * Handles all admin-related business logic including:
 * - Admin CRUD operations
 * - Role and permission management
 * - Password management
 */
final class AdminService
{
    /**
     * The guard name for admin permissions.
     */
    private const GUARD_NAME = 'api_admin';

    /**
     * Get paginated list of admins with filters.
     *
     * @param array{search?: string, role?: string, status?: string, per_page?: int} $filters
     * @param int|null $excludeId Exclude this admin ID (typically the current admin)
     * @return LengthAwarePaginator
     */
    public function getAdminList(array $filters = [], ?int $excludeId = null): LengthAwarePaginator
    {
        $query = Admin::query()
            ->with(['roles'])
            ->when($excludeId, fn (Builder $q) => $q->where('id', '!=', $excludeId));

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            });
        }

        // Role filter
        if (!empty($filters['role'])) {
            $query->whereHas('roles', fn (Builder $q) => $q->where('name', $filters['role']));
        }

        // Status filter (email_verified)
        if (isset($filters['status'])) {
            $verified = $filters['status'] === 'active';
            $query->where('email_verified', $verified);
        }

        $perPage = min($filters['per_page'] ?? 15, 100);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Create a new admin.
     *
     * @param array{name: string, email: string, username: string, password: string, role: string, mobile?: ?string, image?: ?string} $data
     * @return Admin
     */
    public function createAdmin(array $data): Admin
    {
        return DB::transaction(function () use ($data) {
            $admin = Admin::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'username' => $data['username'],
                'password' => Hash::make($data['password']),
                'mobile' => $data['mobile'] ?? null,
                'image' => $data['image'] ?? null,
                'email_verified' => true,
            ]);

            $admin->assignRole($data['role']);

            Log::info('Admin created', [
                'admin_id' => $admin->id,
                'role' => $data['role'],
            ]);

            return $admin->load('roles');
        });
    }

    /**
     * Update an existing admin.
     *
     * @param Admin $admin
     * @param array{name: string, email: string, role?: ?string, mobile?: ?string, image?: ?string} $data
     * @return Admin
     */
    public function updateAdmin(Admin $admin, array $data): Admin
    {
        return DB::transaction(function () use ($admin, $data) {
            $admin->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'] ?? $admin->mobile,
                'image' => $data['image'] ?? $admin->image,
            ]);

            // Update role if provided
            if (!empty($data['role'])) {
                $this->assignRole($admin, $data['role']);
            }

            Log::info('Admin updated', ['admin_id' => $admin->id]);

            return $admin->fresh(['roles']);
        });
    }

    /**
     * Update admin profile (self update).
     *
     * @param Admin $admin
     * @param array{name: string, email: string, mobile?: ?string, image?: ?string} $data
     * @return Admin
     */
    public function updateProfile(Admin $admin, array $data): Admin
    {
        $admin->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'mobile' => $data['mobile'] ?? $admin->mobile,
            'image' => $data['image'] ?? $admin->image,
        ]);

        Log::info('Admin profile updated', ['admin_id' => $admin->id]);

        return $admin->fresh(['roles']);
    }

    /**
     * Update admin password.
     *
     * @param Admin $admin
     * @param string $password
     * @return bool
     */
    public function updatePassword(Admin $admin, string $password): bool
    {
        $result = $admin->update([
            'password' => Hash::make($password),
        ]);

        Log::info('Admin password updated', ['admin_id' => $admin->id]);

        return $result;
    }

    /**
     * Delete (soft delete) an admin.
     *
     * @param Admin $admin
     * @return bool
     */
    public function deleteAdmin(Admin $admin): bool
    {
        return DB::transaction(function () use ($admin) {
            $adminId = $admin->id;

            // Remove all roles
            DB::table('model_has_roles')
                ->where('model_id', $admin->id)
                ->where('model_type', Admin::class)
                ->delete();

            // Delete tokens
            $admin->tokens()->delete();

            // Delete admin
            $admin->delete();

            Log::info('Admin deleted', ['admin_id' => $adminId]);

            return true;
        });
    }

    /**
     * Assign a role to an admin (replaces existing roles).
     *
     * @param Admin $admin
     * @param string $role
     * @return void
     */
    public function assignRole(Admin $admin, string $role): void
    {
        // Remove existing roles
        DB::table('model_has_roles')
            ->where('model_id', $admin->id)
            ->where('model_type', Admin::class)
            ->delete();

        // Assign new role
        $admin->assignRole($role);

        Log::info('Admin role assigned', [
            'admin_id' => $admin->id,
            'role' => $role,
        ]);
    }

    /**
     * Get admin by ID with roles.
     *
     * @param int $id
     * @return Admin|null
     */
    public function getAdminById(int $id): ?Admin
    {
        return Admin::with(['roles', 'permissions'])->find($id);
    }

    /**
     * Get all roles with permissions count.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Role>
     */
    public function getAllRoles(): \Illuminate\Database\Eloquent\Collection
    {
        return Role::where('guard_name', self::GUARD_NAME)
            ->withCount('permissions')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get role by ID with permissions.
     *
     * @param int $id
     * @return Role|null
     */
    public function getRoleById(int $id): ?Role
    {
        return Role::where('guard_name', self::GUARD_NAME)
            ->with('permissions')
            ->find($id);
    }

    /**
     * Create a new role with permissions.
     *
     * @param array{name: string, permissions: array<string>} $data
     * @return Role
     */
    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => self::GUARD_NAME,
            ]);

            if (!empty($data['permissions'])) {
                $this->syncPermissions($role, $data['permissions']);
            }

            Log::info('Role created', [
                'role_id' => $role->id,
                'name' => $role->name,
                'permissions_count' => count($data['permissions']),
            ]);

            return $role->load('permissions');
        });
    }

    /**
     * Update an existing role.
     *
     * @param Role $role
     * @param array{name: string, permissions: array<string>} $data
     * @return Role
     */
    public function updateRole(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $role->update(['name' => $data['name']]);

            $this->syncPermissions($role, $data['permissions']);

            Log::info('Role updated', [
                'role_id' => $role->id,
                'name' => $role->name,
            ]);

            return $role->fresh(['permissions']);
        });
    }

    /**
     * Delete a role.
     *
     * @param Role $role
     * @return bool
     */
    public function deleteRole(Role $role): bool
    {
        $roleId = $role->id;
        $roleName = $role->name;

        $role->delete();

        Log::info('Role deleted', [
            'role_id' => $roleId,
            'name' => $roleName,
        ]);

        return true;
    }

    /**
     * Sync permissions to a role.
     *
     * @param Role $role
     * @param array<string> $permissions
     * @return void
     */
    public function syncPermissions(Role $role, array $permissions): void
    {
        $role->syncPermissions($permissions);

        Log::debug('Role permissions synced', [
            'role_id' => $role->id,
            'permissions_count' => count($permissions),
        ]);
    }

    /**
     * Get all available permissions grouped by module.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection>
     */
    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        return Permission::where('guard_name', self::GUARD_NAME)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get permissions grouped by module.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection>
     */
    public function getPermissionsGrouped(): \Illuminate\Support\Collection
    {
        $permissions = $this->getAllPermissions();

        return $permissions->groupBy(function ($permission) {
            // Extract module name from permission (e.g., "page-list" -> "page")
            $parts = explode('-', $permission->name);
            // Handle multi-word modules like "blog-category"
            if (count($parts) > 2) {
                array_pop($parts); // Remove action
                return implode('-', $parts);
            }
            return $parts[0] ?? 'general';
        });
    }

    /**
     * Check if role is protected (cannot be deleted).
     *
     * @param Role $role
     * @return bool
     */
    public function isProtectedRole(Role $role): bool
    {
        $protectedRoles = ['super-admin', 'Super Admin'];
        return in_array($role->name, $protectedRoles, true);
    }

    /**
     * Check if admin has any of the given roles.
     *
     * @param Admin $admin
     * @param array<string> $roles
     * @return bool
     */
    public function hasAnyRole(Admin $admin, array $roles): bool
    {
        return $admin->hasAnyRole($roles);
    }
}
