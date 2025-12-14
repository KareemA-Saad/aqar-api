<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\SupportTicket\StoreDepartmentRequest;
use App\Http\Resources\SupportDepartmentResource;
use App\Models\Admin;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Admin Support Department Controller
 *
 * Handles support department management operations for administrators.
 *
 * @package App\Http\Controllers\Api\V1\Landlord\Admin
 */
#[OA\Tag(
    name: 'Admin Support Departments',
    description: 'Support department management endpoints for administrators (Guard: api_admin)'
)]
final class SupportDepartmentController extends BaseApiController
{
    public function __construct(
        private readonly SupportTicketService $ticketService,
    ) {}

    /**
     * List all support departments.
     */
    #[OA\Get(
        path: '/api/v1/admin/support-departments',
        summary: 'List all departments',
        description: 'Get paginated list of all support departments with ticket counts',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Support Departments']
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
        description: 'Departments retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Departments retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SupportDepartmentResource')
                ),
                new OA\Property(
                    property: 'pagination',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 10),
                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 1),
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
        /** @var Admin|null $admin */
        $admin = auth('api_admin')->user();

        if (!$admin) {
            return $this->unauthorized();
        }

        $perPage = (int) $request->query('per_page', $this->perPage);
        $departments = $this->ticketService->getDepartmentList($perPage);

        return $this->paginated(
            $departments,
            SupportDepartmentResource::class,
            'Departments retrieved'
        );
    }

    /**
     * Create a new support department.
     */
    #[OA\Post(
        path: '/api/v1/admin/support-departments',
        summary: 'Create department',
        description: 'Create a new support department',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Support Departments']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StoreDepartmentRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Department created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Department created'),
                new OA\Property(property: 'data', ref: '#/components/schemas/SupportDepartmentResource'),
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
                    example: ['name' => ['A department with this name already exists.']]
                ),
            ]
        )
    )]
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        /** @var Admin|null $admin */
        $admin = auth('api_admin')->user();

        if (!$admin) {
            return $this->unauthorized();
        }

        try {
            $data = $request->validatedData();
            $department = $this->ticketService->createDepartment($data);

            return $this->created(
                new SupportDepartmentResource($department),
                'Department created'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to create department: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get department details.
     */
    #[OA\Get(
        path: '/api/v1/admin/support-departments/{department}',
        summary: 'Get department details',
        description: 'Retrieve a specific support department',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Support Departments']
    )]
    #[OA\Parameter(
        name: 'department',
        in: 'path',
        required: true,
        description: 'Department ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Department retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Department retrieved'),
                new OA\Property(property: 'data', ref: '#/components/schemas/SupportDepartmentResource'),
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
        description: 'Department not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Department not found'),
            ]
        )
    )]
    public function show(int $department): JsonResponse
    {
        /** @var Admin|null $admin */
        $admin = auth('api_admin')->user();

        if (!$admin) {
            return $this->unauthorized();
        }

        $departmentModel = $this->ticketService->getDepartmentById($department);

        if (!$departmentModel) {
            return $this->notFound('Department not found');
        }

        return $this->success(
            new SupportDepartmentResource($departmentModel),
            'Department retrieved'
        );
    }

    /**
     * Update a support department.
     */
    #[OA\Put(
        path: '/api/v1/admin/support-departments/{department}',
        summary: 'Update department',
        description: 'Update an existing support department',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Support Departments']
    )]
    #[OA\Parameter(
        name: 'department',
        in: 'path',
        required: true,
        description: 'Department ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StoreDepartmentRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Department updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Department updated'),
                new OA\Property(property: 'data', ref: '#/components/schemas/SupportDepartmentResource'),
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
        description: 'Department not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Department not found'),
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
    public function update(StoreDepartmentRequest $request, int $department): JsonResponse
    {
        /** @var Admin|null $admin */
        $admin = auth('api_admin')->user();

        if (!$admin) {
            return $this->unauthorized();
        }

        $departmentModel = $this->ticketService->getDepartmentById($department);

        if (!$departmentModel) {
            return $this->notFound('Department not found');
        }

        try {
            $data = $request->validatedData();
            $updatedDepartment = $this->ticketService->updateDepartment($departmentModel, $data);

            return $this->success(
                new SupportDepartmentResource($updatedDepartment),
                'Department updated'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to update department: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete a support department.
     */
    #[OA\Delete(
        path: '/api/v1/admin/support-departments/{department}',
        summary: 'Delete department',
        description: 'Delete a support department (only if no tickets exist)',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Support Departments']
    )]
    #[OA\Parameter(
        name: 'department',
        in: 'path',
        required: true,
        description: 'Department ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Department deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Department deleted'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Cannot delete department with tickets',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Cannot delete department with existing tickets'),
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
        description: 'Department not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Department not found'),
            ]
        )
    )]
    public function destroy(int $department): JsonResponse
    {
        /** @var Admin|null $admin */
        $admin = auth('api_admin')->user();

        if (!$admin) {
            return $this->unauthorized();
        }

        $departmentModel = $this->ticketService->getDepartmentById($department);

        if (!$departmentModel) {
            return $this->notFound('Department not found');
        }

        try {
            $this->ticketService->deleteDepartment($departmentModel);

            return $this->success(null, 'Department deleted');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }
}
