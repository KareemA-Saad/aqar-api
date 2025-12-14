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
