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
