# Management APIs
_Exported on 12/20/2025 at 16:28:24 GMT+2 from Cursor (2.2.20)_

---

**User**

Create the Admin management API for landlord administrators.

Reference the current admin logic from:  @OLDARCHIVE/app/Http/Controllers/Landlord/Admin @OLDARCHIVE/app/Models 
@app/Http/Controllers/Landlord/Admin/LandlordAdminController.php
@app/Http/Controllers/Landlord/Admin/AdminRoleManageController.php
@app/Models/Admin.php

Create:

1. app/Http/Controllers/Api/V1/Landlord/Admin/AdminController.php
   - index() - paginated list with filters (search, role, status)
   - store(AdminRequest) - create admin
   - show(id) - get admin details with roles
   - update(id, AdminRequest) - update admin
   - destroy(id) - soft delete admin
   - updatePassword(id, PasswordRequest)
   - updateProfile(ProfileRequest) - update own profile

2. app/Http/Controllers/Api/V1/Landlord/Admin/RoleController.php
   - index() - list all roles with permissions count
   - store(RoleRequest) - create role with permissions
   - show(id) - role with all permissions
   - update(id, RoleRequest)
   - destroy(id)
   - permissions() - list all available permissions

3. app/Http/Requests/Admin/
   - StoreAdminRequest.php
   - UpdateAdminRequest.php
   - UpdatePasswordRequest.php
   - StoreRoleRequest.php
   - UpdateRoleRequest.php

4. app/Http/Resources/
   - AdminResource.php
   - AdminCollection.php
   - RoleResource.php
   - PermissionResource.php

5. app/Services/AdminService.php
   - createAdmin(array $data): Admin
   - updateAdmin(Admin $admin, array $data): Admin
   - deleteAdmin(Admin $admin): bool
   - assignRole(Admin $admin, string $role): void
   - syncPermissions(Role $role, array $permissions): void

6. database/seeders/RolesAndPermissionsSeeder.php
   - Seed default roles: super-admin, admin, editor
   - Seed all permissions based on modules

Reference role/permission structure from:
@database/migrations/2022_04_20_100718_create_permission_tables.php 

---

**Cursor**

I'll start by reading the workspace rules and the referenced files to understand the existing patterns and conventions.

Let me explore the OLDARCHIVE folder and existing API controllers to understand the patterns better.

Let me read the existing files to understand the patterns being used:

Let me read the ApiResponse trait and check for existing seeders to understand the patterns:

Now I have a clear picture of the patterns. Let me create all the Admin management API files:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;

/**
 * Store Admin Request DTO
 *
 * Validates admin creation data.
 */
#[OA\Schema(
    schema: 'StoreAdminRequest',
    title: 'Store Admin Request',
    required: ['name', 'email', 'username', 'password', 'role'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe', maxLength: 191),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@aqar.com', maxLength: 191),
        new OA\Property(property: 'username', type: 'string', example: 'johndoe', maxLength: 191),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'SecurePass123!', minLength: 8),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'SecurePass123!'),
        new OA\Property(property: 'role', type: 'string', example: 'admin'),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890', nullable: true),
        new OA\Property(property: 'image', type: 'string', example: 'uploads/admins/avatar.jpg', nullable: true),
    ]
)]
final class StoreAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('admins', 'email')],
            'username' => ['required', 'string', 'max:191', Rule::unique('admins', 'username')],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role' => ['required', 'string', 'max:191', 'exists:roles,name'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'image' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'username.required' => 'Username is required.',
            'username.unique' => 'This username is already taken.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Role is required.',
            'role.exists' => 'Selected role does not exist.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{name: string, email: string, username: string, password: string, role: string, mobile: ?string, image: ?string}
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'username' => strtolower($data['username']),
            'password' => $data['password'],
            'role' => $data['role'],
            'mobile' => $data['mobile'] ?? null,
            'image' => $data['image'] ?? null,
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Update Admin Request DTO
 *
 * Validates admin update data.
 */
#[OA\Schema(
    schema: 'UpdateAdminRequest',
    title: 'Update Admin Request',
    required: ['name', 'email'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe', maxLength: 191),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@aqar.com', maxLength: 191),
        new OA\Property(property: 'role', type: 'string', example: 'admin', nullable: true),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890', nullable: true),
        new OA\Property(property: 'image', type: 'string', example: 'uploads/admins/avatar.jpg', nullable: true),
    ]
)]
final class UpdateAdminRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $adminId = $this->route('admin');

        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('admins', 'email')->ignore($adminId)],
            'role' => ['nullable', 'string', 'max:191', 'exists:roles,name'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'image' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'role.exists' => 'Selected role does not exist.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{name: string, email: string, role: ?string, mobile: ?string, image: ?string}
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'role' => $data['role'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'image' => $data['image'] ?? null,
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;

/**
 * Update Password Request DTO
 *
 * Validates password update data.
 */
#[OA\Schema(
    schema: 'UpdatePasswordRequest',
    title: 'Update Password Request',
    required: ['password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewSecurePass123!', minLength: 8),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewSecurePass123!'),
    ]
)]
final class UpdatePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{password: string}
     */
    public function validatedData(): array
    {
        return [
            'password' => $this->validated()['password'],
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Update Profile Request DTO
 *
 * Validates own profile update data.
 */
#[OA\Schema(
    schema: 'UpdateProfileRequest',
    title: 'Update Profile Request',
    required: ['name', 'email'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe', maxLength: 191),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@aqar.com', maxLength: 191),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890', nullable: true),
        new OA\Property(property: 'image', type: 'string', example: 'uploads/admins/avatar.jpg', nullable: true),
    ]
)]
final class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Admin|null $admin */
        $admin = auth('api_admin')->user();
        $adminId = $admin?->id;

        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('admins', 'email')->ignore($adminId)],
            'mobile' => ['nullable', 'string', 'max:20'],
            'image' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{name: string, email: string, mobile: ?string, image: ?string}
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'mobile' => $data['mobile'] ?? null,
            'image' => $data['image'] ?? null,
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Store Role Request DTO
 *
 * Validates role creation data.
 */
#[OA\Schema(
    schema: 'StoreRoleRequest',
    title: 'Store Role Request',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'editor', maxLength: 191),
        new OA\Property(
            property: 'permissions',
            type: 'array',
            items: new OA\Items(type: 'string', example: 'page-list'),
            nullable: true
        ),
    ]
)]
final class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191', Rule::unique('roles', 'name')],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required.',
            'name.unique' => 'This role name already exists.',
            'permissions.array' => 'Permissions must be an array.',
            'permissions.*.exists' => 'One or more selected permissions do not exist.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{name: string, permissions: array<string>}
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'permissions' => $data['permissions'] ?? [],
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Update Role Request DTO
 *
 * Validates role update data.
 */
#[OA\Schema(
    schema: 'UpdateRoleRequest',
    title: 'Update Role Request',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'editor', maxLength: 191),
        new OA\Property(
            property: 'permissions',
            type: 'array',
            items: new OA\Items(type: 'string', example: 'page-list'),
            nullable: true
        ),
    ]
)]
final class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $roleId = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:191', Rule::unique('roles', 'name')->ignore($roleId)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required.',
            'name.unique' => 'This role name already exists.',
            'permissions.array' => 'Permissions must be an array.',
            'permissions.*.exists' => 'One or more selected permissions do not exist.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{name: string, permissions: array<string>}
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'permissions' => $data['permissions'] ?? [],
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Admin Collection Resource
 *
 * Collection wrapper for admin resources with pagination metadata.
 */
#[OA\Schema(
    schema: 'AdminCollection',
    title: 'Admin Collection',
    description: 'Paginated collection of admin resources',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/AdminResource')
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                new OA\Property(property: 'from', type: 'integer', example: 1),
                new OA\Property(property: 'last_page', type: 'integer', example: 10),
                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                new OA\Property(property: 'to', type: 'integer', example: 15),
                new OA\Property(property: 'total', type: 'integer', example: 150),
            ],
            type: 'object'
        ),
    ]
)]
class AdminCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = AdminResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'current_page' => $this->currentPage(),
                'from' => $this->firstItem(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'to' => $this->lastItem(),
                'total' => $this->total(),
            ],
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Role;

/**
 * @mixin Role
 */
#[OA\Schema(
    schema: 'RoleResource',
    title: 'Role Resource',
    description: 'Role resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'admin'),
        new OA\Property(property: 'guard_name', type: 'string', example: 'api_admin'),
        new OA\Property(property: 'permissions_count', type: 'integer', example: 15, nullable: true),
        new OA\Property(
            property: 'permissions',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PermissionResource'),
            nullable: true
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
    ]
)]
class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'permissions_count' => $this->whenCounted('permissions'),
            'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;
use Spatie\Permission\Models\Permission;

/**
 * @mixin Permission
 */
#[OA\Schema(
    schema: 'PermissionResource',
    title: 'Permission Resource',
    description: 'Permission resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'page-list'),
        new OA\Property(property: 'guard_name', type: 'string', example: 'api_admin'),
        new OA\Property(property: 'group', type: 'string', example: 'page', nullable: true, description: 'Permission group derived from name'),
    ]
)]
class PermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'group' => $this->getPermissionGroup(),
        ];
    }

    /**
     * Extract the permission group from the permission name.
     *
     * Examples:
     * - page-list -> page
     * - blog-category-edit -> blog-category
     * - admin-management-create -> admin-management
     */
    private function getPermissionGroup(): ?string
    {
        $name = $this->name;

        // Common action suffixes to strip
        $actions = ['-list', '-create', '-edit', '-delete', '-view', '-manage'];

        foreach ($actions as $action) {
            if (str_ends_with($name, $action)) {
                return substr($name, 0, -strlen($action));
            }
        }

        return $name;
    }
}

```

```php
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

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Admin\StoreAdminRequest;
use App\Http\Requests\Admin\UpdateAdminRequest;
use App\Http\Requests\Admin\UpdatePasswordRequest;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Http\Resources\AdminCollection;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Admin Controller
 *
 * Handles admin user management operations.
 *
 * @package App\Http\Controllers\Api\V1\Landlord\Admin
 */
#[OA\Tag(
    name: 'Admin Management',
    description: 'Admin user management endpoints (Guard: api_admin). Manage platform administrators.'
)]
final class AdminController extends BaseApiController
{
    public function __construct(
        private readonly AdminService $adminService,
    ) {}

    /**
     * Get paginated list of admins.
     */
    #[OA\Get(
        path: '/api/v1/admin/admins',
        summary: 'List all admins',
        description: 'Get paginated list of all administrators with optional filters',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Management']
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        required: false,
        description: 'Search by name, email, or username',
        schema: new OA\Schema(type: 'string', example: 'john')
    )]
    #[OA\Parameter(
        name: 'role',
        in: 'query',
        required: false,
        description: 'Filter by role name',
        schema: new OA\Schema(type: 'string', example: 'admin')
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        required: false,
        description: 'Filter by status (active/inactive)',
        schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'])
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'Items per page (max 100)',
        schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)
    )]
    #[OA\Response(
        response: 200,
        description: 'Admins retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Admins retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/AdminResource')
                ),
                new OA\Property(
                    property: 'pagination',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 50),
                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 4),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    public function index(Request $request): JsonResponse
    {
        /** @var Admin|null $currentAdmin */
        $currentAdmin = auth('api_admin')->user();

        if (!$currentAdmin) {
            return $this->unauthorized();
        }

        $filters = [
            'search' => $request->query('search'),
            'role' => $request->query('role'),
            'status' => $request->query('status'),
            'per_page' => (int) $request->query('per_page', $this->perPage),
        ];

        $admins = $this->adminService->getAdminList($filters, $currentAdmin->id);

        return $this->paginated(
            $admins,
            AdminResource::class,
            'Admins retrieved successfully'
        );
    }

    /**
     * Create a new admin.
     */
    #[OA\Post(
        path: '/api/v1/admin/admins',
        summary: 'Create new admin',
        description: 'Create a new administrator account',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Management']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StoreAdminRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Admin created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Admin created successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AdminResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    example: ['email' => ['This email is already registered.']]
                ),
            ]
        )
    )]
    public function store(StoreAdminRequest $request): JsonResponse
    {
        $data = $request->validatedData();

        try {
            $admin = $this->adminService->createAdmin($data);

            return $this->created(
                new AdminResource($admin),
                'Admin created successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to create admin: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get admin details.
     */
    #[OA\Get(
        path: '/api/v1/admin/admins/{admin}',
        summary: 'Get admin details',
        description: 'Retrieve details of a specific administrator',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Management']
    )]
    #[OA\Parameter(
        name: 'admin',
        in: 'path',
        required: true,
        description: 'Admin ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Admin retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Admin retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AdminResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Admin not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Admin not found'),
            ]
        )
    )]
    public function show(int $admin): JsonResponse
    {
        $adminModel = $this->adminService->getAdminById($admin);

        if (!$adminModel) {
            return $this->notFound('Admin not found');
        }

        return $this->success(
            new AdminResource($adminModel),
            'Admin retrieved successfully'
        );
    }

    /**
     * Update admin details.
     */
    #[OA\Put(
        path: '/api/v1/admin/admins/{admin}',
        summary: 'Update admin',
        description: 'Update an existing administrator',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Management']
    )]
    #[OA\Parameter(
        name: 'admin',
        in: 'path',
        required: true,
        description: 'Admin ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UpdateAdminRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Admin updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Admin updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AdminResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Admin not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Admin not found'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function update(UpdateAdminRequest $request, int $admin): JsonResponse
    {
        $adminModel = $this->adminService->getAdminById($admin);

        if (!$adminModel) {
            return $this->notFound('Admin not found');
        }

        try {
            $data = $request->validatedData();
            $updatedAdmin = $this->adminService->updateAdmin($adminModel, $data);

            return $this->success(
                new AdminResource($updatedAdmin),
                'Admin updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to update admin: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete an admin.
     */
    #[OA\Delete(
        path: '/api/v1/admin/admins/{admin}',
        summary: 'Delete admin',
        description: 'Delete an administrator account',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Management']
    )]
    #[OA\Parameter(
        name: 'admin',
        in: 'path',
        required: true,
        description: 'Admin ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Admin deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Admin deleted successfully'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Cannot delete yourself',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'You cannot delete your own account'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Admin not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Admin not found'),
            ]
        )
    )]
    public function destroy(int $admin): JsonResponse
    {
        /** @var Admin|null $currentAdmin */
        $currentAdmin = auth('api_admin')->user();

        if (!$currentAdmin) {
            return $this->unauthorized();
        }

        // Prevent self-deletion
        if ($currentAdmin->id === $admin) {
            return $this->forbidden('You cannot delete your own account');
        }

        $adminModel = $this->adminService->getAdminById($admin);

        if (!$adminModel) {
            return $this->notFound('Admin not found');
        }

        try {
            $this->adminService->deleteAdmin($adminModel);

            return $this->success(null, 'Admin deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete admin: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update admin password.
     */
    #[OA\Put(
        path: '/api/v1/admin/admins/{admin}/password',
        summary: 'Update admin password',
        description: 'Update the password of a specific administrator',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Management']
    )]
    #[OA\Parameter(
        name: 'admin',
        in: 'path',
        required: true,
        description: 'Admin ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UpdatePasswordRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Password updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Password updated successfully'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Admin not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Admin not found'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function updatePassword(UpdatePasswordRequest $request, int $admin): JsonResponse
    {
        $adminModel = $this->adminService->getAdminById($admin);

        if (!$adminModel) {
            return $this->notFound('Admin not found');
        }

        try {
            $data = $request->validatedData();
            $this->adminService->updatePassword($adminModel, $data['password']);

            return $this->success(null, 'Password updated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to update password: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update own profile.
     */
    #[OA\Put(
        path: '/api/v1/admin/profile',
        summary: 'Update own profile',
        description: 'Update the authenticated admin\'s profile',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Management']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UpdateProfileRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Profile updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Profile updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AdminResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var Admin|null $admin */
        $admin = auth('api_admin')->user();

        if (!$admin) {
            return $this->unauthorized();
        }

        try {
            $data = $request->validatedData();
            $updatedAdmin = $this->adminService->updateProfile($admin, $data);

            return $this->success(
                new AdminResource($updatedAdmin),
                'Profile updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to update profile: ' . $e->getMessage(), 500);
        }
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Role Controller
 *
 * Handles role and permission management operations.
 *
 * @package App\Http\Controllers\Api\V1\Landlord\Admin
 */
#[OA\Tag(
    name: 'Role Management',
    description: 'Role and permission management endpoints (Guard: api_admin). Manage roles and their permissions.'
)]
final class RoleController extends BaseApiController
{
    public function __construct(
        private readonly AdminService $adminService,
    ) {}

    /**
     * Get list of all roles.
     */
    #[OA\Get(
        path: '/api/v1/admin/roles',
        summary: 'List all roles',
        description: 'Get list of all roles with permissions count',
        security: [['sanctum_admin' => []]],
        tags: ['Role Management']
    )]
    #[OA\Response(
        response: 200,
        description: 'Roles retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Roles retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/RoleResource')
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    public function index(): JsonResponse
    {
        $roles = $this->adminService->getAllRoles();

        return $this->success(
            RoleResource::collection($roles),
            'Roles retrieved successfully'
        );
    }

    /**
     * Create a new role.
     */
    #[OA\Post(
        path: '/api/v1/admin/roles',
        summary: 'Create new role',
        description: 'Create a new role with permissions',
        security: [['sanctum_admin' => []]],
        tags: ['Role Management']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StoreRoleRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Role created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Role created successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/RoleResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(
                    property: 'errors',
                    type: 'object',
                    example: ['name' => ['This role name already exists.']]
                ),
            ]
        )
    )]
    public function store(StoreRoleRequest $request): JsonResponse
    {
        try {
            $data = $request->validatedData();
            $role = $this->adminService->createRole($data);

            return $this->created(
                new RoleResource($role),
                'Role created successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to create role: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get role details with permissions.
     */
    #[OA\Get(
        path: '/api/v1/admin/roles/{role}',
        summary: 'Get role details',
        description: 'Retrieve details of a specific role with all its permissions',
        security: [['sanctum_admin' => []]],
        tags: ['Role Management']
    )]
    #[OA\Parameter(
        name: 'role',
        in: 'path',
        required: true,
        description: 'Role ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Role retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Role retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/RoleResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Role not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Role not found'),
            ]
        )
    )]
    public function show(int $role): JsonResponse
    {
        $roleModel = $this->adminService->getRoleById($role);

        if (!$roleModel) {
            return $this->notFound('Role not found');
        }

        return $this->success(
            new RoleResource($roleModel),
            'Role retrieved successfully'
        );
    }

    /**
     * Update an existing role.
     */
    #[OA\Put(
        path: '/api/v1/admin/roles/{role}',
        summary: 'Update role',
        description: 'Update an existing role and its permissions',
        security: [['sanctum_admin' => []]],
        tags: ['Role Management']
    )]
    #[OA\Parameter(
        name: 'role',
        in: 'path',
        required: true,
        description: 'Role ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UpdateRoleRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Role updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Role updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/RoleResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Cannot modify protected role',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Cannot modify the super-admin role'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Role not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Role not found'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function update(UpdateRoleRequest $request, int $role): JsonResponse
    {
        $roleModel = $this->adminService->getRoleById($role);

        if (!$roleModel) {
            return $this->notFound('Role not found');
        }

        // Prevent modification of protected roles
        if ($this->adminService->isProtectedRole($roleModel)) {
            return $this->forbidden('Cannot modify the super-admin role');
        }

        try {
            $data = $request->validatedData();
            $updatedRole = $this->adminService->updateRole($roleModel, $data);

            return $this->success(
                new RoleResource($updatedRole),
                'Role updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to update role: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a role.
     */
    #[OA\Delete(
        path: '/api/v1/admin/roles/{role}',
        summary: 'Delete role',
        description: 'Delete a role. Protected roles (super-admin) cannot be deleted.',
        security: [['sanctum_admin' => []]],
        tags: ['Role Management']
    )]
    #[OA\Parameter(
        name: 'role',
        in: 'path',
        required: true,
        description: 'Role ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Role deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Role deleted successfully'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 403,
        description: 'Cannot delete protected role',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Cannot delete the super-admin role'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Role not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Role not found'),
            ]
        )
    )]
    public function destroy(int $role): JsonResponse
    {
        $roleModel = $this->adminService->getRoleById($role);

        if (!$roleModel) {
            return $this->notFound('Role not found');
        }

        // Prevent deletion of protected roles
        if ($this->adminService->isProtectedRole($roleModel)) {
            return $this->forbidden('Cannot delete the super-admin role');
        }

        try {
            $this->adminService->deleteRole($roleModel);

            return $this->success(null, 'Role deleted successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to delete role: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get all available permissions.
     */
    #[OA\Get(
        path: '/api/v1/admin/permissions',
        summary: 'List all permissions',
        description: 'Get list of all available permissions, optionally grouped by module',
        security: [['sanctum_admin' => []]],
        tags: ['Role Management']
    )]
    #[OA\Parameter(
        name: 'grouped',
        in: 'query',
        required: false,
        description: 'Return permissions grouped by module',
        schema: new OA\Schema(type: 'boolean', default: false)
    )]
    #[OA\Response(
        response: 200,
        description: 'Permissions retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Permissions retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PermissionResource')
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    public function permissions(): JsonResponse
    {
        $grouped = request()->boolean('grouped', false);

        if ($grouped) {
            $permissions = $this->adminService->getPermissionsGrouped();

            // Transform grouped permissions
            $groupedData = $permissions->map(function ($items, $group) {
                return [
                    'group' => $group,
                    'permissions' => PermissionResource::collection($items),
                ];
            })->values();

            return $this->success($groupedData, 'Permissions retrieved successfully');
        }

        $permissions = $this->adminService->getAllPermissions();

        return $this->success(
            PermissionResource::collection($permissions),
            'Permissions retrieved successfully'
        );
    }
}

```

```php
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

```

```php
<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AdminAuthController;
use App\Http\Controllers\Api\V1\Auth\TenantUserAuthController;
use App\Http\Controllers\Api\V1\Auth\UserAuthController;
use App\Http\Controllers\Api\V1\Landlord\Admin\AdminController;
use App\Http\Controllers\Api\V1\Landlord\Admin\RoleController;
use App\Http\Controllers\Api\V1\Landlord\TenantController;
use Illuminate\Support\Facades\Route;
```

```php
    /*
    |--------------------------------------------------------------------------
    | Admin Protected Routes
    |--------------------------------------------------------------------------
    | Routes for platform administrators.
    | Guard: api_admin
    */
    Route::middleware('auth:api_admin')->prefix('admin')->name('admin.')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Admin Management Routes
        |--------------------------------------------------------------------------
        */
        // Admin profile (own)
        Route::put('profile', [AdminController::class, 'updateProfile'])->name('profile.update');

        // Admin CRUD
        Route::get('admins', [AdminController::class, 'index'])->name('admins.index');
        Route::post('admins', [AdminController::class, 'store'])->name('admins.store');
        Route::get('admins/{admin}', [AdminController::class, 'show'])->name('admins.show');
        Route::put('admins/{admin}', [AdminController::class, 'update'])->name('admins.update');
        Route::delete('admins/{admin}', [AdminController::class, 'destroy'])->name('admins.destroy');
        Route::put('admins/{admin}/password', [AdminController::class, 'updatePassword'])->name('admins.password');

        /*
        |--------------------------------------------------------------------------
        | Role Management Routes
        |--------------------------------------------------------------------------
        */
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // Permissions
        Route::get('permissions', [RoleController::class, 'permissions'])->name('permissions.index');
    });
```

I've created the complete Admin management API for landlord administrators. Here's a summary of all the files created:

## Files Created

### 1. Controllers (`app/Http/Controllers/Api/V1/Landlord/Admin/`)

**AdminController.php** - Admin user management:
- `index()` - Paginated list with filters (search, role, status)
- `store(StoreAdminRequest)` - Create admin
- `show(id)` - Get admin details with roles
- `update(id, UpdateAdminRequest)` - Update admin
- `destroy(id)` - Delete admin (with self-deletion protection)
- `updatePassword(id, UpdatePasswordRequest)` - Update admin password
- `updateProfile(UpdateProfileRequest)` - Update own profile

**RoleController.php** - Role and permission management:
- `index()` - List all roles with permissions count
- `store(StoreRoleRequest)` - Create role with permissions
- `show(id)` - Role with all permissions
- `update(id, UpdateRoleRequest)` - Update role
- `destroy(id)` - Delete role (protected roles cannot be deleted)
- `permissions()` - List all permissions (optional `?grouped=true`)

### 2. Form Requests (`app/Http/Requests/Admin/`)
- `StoreAdminRequest.php`
- `UpdateAdminRequest.php`
- `UpdatePasswordRequest.php`
- `UpdateProfileRequest.php`
- `StoreRoleRequest.php`
- `UpdateRoleRequest.php`

### 3. API Resources (`app/Http/Resources/`)
- `AdminCollection.php` - Paginated admin collection
- `RoleResource.php` - Role with permissions
- `PermissionResource.php` - Permission with group extraction

### 4. Service (`app/Services/AdminService.php`)
All business logic including:
- `createAdmin()`, `updateAdmin()`, `deleteAdmin()`
- `assignRole()`, `syncPermissions()`
- `createRole()`, `updateRole()`, `deleteRole()`
- `getAllPermissions()`, `getPermissionsGrouped()`

### 5. Seeder (`database/seeders/RolesAndPermissionsSeeder.php`)
Seeds default roles and permissions:
- **Roles**: `super-admin`, `admin`, `editor`
- **Permissions**: Module-based (admin, role, user, tenant, page, blog, service, etc.)

### 6. Updated Routes (`routes/api.php`)
All admin routes under `api/v1/admin/` with `auth:api_admin` middleware.

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| PUT | `/api/v1/admin/profile` | Update own profile |
| GET | `/api/v1/admin/admins` | List admins |
| POST | `/api/v1/admin/admins` | Create admin |
| GET | `/api/v1/admin/admins/{id}` | Get admin |
| PUT | `/api/v1/admin/admins/{id}` | Update admin |
| DELETE | `/api/v1/admin/admins/{id}` | Delete admin |
| PUT | `/api/v1/admin/admins/{id}/password` | Update password |
| GET | `/api/v1/admin/roles` | List roles |
| POST | `/api/v1/admin/roles` | Create role |
| GET | `/api/v1/admin/roles/{id}` | Get role |
| PUT | `/api/v1/admin/roles/{id}` | Update role |
| DELETE | `/api/v1/admin/roles/{id}` | Delete role |
| GET | `/api/v1/admin/permissions` | List permissions |

Run the seeder to populate roles and permissions:
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

```shellscript
php artisan db:seed --class=RolesAndPermissionsSeeder
```

---

**User**

Check if implemented yet and if not start it :

Create the User management API for landlord system (tenant owners).

Reference: @OLDARCHIVE/app 
@app/Http/Controllers/Landlord/Frontend/UserDashboardController.php
@app/Http/Controllers/Landlord/Admin/TenantManageController.php
@app/Models/User.php
@app/Models/Tenant.php

Create:

1. app/Http/Controllers/Api/V1/Landlord/UserController.php (Admin managing users)
   - index() - paginated users with tenants info
   - show(id) - user with all tenants and payment history
   - update(id) - update user details
   - destroy(id) - deactivate user
   - impersonate(id) - get token to act as user
   - paymentHistory(id) - user's payment logs

2. app/Http/Controllers/Api/V1/Landlord/UserDashboardController.php (User self-service)
   - dashboard() - stats (tenants count, active packages, support tickets)
   - profile() - get own profile
   - updateProfile(ProfileRequest)
   - changePassword(PasswordRequest)
   - tenants() - list own tenants with status
   - createTenant(CreateTenantRequest) - with plan selection
   - supportTickets() - own support tickets

3. app/Http/Requests/User/
   - UpdateProfileRequest.php
   - ChangePasswordRequest.php
   - CreateTenantRequest.php

4. app/Http/Resources/
   - UserResource.php
   - UserCollection.php
   - UserDashboardResource.php
   - TenantResource.php
   - TenantCollection.php

5. app/Services/UserService.php
   - getUserDashboardStats(User $user): array
   - updateProfile(User $user, array $data): User
   - createTenantForUser(User $user, PricePlan $plan, array $data): Tenant

Reference user dashboard logic from @OLDARCHIVE/app/Http/Controllers/Landlord/Frontend @app/Http/Controllers/Landlord/Frontend/UserDashboardController.php particularly:
- user_index() method for dashboard data
- user_profile_update() for profile updates
- package_orders() for payment history

---

**Cursor**

Let me first check if any of these files already exist, and then read the reference files to understand the patterns.

The files don't exist yet. Let me read the reference files from OLDARCHIVE and the existing models:

Let me check the existing resources and also look at the PaymentLog model:

Now I have all the context. Let me create all the required files:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Update Profile Request DTO
 *
 * Validates user profile update data.
 */
#[OA\Schema(
    schema: 'UserUpdateProfileRequest',
    title: 'Update Profile Request',
    required: ['name', 'email'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe', maxLength: 191),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com', maxLength: 191),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890', nullable: true),
        new OA\Property(property: 'company', type: 'string', example: 'Acme Corp', nullable: true),
        new OA\Property(property: 'address', type: 'string', example: '123 Main St', nullable: true),
        new OA\Property(property: 'city', type: 'string', example: 'New York', nullable: true),
        new OA\Property(property: 'state', type: 'string', example: 'NY', nullable: true),
        new OA\Property(property: 'country', type: 'string', example: 'USA', nullable: true),
        new OA\Property(property: 'image', type: 'string', example: 'uploads/users/avatar.jpg', nullable: true),
    ]
)]
final class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();
        $userId = $user?->id;

        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($userId)],
            'mobile' => ['nullable', 'string', 'max:20'],
            'company' => ['nullable', 'string', 'max:191'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:191'],
            'state' => ['nullable', 'string', 'max:191'],
            'country' => ['nullable', 'string', 'max:191'],
            'image' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{name: string, email: string, mobile: ?string, company: ?string, address: ?string, city: ?string, state: ?string, country: ?string, image: ?string}
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'mobile' => $data['mobile'] ?? null,
            'company' => $data['company'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'image' => $data['image'] ?? null,
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;

/**
 * Change Password Request DTO
 *
 * Validates password change data.
 */
#[OA\Schema(
    schema: 'ChangePasswordRequest',
    title: 'Change Password Request',
    required: ['old_password', 'password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'old_password', type: 'string', format: 'password', example: 'OldPassword123!'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewSecurePass123!', minLength: 8),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewSecurePass123!'),
    ]
)]
final class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'old_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'old_password.required' => 'Current password is required.',
            'password.required' => 'New password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{old_password: string, password: string}
     */
    public function validatedData(): array
    {
        return [
            'old_password' => $this->validated()['old_password'],
            'password' => $this->validated()['password'],
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Admin Update User Request DTO
 *
 * Validates user update data when admin is updating a user.
 */
#[OA\Schema(
    schema: 'AdminUpdateUserRequest',
    title: 'Admin Update User Request',
    required: ['name', 'email'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe', maxLength: 191),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com', maxLength: 191),
        new OA\Property(property: 'username', type: 'string', example: 'johndoe', maxLength: 191, nullable: true),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890', nullable: true),
        new OA\Property(property: 'company', type: 'string', example: 'Acme Corp', nullable: true),
        new OA\Property(property: 'address', type: 'string', example: '123 Main St', nullable: true),
        new OA\Property(property: 'city', type: 'string', example: 'New York', nullable: true),
        new OA\Property(property: 'state', type: 'string', example: 'NY', nullable: true),
        new OA\Property(property: 'country', type: 'string', example: 'USA', nullable: true),
        new OA\Property(property: 'image', type: 'string', example: 'uploads/users/avatar.jpg', nullable: true),
        new OA\Property(property: 'email_verified', type: 'boolean', example: true, nullable: true),
    ]
)]
final class AdminUpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($userId)],
            'username' => ['nullable', 'string', 'max:191', Rule::unique('users', 'username')->ignore($userId)],
            'mobile' => ['nullable', 'string', 'max:20'],
            'company' => ['nullable', 'string', 'max:191'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:191'],
            'state' => ['nullable', 'string', 'max:191'],
            'country' => ['nullable', 'string', 'max:191'],
            'image' => ['nullable', 'string', 'max:255'],
            'email_verified' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'username.unique' => 'This username is already taken.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array<string, mixed>
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'username' => isset($data['username']) ? strtolower($data['username']) : null,
            'mobile' => $data['mobile'] ?? null,
            'company' => $data['company'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'image' => $data['image'] ?? null,
            'email_verified' => $data['email_verified'] ?? null,
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PaymentLog;
use App\Models\PricePlan;
use App\Models\SupportTicket;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * User Service
 *
 * Handles all user-related business logic including:
 * - User CRUD operations
 * - User dashboard statistics
 * - Profile management
 * - Tenant creation for users
 */
final class UserService
{
    public function __construct(
        private readonly TenantService $tenantService,
    ) {}

    /**
     * Get paginated list of users with filters.
     *
     * @param array{search?: string, has_subdomain?: bool, email_verified?: bool, per_page?: int} $filters
     * @return LengthAwarePaginator
     */
    public function getUserList(array $filters = []): LengthAwarePaginator
    {
        $query = User::query()
            ->withCount(['tenants', 'supportTickets'])
            ->with(['latestPaymentLog']);

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        // Has subdomain filter
        if (isset($filters['has_subdomain'])) {
            $query->where('has_subdomain', $filters['has_subdomain']);
        }

        // Email verified filter
        if (isset($filters['email_verified'])) {
            $query->where('email_verified', $filters['email_verified']);
        }

        $perPage = min($filters['per_page'] ?? 15, 100);

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    /**
     * Get user by ID with all relationships.
     *
     * @param int $id
     * @return User|null
     */
    public function getUserById(int $id): ?User
    {
        return User::with([
            'tenants' => fn ($q) => $q->with(['domains', 'paymentLog']),
            'paymentLogs' => fn ($q) => $q->with('package')->latest()->limit(20),
            'supportTickets' => fn ($q) => $q->with('department')->latest()->limit(10),
        ])
            ->withCount(['tenants', 'supportTickets', 'paymentLogs'])
            ->find($id);
    }

    /**
     * Update user details (admin action).
     *
     * @param User $user
     * @param array<string, mixed> $data
     * @return User
     */
    public function updateUser(User $user, array $data): User
    {
        $updateData = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'username' => $data['username'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'company' => $data['company'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'image' => $data['image'] ?? null,
        ], fn ($v) => $v !== null);

        // Handle email_verified separately as it can be false
        if (isset($data['email_verified'])) {
            $updateData['email_verified'] = $data['email_verified'];
        }

        $user->update($updateData);

        Log::info('User updated by admin', ['user_id' => $user->id]);

        return $user->fresh();
    }

    /**
     * Deactivate user (soft action - revoke tokens).
     *
     * @param User $user
     * @return bool
     */
    public function deactivateUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            // Revoke all tokens
            $user->tokens()->delete();

            Log::info('User deactivated', ['user_id' => $user->id]);

            return true;
        });
    }

    /**
     * Delete user and all associated data.
     *
     * @param User $user
     * @return bool
     */
    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            $userId = $user->id;

            // Delete all tenants
            foreach ($user->tenants as $tenant) {
                $this->tenantService->deleteTenant($tenant);
            }

            // Revoke all tokens
            $user->tokens()->delete();

            // Delete user
            $user->delete();

            Log::info('User deleted', ['user_id' => $userId]);

            return true;
        });
    }

    /**
     * Generate impersonation token for user.
     *
     * @param User $user
     * @return array{token: string, expires_at: string}
     */
    public function generateImpersonationToken(User $user): array
    {
        $expiresAt = now()->addHours(2);

        $token = $user->createToken(
            name: 'admin-impersonation',
            abilities: ['user:read', 'user:write', 'tenants:read', 'tenants:write', 'impersonated'],
            expiresAt: $expiresAt
        );

        Log::info('Impersonation token generated', ['user_id' => $user->id]);

        return [
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toISOString(),
        ];
    }

    /**
     * Get user's payment history.
     *
     * @param User $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPaymentHistory(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return PaymentLog::where('user_id', $user->id)
            ->with(['package', 'tenant'])
            ->orderByDesc('created_at')
            ->paginate(min($perPage, 100));
    }

    /**
     * Get user dashboard statistics.
     *
     * @param User $user
     * @return array{tenants_count: int, active_packages: int, support_tickets_count: int, open_tickets_count: int, total_spent: float, recent_payments: Collection}
     */
    public function getUserDashboardStats(User $user): array
    {
        $tenantsCount = $user->tenants()->count();

        $activePackages = PaymentLog::where('user_id', $user->id)
            ->where('payment_status', 1)
            ->where(function ($q) {
                $q->whereNull('expire_date')
                    ->orWhere('expire_date', '>', now());
            })
            ->count();

        $supportTicketsCount = $user->supportTickets()->count();
        $openTicketsCount = $user->supportTickets()->where('status', 0)->count();

        $totalSpent = PaymentLog::where('user_id', $user->id)
            ->where('payment_status', 1)
            ->sum('package_price');

        $recentPayments = PaymentLog::where('user_id', $user->id)
            ->with(['package', 'tenant'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return [
            'tenants_count' => $tenantsCount,
            'active_packages' => $activePackages,
            'support_tickets_count' => $supportTicketsCount,
            'open_tickets_count' => $openTicketsCount,
            'total_spent' => (float) $totalSpent,
            'recent_payments' => $recentPayments,
        ];
    }

    /**
     * Update user profile (self-service).
     *
     * @param User $user
     * @param array<string, mixed> $data
     * @return User
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'mobile' => $data['mobile'] ?? $user->mobile,
            'company' => $data['company'] ?? $user->company,
            'address' => $data['address'] ?? $user->address,
            'city' => $data['city'] ?? $user->city,
            'state' => $data['state'] ?? $user->state,
            'country' => $data['country'] ?? $user->country,
            'image' => $data['image'] ?? $user->image,
        ]);

        Log::info('User profile updated', ['user_id' => $user->id]);

        return $user->fresh();
    }

    /**
     * Change user password.
     *
     * @param User $user
     * @param string $oldPassword
     * @param string $newPassword
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function changePassword(User $user, string $oldPassword, string $newPassword): bool
    {
        if (!Hash::check($oldPassword, $user->password)) {
            throw new \InvalidArgumentException('Current password is incorrect');
        }

        $user->update([
            'password' => Hash::make($newPassword),
        ]);

        // Revoke all existing tokens for security
        $user->tokens()->delete();

        Log::info('User password changed', ['user_id' => $user->id]);

        return true;
    }

    /**
     * Get user's tenants with status.
     *
     * @param User $user
     * @return Collection<int, Tenant>
     */
    public function getUserTenants(User $user): Collection
    {
        return $user->tenants()
            ->with(['domains', 'paymentLog'])
            ->get();
    }

    /**
     * Get user's support tickets.
     *
     * @param User $user
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserSupportTickets(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return SupportTicket::where('user_id', $user->id)
            ->with(['department', 'admin'])
            ->orderByDesc('created_at')
            ->paginate(min($perPage, 100));
    }

    /**
     * Create a tenant for user.
     *
     * @param User $user
     * @param PricePlan $plan
     * @param array{subdomain: string, theme?: ?string, theme_code?: ?string} $data
     * @return Tenant
     */
    public function createTenantForUser(User $user, PricePlan $plan, array $data): Tenant
    {
        return $this->tenantService->createTenant(
            user: $user,
            plan: $plan,
            data: $data,
            async: true
        );
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

/**
 * User Collection Resource
 *
 * Collection wrapper for user resources with pagination metadata.
 */
#[OA\Schema(
    schema: 'UserCollection',
    title: 'User Collection',
    description: 'Paginated collection of user resources',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/UserResource')
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                new OA\Property(property: 'from', type: 'integer', example: 1),
                new OA\Property(property: 'last_page', type: 'integer', example: 10),
                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                new OA\Property(property: 'to', type: 'integer', example: 15),
                new OA\Property(property: 'total', type: 'integer', example: 150),
            ],
            type: 'object'
        ),
    ]
)]
class UserCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = UserResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'current_page' => $this->currentPage(),
                'from' => $this->firstItem(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'to' => $this->lastItem(),
                'total' => $this->total(),
            ],
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * User Dashboard Resource
 *
 * Resource for user dashboard data.
 */
#[OA\Schema(
    schema: 'UserDashboardResource',
    title: 'User Dashboard Resource',
    description: 'User dashboard statistics and summary',
    properties: [
        new OA\Property(property: 'tenants_count', type: 'integer', example: 3),
        new OA\Property(property: 'active_packages', type: 'integer', example: 2),
        new OA\Property(property: 'support_tickets_count', type: 'integer', example: 5),
        new OA\Property(property: 'open_tickets_count', type: 'integer', example: 1),
        new OA\Property(property: 'total_spent', type: 'number', format: 'float', example: 299.99),
        new OA\Property(
            property: 'recent_payments',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PaymentLogResource')
        ),
    ]
)]
class UserDashboardResource extends JsonResource
{
    /**
     * Create a new resource instance.
     *
     * @param array<string, mixed> $resource
     */
    public function __construct(array $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'tenants_count' => $this->resource['tenants_count'],
            'active_packages' => $this->resource['active_packages'],
            'support_tickets_count' => $this->resource['support_tickets_count'],
            'open_tickets_count' => $this->resource['open_tickets_count'],
            'total_spent' => $this->resource['total_spent'],
            'recent_payments' => PaymentLogResource::collection($this->resource['recent_payments']),
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Tenant Collection Resource
 *
 * Collection wrapper for tenant resources.
 */
#[OA\Schema(
    schema: 'TenantCollection',
    title: 'Tenant Collection',
    description: 'Collection of tenant resources',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/TenantResource')
        ),
    ]
)]
class TenantCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = TenantResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\User\AdminUpdateUserRequest;
use App\Http\Resources\PaymentLogResource;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * User Controller (Admin)
 *
 * Handles user management operations by administrators.
 *
 * @package App\Http\Controllers\Api\V1\Landlord
 */
#[OA\Tag(
    name: 'User Management (Admin)',
    description: 'Admin endpoints to manage landlord users (Guard: api_admin). Manage tenant owners and their subscriptions.'
)]
final class UserController extends BaseApiController
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * Get paginated list of users.
     */
    #[OA\Get(
        path: '/api/v1/admin/users',
        summary: 'List all users',
        description: 'Get paginated list of all landlord users with tenant info',
        security: [['sanctum_admin' => []]],
        tags: ['User Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        required: false,
        description: 'Search by name, email, username, or company',
        schema: new OA\Schema(type: 'string', example: 'john')
    )]
    #[OA\Parameter(
        name: 'has_subdomain',
        in: 'query',
        required: false,
        description: 'Filter by users with/without tenants',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Parameter(
        name: 'email_verified',
        in: 'query',
        required: false,
        description: 'Filter by email verification status',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'Items per page (max 100)',
        schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)
    )]
    #[OA\Response(
        response: 200,
        description: 'Users retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Users retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/UserResource')
                ),
                new OA\Property(
                    property: 'pagination',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 50),
                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 4),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->query('search'),
            'has_subdomain' => $request->has('has_subdomain') ? $request->boolean('has_subdomain') : null,
            'email_verified' => $request->has('email_verified') ? $request->boolean('email_verified') : null,
            'per_page' => (int) $request->query('per_page', $this->perPage),
        ];

        // Remove null values
        $filters = array_filter($filters, fn ($v) => $v !== null);

        $users = $this->userService->getUserList($filters);

        return $this->paginated(
            $users,
            UserResource::class,
            'Users retrieved successfully'
        );
    }

    /**
     * Get user details with all tenants and payment history.
     */
    #[OA\Get(
        path: '/api/v1/admin/users/{user}',
        summary: 'Get user details',
        description: 'Retrieve details of a specific user with all tenants and payment history',
        security: [['sanctum_admin' => []]],
        tags: ['User Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'user',
        in: 'path',
        required: true,
        description: 'User ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'User retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'User retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/UserResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'User not found'),
            ]
        )
    )]
    public function show(int $user): JsonResponse
    {
        $userModel = $this->userService->getUserById($user);

        if (!$userModel) {
            return $this->notFound('User not found');
        }

        return $this->success(
            new UserResource($userModel),
            'User retrieved successfully'
        );
    }

    /**
     * Update user details.
     */
    #[OA\Put(
        path: '/api/v1/admin/users/{user}',
        summary: 'Update user',
        description: 'Update user details by administrator',
        security: [['sanctum_admin' => []]],
        tags: ['User Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'user',
        in: 'path',
        required: true,
        description: 'User ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/AdminUpdateUserRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'User updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'User updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/UserResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'User not found'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function update(AdminUpdateUserRequest $request, int $user): JsonResponse
    {
        $userModel = $this->userService->getUserById($user);

        if (!$userModel) {
            return $this->notFound('User not found');
        }

        try {
            $data = $request->validatedData();
            $updatedUser = $this->userService->updateUser($userModel, $data);

            return $this->success(
                new UserResource($updatedUser),
                'User updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to update user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Deactivate user (revoke tokens).
     */
    #[OA\Delete(
        path: '/api/v1/admin/users/{user}',
        summary: 'Deactivate user',
        description: 'Deactivate a user by revoking all their tokens',
        security: [['sanctum_admin' => []]],
        tags: ['User Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'user',
        in: 'path',
        required: true,
        description: 'User ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'User deactivated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'User deactivated successfully'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'User not found'),
            ]
        )
    )]
    public function destroy(int $user): JsonResponse
    {
        $userModel = $this->userService->getUserById($user);

        if (!$userModel) {
            return $this->notFound('User not found');
        }

        try {
            $this->userService->deactivateUser($userModel);

            return $this->success(null, 'User deactivated successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to deactivate user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Impersonate user and get token.
     */
    #[OA\Post(
        path: '/api/v1/admin/users/{user}/impersonate',
        summary: 'Impersonate user',
        description: 'Get a token to act as this user. Token expires in 2 hours.',
        security: [['sanctum_admin' => []]],
        tags: ['User Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'user',
        in: 'path',
        required: true,
        description: 'User ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Impersonation token generated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Impersonation token generated'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'User not found'),
            ]
        )
    )]
    public function impersonate(int $user): JsonResponse
    {
        $userModel = $this->userService->getUserById($user);

        if (!$userModel) {
            return $this->notFound('User not found');
        }

        try {
            $tokenData = $this->userService->generateImpersonationToken($userModel);

            return $this->success([
                'user' => new UserResource($userModel),
                'token' => $tokenData['token'],
                'token_type' => 'Bearer',
                'expires_at' => $tokenData['expires_at'],
            ], 'Impersonation token generated');
        } catch (\Exception $e) {
            return $this->error('Failed to generate impersonation token: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get user's payment history.
     */
    #[OA\Get(
        path: '/api/v1/admin/users/{user}/payments',
        summary: 'Get user payment history',
        description: 'Get paginated payment history for a specific user',
        security: [['sanctum_admin' => []]],
        tags: ['User Management (Admin)']
    )]
    #[OA\Parameter(
        name: 'user',
        in: 'path',
        required: true,
        description: 'User ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15)
    )]
    #[OA\Response(
        response: 200,
        description: 'Payment history retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Payment history retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PaymentLogResource')
                ),
                new OA\Property(
                    property: 'pagination',
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'User not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'User not found'),
            ]
        )
    )]
    public function paymentHistory(Request $request, int $user): JsonResponse
    {
        $userModel = $this->userService->getUserById($user);

        if (!$userModel) {
            return $this->notFound('User not found');
        }

        $perPage = (int) $request->query('per_page', $this->perPage);
        $payments = $this->userService->getPaymentHistory($userModel, $perPage);

        return $this->paginated(
            $payments,
            PaymentLogResource::class,
            'Payment history retrieved'
        );
    }
}

```

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Tenant\CreateTenantRequest;
use App\Http\Requests\User\ChangePasswordRequest;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Resources\PaymentLogResource;
use App\Http\Resources\SupportTicketResource;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserDashboardResource;
use App\Http\Resources\UserResource;
use App\Models\PricePlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * User Dashboard Controller (Self-Service)
 *
 * Handles user self-service operations including profile management,
 * tenant creation, and dashboard data.
 *
 * @package App\Http\Controllers\Api\V1\Landlord
 */
#[OA\Tag(
    name: 'User Dashboard',
    description: 'User self-service endpoints (Guard: api_user). Manage own profile, tenants, and view dashboard.'
)]
final class UserDashboardController extends BaseApiController
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    /**
     * Get dashboard statistics.
     */
    #[OA\Get(
        path: '/api/v1/dashboard',
        summary: 'Get dashboard stats',
        description: 'Get user dashboard statistics including tenant count, active packages, and support tickets',
        security: [['sanctum_user' => []]],
        tags: ['User Dashboard']
    )]
    #[OA\Response(
        response: 200,
        description: 'Dashboard data retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Dashboard data retrieved'),
                new OA\Property(property: 'data', ref: '#/components/schemas/UserDashboardResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    public function dashboard(): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $stats = $this->userService->getUserDashboardStats($user);

        return $this->success(
            new UserDashboardResource($stats),
            'Dashboard data retrieved'
        );
    }

    /**
     * Get own profile.
     */
    #[OA\Get(
        path: '/api/v1/profile',
        summary: 'Get own profile',
        description: 'Get the authenticated user\'s profile',
        security: [['sanctum_user' => []]],
        tags: ['User Dashboard']
    )]
    #[OA\Response(
        response: 200,
        description: 'Profile retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Profile retrieved'),
                new OA\Property(property: 'data', ref: '#/components/schemas/UserResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    public function profile(): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $user->load(['tenants', 'latestPaymentLog']);

        return $this->success(
            new UserResource($user),
            'Profile retrieved'
        );
    }

    /**
     * Update own profile.
     */
    #[OA\Put(
        path: '/api/v1/profile',
        summary: 'Update own profile',
        description: 'Update the authenticated user\'s profile',
        security: [['sanctum_user' => []]],
        tags: ['User Dashboard']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UserUpdateProfileRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Profile updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Profile updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/UserResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        try {
            $data = $request->validatedData();
            $updatedUser = $this->userService->updateProfile($user, $data);

            return $this->success(
                new UserResource($updatedUser),
                'Profile updated successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to update profile: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Change password.
     */
    #[OA\Post(
        path: '/api/v1/profile/change-password',
        summary: 'Change password',
        description: 'Change the authenticated user\'s password. This will revoke all existing tokens.',
        security: [['sanctum_user' => []]],
        tags: ['User Dashboard']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/ChangePasswordRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Password changed successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Password changed successfully. Please login again.'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid current password',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Current password is incorrect'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        try {
            $data = $request->validatedData();
            $this->userService->changePassword($user, $data['old_password'], $data['password']);

            return $this->success(null, 'Password changed successfully. Please login again.');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->error('Failed to change password: ' . $e->getMessage(), 500);
        }
    }

    /**
     * List own tenants.
     */
    #[OA\Get(
        path: '/api/v1/my-tenants',
        summary: 'List own tenants',
        description: 'Get list of all tenants owned by the authenticated user with status',
        security: [['sanctum_user' => []]],
        tags: ['User Dashboard']
    )]
    #[OA\Response(
        response: 200,
        description: 'Tenants retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Tenants retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/TenantResource')
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    public function tenants(): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $tenants = $this->userService->getUserTenants($user);

        return $this->success(
            TenantResource::collection($tenants),
            'Tenants retrieved'
        );
    }

    /**
     * Create a new tenant.
     */
    #[OA\Post(
        path: '/api/v1/my-tenants',
        summary: 'Create new tenant',
        description: 'Create a new tenant with plan selection. Database setup is asynchronous.',
        security: [['sanctum_user' => []]],
        tags: ['User Dashboard']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['subdomain', 'plan_id'],
            properties: [
                new OA\Property(
                    property: 'subdomain',
                    type: 'string',
                    example: 'my-property',
                    description: 'Unique subdomain (lowercase, alphanumeric with hyphens)'
                ),
                new OA\Property(
                    property: 'plan_id',
                    type: 'integer',
                    example: 1,
                    description: 'Price plan ID'
                ),
                new OA\Property(
                    property: 'theme',
                    type: 'string',
                    example: 'default',
                    nullable: true
                ),
                new OA\Property(
                    property: 'theme_code',
                    type: 'string',
                    example: '#3498DB',
                    nullable: true
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Tenant created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Tenant created successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'tenant', ref: '#/components/schemas/TenantResource'),
                        new OA\Property(property: 'message', type: 'string', example: 'Database setup is in progress.'),
                        new OA\Property(property: 'database_status', type: 'string', example: 'pending'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function createTenant(CreateTenantRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $data = $request->validatedData();

        // Check if subdomain already exists
        if (Tenant::find($data['subdomain'])) {
            return $this->validationError(
                ['subdomain' => ['This subdomain is already taken.']],
                'Validation failed'
            );
        }

        // Get the price plan
        $plan = PricePlan::find($data['plan_id']);

        if (!$plan) {
            return $this->validationError(
                ['plan_id' => ['Selected price plan does not exist.']],
                'Validation failed'
            );
        }

        try {
            $tenant = $this->userService->createTenantForUser($user, $plan, $data);

            return $this->created([
                'tenant' => new TenantResource($tenant->load(['domains', 'paymentLog'])),
                'message' => 'Database setup is in progress.',
                'database_status' => 'pending',
            ], 'Tenant created successfully');
        } catch (\Exception $e) {
            return $this->error('Failed to create tenant: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get own support tickets.
     */
    #[OA\Get(
        path: '/api/v1/my-tickets',
        summary: 'List own support tickets',
        description: 'Get paginated list of support tickets created by the authenticated user',
        security: [['sanctum_user' => []]],
        tags: ['User Dashboard']
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 10)
    )]
    #[OA\Response(
        response: 200,
        description: 'Support tickets retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Support tickets retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SupportTicketResource')
                ),
                new OA\Property(property: 'pagination', type: 'object'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    public function supportTickets(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $perPage = (int) $request->query('per_page', 10);
        $tickets = $this->userService->getUserSupportTickets($user, $perPage);

        return $this->paginated(
            $tickets,
            SupportTicketResource::class,
            'Support tickets retrieved'
        );
    }

    /**
     * Get payment history.
     */
    #[OA\Get(
        path: '/api/v1/my-payments',
        summary: 'Get own payment history',
        description: 'Get paginated payment history for the authenticated user',
        security: [['sanctum_user' => []]],
        tags: ['User Dashboard']
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15)
    )]
    #[OA\Response(
        response: 200,
        description: 'Payment history retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Payment history retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/PaymentLogResource')
                ),
                new OA\Property(property: 'pagination', type: 'object'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    public function paymentHistory(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $perPage = (int) $request->query('per_page', $this->perPage);
        $payments = $this->userService->getPaymentHistory($user, $perPage);

        return $this->paginated(
            $payments,
            PaymentLogResource::class,
            'Payment history retrieved'
        );
    }
}

```

Let me check if SupportTicketResource exists:

Good, the resource exists. Now let me read the current state of the routes file and add the new routes:

```php
use App\Http\Controllers\Api\V1\Auth\AdminAuthController;
use App\Http\Controllers\Api\V1\Auth\TenantUserAuthController;
use App\Http\Controllers\Api\V1\Auth\UserAuthController;
use App\Http\Controllers\Api\V1\Landlord\Admin\AdminController;
use App\Http\Controllers\Api\V1\Landlord\Admin\RoleController;
use App\Http\Controllers\Api\V1\Landlord\TenantController;
use App\Http\Controllers\Api\V1\Landlord\UserController;
use App\Http\Controllers\Api\V1\Landlord\UserDashboardController;
use Illuminate\Support\Facades\Route;
```

```php
    /*
    |--------------------------------------------------------------------------
    | Admin Protected Routes
    |--------------------------------------------------------------------------
    | Routes for platform administrators.
    | Guard: api_admin
    */
    Route::middleware('auth:api_admin')->prefix('admin')->name('admin.')->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Admin Management Routes
        |--------------------------------------------------------------------------
        */
        // Admin profile (own)
        Route::put('profile', [AdminController::class, 'updateProfile'])->name('profile.update');

        // Admin CRUD
        Route::get('admins', [AdminController::class, 'index'])->name('admins.index');
        Route::post('admins', [AdminController::class, 'store'])->name('admins.store');
        Route::get('admins/{admin}', [AdminController::class, 'show'])->name('admins.show');
        Route::put('admins/{admin}', [AdminController::class, 'update'])->name('admins.update');
        Route::delete('admins/{admin}', [AdminController::class, 'destroy'])->name('admins.destroy');
        Route::put('admins/{admin}/password', [AdminController::class, 'updatePassword'])->name('admins.password');

        /*
        |--------------------------------------------------------------------------
        | Role Management Routes
        |--------------------------------------------------------------------------
        */
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        Route::get('roles/{role}', [RoleController::class, 'show'])->name('roles.show');
        Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');

        // Permissions
        Route::get('permissions', [RoleController::class, 'permissions'])->name('permissions.index');

        /*
        |--------------------------------------------------------------------------
        | User Management Routes (Admin managing users)
        |--------------------------------------------------------------------------
        */
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
        Route::get('users/{user}/payments', [UserController::class, 'paymentHistory'])->name('users.payments');
    });

    /*
    |--------------------------------------------------------------------------
    | User Dashboard Routes (Self-Service)
    |--------------------------------------------------------------------------
    | Routes for authenticated users to manage their own profile and data.
    | Guard: api_user
    */
    Route::middleware('auth:api_user')->group(function () {
        // Dashboard
        Route::get('dashboard', [UserDashboardController::class, 'dashboard'])->name('dashboard');

        // Profile management
        Route::get('profile', [UserDashboardController::class, 'profile'])->name('profile');
        Route::put('profile', [UserDashboardController::class, 'updateProfile'])->name('profile.update');
        Route::post('profile/change-password', [UserDashboardController::class, 'changePassword'])->name('profile.password');

        // User's own tenants (alternative to /tenants)
        Route::get('my-tenants', [UserDashboardController::class, 'tenants'])->name('my-tenants.index');
        Route::post('my-tenants', [UserDashboardController::class, 'createTenant'])->name('my-tenants.store');

        // Support tickets
        Route::get('my-tickets', [UserDashboardController::class, 'supportTickets'])->name('my-tickets.index');

        // Payment history
        Route::get('my-payments', [UserDashboardController::class, 'paymentHistory'])->name('my-payments.index');
    });
```

I've created the complete User management API for the landlord system. Here's a summary of all the files created:

## Files Created

### 1. Controllers (`app/Http/Controllers/Api/V1/Landlord/`)

**UserController.php** - Admin managing users:
- `index()` - Paginated users list with filters (search, has_subdomain, email_verified)
- `show(id)` - User with all tenants and payment history
- `update(id)` - Update user details
- `destroy(id)` - Deactivate user (revoke tokens)
- `impersonate(id)` - Get token to act as user (2-hour expiry)
- `paymentHistory(id)` - User's payment logs

**UserDashboardController.php** - User self-service:
- `dashboard()` - Stats (tenants count, active packages, support tickets)
- `profile()` - Get own profile
- `updateProfile(UpdateProfileRequest)` - Update own profile
- `changePassword(ChangePasswordRequest)` - Change password (requires old password)
- `tenants()` - List own tenants with status
- `createTenant(CreateTenantRequest)` - Create with plan selection
- `supportTickets()` - Own support tickets
- `paymentHistory()` - Own payment history

### 2. Form Requests (`app/Http/Requests/User/`)
- `UpdateProfileRequest.php` - User profile update
- `ChangePasswordRequest.php` - Password change with old password verification
- `AdminUpdateUserRequest.php` - Admin updating user details

### 3. API Resources (`app/Http/Resources/`)
- `UserCollection.php` - Paginated user collection with metadata
- `UserDashboardResource.php` - Dashboard statistics
- `TenantCollection.php` - Tenant collection

### 4. Service (`app/Services/UserService.php`)
- `getUserList()`, `getUserById()`, `updateUser()`, `deactivateUser()`, `deleteUser()`
- `generateImpersonationToken()`, `getPaymentHistory()`
- `getUserDashboardStats()`, `updateProfile()`, `changePassword()`
- `getUserTenants()`, `getUserSupportTickets()`, `createTenantForUser()`

### 5. Updated Routes (`routes/api.php`)

## API Endpoints

### Admin Endpoints (Guard: `api_admin`)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/admin/users` | List users |
| GET | `/api/v1/admin/users/{id}` | Get user details |
| PUT | `/api/v1/admin/users/{id}` | Update user |
| DELETE | `/api/v1/admin/users/{id}` | Deactivate user |
| POST | `/api/v1/admin/users/{id}/impersonate` | Impersonate user |
| GET | `/api/v1/admin/users/{id}/payments` | User payment history |

### User Self-Service Endpoints (Guard: `api_user`)
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/dashboard` | Dashboard stats |
| GET | `/api/v1/profile` | Get own profile |
| PUT | `/api/v1/profile` | Update profile |
| POST | `/api/v1/profile/change-password` | Change password |
| GET | `/api/v1/my-tenants` | List own tenants |
| POST | `/api/v1/my-tenants` | Create tenant |
| GET | `/api/v1/my-tickets` | Support tickets |
| GET | `/api/v1/my-payments` | Payment history |

### Previously Created (Admin Management)
| Method | Endpoint | Description |
|--------|----------|-------------|
| PUT | `/api/v1/admin/profile` | Update admin profile |
| GET/POST/PUT/DELETE | `/api/v1/admin/admins/*` | Admin CRUD |
| GET/POST/PUT/DELETE | `/api/v1/admin/roles/*` | Role CRUD |
| GET | `/api/v1/admin/permissions` | List permissions |

