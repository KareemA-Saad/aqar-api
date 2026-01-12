<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Entities\Appointment;
use Modules\Appointment\Http\Requests\Api\V1\StoreAppointmentRequest;
use Modules\Appointment\Http\Requests\Api\V1\UpdateAppointmentRequest;
use Modules\Appointment\Http\Resources\AppointmentCollection;
use Modules\Appointment\Http\Resources\AppointmentResource;
use Modules\Appointment\Services\AppointmentService;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin Appointment Controller
 *
 * Manages appointments within a tenant context.
 * Requires tenant admin authentication and tenant context.
 *
 * @package Modules\Appointment\Http\Controllers\Api\V1\Admin
 */
#[OA\Tag(
    name: 'Tenant Admin - Appointments',
    description: 'Manage appointments within a tenant'
)]
final class AppointmentController extends BaseApiController
{
    public function __construct(
        private readonly AppointmentService $appointmentService,
    ) {}

    /**
     * List all appointments with pagination and filters.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/appointments',
        summary: 'List appointments',
        description: 'Get paginated list of appointments with optional filters',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointments']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        description: 'Search by title or description',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'category_id',
        in: 'query',
        description: 'Filter by category ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'subcategory_id',
        in: 'query',
        description: 'Filter by subcategory ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        description: 'Filter by status (1=active, 0=inactive)',
        schema: new OA\Schema(type: 'integer', enum: [0, 1])
    )]
    #[OA\Parameter(
        name: 'is_popular',
        in: 'query',
        description: 'Filter by popular status',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Parameter(
        name: 'min_price',
        in: 'query',
        description: 'Minimum price filter',
        schema: new OA\Schema(type: 'number')
    )]
    #[OA\Parameter(
        name: 'max_price',
        in: 'query',
        description: 'Maximum price filter',
        schema: new OA\Schema(type: 'number')
    )]
    #[OA\Parameter(
        name: 'sort_by',
        in: 'query',
        description: 'Sort field',
        schema: new OA\Schema(type: 'string', enum: ['title', 'price', 'views', 'created_at', 'updated_at', 'status'])
    )]
    #[OA\Parameter(
        name: 'sort_order',
        in: 'query',
        description: 'Sort direction',
        schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15, minimum: 1, maximum: 100)
    )]
    #[OA\Response(
        response: 200,
        description: 'Appointments retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Appointments retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AppointmentCollection'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'category_id',
            'subcategory_id',
            'status',
            'is_popular',
            'min_price',
            'max_price',
            'sort_by',
            'sort_order',
        ]);

        $perPage = min((int) $request->input('per_page', 15), 100);

        $appointments = $this->appointmentService->getAppointments($filters, $perPage);

        return $this->success(
            new AppointmentCollection($appointments),
            'Appointments retrieved successfully'
        );
    }

    /**
     * Create a new appointment.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments',
        summary: 'Create appointment',
        description: 'Create a new appointment',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointments']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StoreAppointmentRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Appointment created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Appointment created successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AppointmentResource'),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Package limit reached')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->createAppointment($request->validated());

            return $this->success(
                new AppointmentResource($appointment),
                'Appointment created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get a single appointment.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/appointments/{id}',
        summary: 'Get appointment',
        description: 'Get a single appointment by ID',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointments']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Appointment ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Appointment retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Appointment retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AppointmentResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Appointment not found')]
    public function show(int $id): JsonResponse
    {
        $appointment = $this->appointmentService->findById($id);

        if (!$appointment) {
            return $this->error('Appointment not found', 404);
        }

        return $this->success(
            new AppointmentResource($appointment),
            'Appointment retrieved successfully'
        );
    }

    /**
     * Update an appointment.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/appointments/{id}',
        summary: 'Update appointment',
        description: 'Update an existing appointment',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointments']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Appointment ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UpdateAppointmentRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Appointment updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Appointment updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AppointmentResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Appointment not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function update(UpdateAppointmentRequest $request, int $id): JsonResponse
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return $this->error('Appointment not found', 404);
        }

        $appointment = $this->appointmentService->updateAppointment($appointment, $request->validated());

        return $this->success(
            new AppointmentResource($appointment),
            'Appointment updated successfully'
        );
    }

    /**
     * Delete an appointment.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/appointments/{id}',
        summary: 'Delete appointment',
        description: 'Delete an appointment',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointments']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Appointment ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Appointment deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Appointment deleted successfully'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Appointment not found')]
    public function destroy(int $id): JsonResponse
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return $this->error('Appointment not found', 404);
        }

        $this->appointmentService->deleteAppointment($appointment);

        return $this->success(null, 'Appointment deleted successfully');
    }

    /**
     * Toggle appointment status.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/{id}/toggle-status',
        summary: 'Toggle appointment status',
        description: 'Toggle the active/inactive status of an appointment',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointments']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Appointment ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Status toggled successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Appointment status toggled successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AppointmentResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Appointment not found')]
    public function toggleStatus(int $id): JsonResponse
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return $this->error('Appointment not found', 404);
        }

        $appointment = $this->appointmentService->toggleStatus($appointment);

        return $this->success(
            new AppointmentResource($appointment),
            'Appointment status toggled successfully'
        );
    }

    /**
     * Clone an appointment.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/{id}/clone',
        summary: 'Clone appointment',
        description: 'Create a copy of an existing appointment',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointments']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Appointment ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 201,
        description: 'Appointment cloned successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Appointment cloned successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AppointmentResource'),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Package limit reached')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Appointment not found')]
    public function clone(int $id): JsonResponse
    {
        $appointment = Appointment::find($id);

        if (!$appointment) {
            return $this->error('Appointment not found', 404);
        }

        try {
            $clonedAppointment = $this->appointmentService->cloneAppointment($appointment);

            return $this->success(
                new AppointmentResource($clonedAppointment),
                'Appointment cloned successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Bulk delete appointments.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/bulk-delete',
        summary: 'Bulk delete appointments',
        description: 'Delete multiple appointments at once',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointments']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['ids'],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    example: [1, 2, 3]
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Appointments deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: '3 appointments deleted successfully'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:appointments,id'],
        ]);

        $count = $this->appointmentService->bulkDelete($request->ids);

        return $this->success(null, "{$count} appointments deleted successfully");
    }

    /**
     * Check package limit.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/appointments/package-limit',
        summary: 'Check package limit',
        description: 'Check if more appointments can be created based on package limits',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointments']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Response(
        response: 200,
        description: 'Package limit info retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Package limit info retrieved'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'allowed', type: 'boolean', example: true),
                        new OA\Property(property: 'current', type: 'integer', example: 5),
                        new OA\Property(property: 'limit', type: 'integer', example: 10, nullable: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Appointment creation allowed'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function packageLimit(): JsonResponse
    {
        $limitInfo = $this->appointmentService->checkPackageLimit();

        return $this->success($limitInfo, 'Package limit info retrieved');
    }
}
