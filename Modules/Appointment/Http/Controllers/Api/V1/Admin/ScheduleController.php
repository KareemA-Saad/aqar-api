<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Entities\AppointmentDay;
use Modules\Appointment\Entities\AppointmentDayType;
use Modules\Appointment\Entities\AppointmentSchedule;
use Modules\Appointment\Http\Resources\ScheduleResource;
use Modules\Appointment\Services\ScheduleService;
use Modules\Appointment\Services\SlotAvailabilityService;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin Schedule Controller
 *
 * Manages appointment schedules, days, and time slots within a tenant context.
 *
 * @package Modules\Appointment\Http\Controllers\Api\V1\Admin
 */
#[OA\Tag(
    name: 'Tenant Admin - Appointment Schedules',
    description: 'Manage appointment schedules and time slots within a tenant'
)]
final class ScheduleController extends BaseApiController
{
    public function __construct(
        private readonly ScheduleService $scheduleService,
        private readonly SlotAvailabilityService $slotAvailabilityService,
    ) {}

    /**
     * Get all days with their schedules.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/appointments/schedules',
        summary: 'List all schedules',
        description: 'Get all appointment days with their time slots',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Schedules']
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
        description: 'Schedules retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Schedules retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'days', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'day_types', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(): JsonResponse
    {
        $days = $this->scheduleService->getAllDays();
        $dayTypes = $this->scheduleService->getAllDayTypes();

        return $this->success([
            'days' => $days,
            'day_types' => $dayTypes,
        ], 'Schedules retrieved successfully');
    }

    /**
     * Get availability for a specific date.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/appointments/schedules/availability',
        summary: 'Get availability for date',
        description: 'Get available and booked slots for a specific date',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Schedules']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'date',
        in: 'query',
        required: true,
        description: 'Date to check availability (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date', example: '2024-01-15')
    )]
    #[OA\Parameter(
        name: 'appointment_id',
        in: 'query',
        description: 'Filter by appointment ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Availability retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Availability retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'date', type: 'string', example: '2024-01-15'),
                        new OA\Property(property: 'day', type: 'string', example: 'Monday'),
                        new OA\Property(property: 'available', type: 'boolean', example: true),
                        new OA\Property(property: 'total_slots', type: 'integer', example: 10),
                        new OA\Property(property: 'available_slots', type: 'integer', example: 8),
                        new OA\Property(property: 'slots', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function availability(Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date', 'date_format:Y-m-d'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
        ]);

        $availability = $this->slotAvailabilityService->getAvailableSlotsForDate(
            $request->date,
            $request->appointment_id
        );

        return $this->success($availability, 'Availability retrieved successfully');
    }

    /**
     * Get availability for a date range.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/appointments/schedules/availability-range',
        summary: 'Get availability for date range',
        description: 'Get availability summary for a range of dates',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Schedules']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'start_date',
        in: 'query',
        required: true,
        description: 'Start date (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'end_date',
        in: 'query',
        required: true,
        description: 'End date (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'appointment_id',
        in: 'query',
        description: 'Filter by appointment ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Availability range retrieved successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function availabilityRange(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => ['required', 'date', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
        ]);

        $availability = $this->slotAvailabilityService->getAvailabilityForDateRange(
            $request->start_date,
            $request->end_date,
            $request->appointment_id
        );

        return $this->success($availability, 'Availability range retrieved successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Day Management
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new day.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/schedules/days',
        summary: 'Create day',
        description: 'Create a new appointment day (max 7 days)',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Schedules']
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
            required: ['day', 'key'],
            properties: [
                new OA\Property(property: 'day', type: 'string', example: 'Monday', description: 'Translated day name'),
                new OA\Property(property: 'key', type: 'string', example: 'Monday', description: 'Day key (English day name)'),
                new OA\Property(property: 'status', type: 'boolean', example: true),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Day created successfully'
    )]
    #[OA\Response(response: 400, description: 'Maximum days limit reached')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function storeDay(Request $request): JsonResponse
    {
        $request->validate([
            'day' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
            'status' => ['nullable', 'boolean'],
        ]);

        try {
            $day = $this->scheduleService->createDay($request->all());
            return $this->success($day, 'Day created successfully', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Update a day.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/appointments/schedules/days/{id}',
        summary: 'Update day',
        description: 'Update an appointment day',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Schedules']
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
        description: 'Day ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'day', type: 'string', example: 'Monday'),
                new OA\Property(property: 'status', type: 'boolean', example: true),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Day updated successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Day not found')]
    public function updateDay(Request $request, int $id): JsonResponse
    {
        $day = AppointmentDay::find($id);

        if (!$day) {
            return $this->error('Day not found', 404);
        }

        $request->validate([
            'day' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
        ]);

        $day = $this->scheduleService->updateDay($day, $request->all());

        return $this->success($day, 'Day updated successfully');
    }

    /**
     * Delete a day.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/appointments/schedules/days/{id}',
        summary: 'Delete day',
        description: 'Delete a day and all its schedules',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Schedules']
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
        description: 'Day ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Day deleted successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Day not found')]
    public function destroyDay(int $id): JsonResponse
    {
        $day = AppointmentDay::find($id);

        if (!$day) {
            return $this->error('Day not found', 404);
        }

        $this->scheduleService->deleteDay($day);

        return $this->success(null, 'Day deleted successfully');
    }

    /**
     * Toggle day status.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/schedules/days/{id}/toggle-status',
        summary: 'Toggle day status',
        description: 'Toggle the active/inactive status of a day',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Schedules']
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
        description: 'Day ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Day status toggled successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Day not found')]
    public function toggleDayStatus(int $id): JsonResponse
    {
        $day = AppointmentDay::find($id);

        if (!$day) {
            return $this->error('Day not found', 404);
        }

        $day = $this->scheduleService->toggleDayStatus($day);

        return $this->success($day, 'Day status toggled successfully');
    }

    /*
    |--------------------------------------------------------------------------
    | Time Slot Management
    |--------------------------------------------------------------------------
    */

    /**
     * Create a time slot.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/schedules/slots',
        summary: 'Create time slot',
        description: 'Create a new time slot for a day',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Schedules']
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
            required: ['day_id', 'time'],
            properties: [
                new OA\Property(property: 'day_id', type: 'integer', example: 1),
                new OA\Property(property: 'time', type: 'string', example: '09:00 - 10:00', description: 'Time range format'),
                new OA\Property(property: 'day_type', type: 'integer', example: 1, description: 'Day type ID (Morning, Evening, etc.)'),
                new OA\Property(property: 'allow_multiple', type: 'boolean', example: false, description: 'Allow multiple bookings'),
                new OA\Property(property: 'status', type: 'boolean', example: true),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Time slot created successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function storeSlot(Request $request): JsonResponse
    {
        $request->validate([
            'day_id' => ['required', 'integer', 'exists:appointment_days,id'],
            'time' => ['required', 'string', 'regex:/^\d{2}:\d{2}\s-\s\d{2}:\d{2}$/'],
            'day_type' => ['nullable', 'integer', 'exists:appointment_day_types,id'],
            'allow_multiple' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
        ]);

        $schedule = $this->scheduleService->createSchedule($request->all());

        return $this->success(
            new ScheduleResource($schedule->fresh(['day', 'type'])),
            'Time slot created successfully',
            201
        );
    }

    /**
     * Update a time slot.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/appointments/schedules/slots/{id}',
        summary: 'Update time slot',
        description: 'Update an existing time slot',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Schedules']
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
        description: 'Time slot ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'day_id', type: 'integer', example: 1),
                new OA\Property(property: 'time', type: 'string', example: '09:00 - 10:00'),
                new OA\Property(property: 'day_type', type: 'integer', example: 1),
                new OA\Property(property: 'allow_multiple', type: 'boolean', example: false),
                new OA\Property(property: 'status', type: 'boolean', example: true),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Time slot updated successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Time slot not found')]
    public function updateSlot(Request $request, int $id): JsonResponse
    {
        $schedule = AppointmentSchedule::find($id);

        if (!$schedule) {
            return $this->error('Time slot not found', 404);
        }

        $request->validate([
            'day_id' => ['nullable', 'integer', 'exists:appointment_days,id'],
            'time' => ['nullable', 'string', 'regex:/^\d{2}:\d{2}\s-\s\d{2}:\d{2}$/'],
            'day_type' => ['nullable', 'integer', 'exists:appointment_day_types,id'],
            'allow_multiple' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
        ]);

        $schedule = $this->scheduleService->updateSchedule($schedule, $request->all());

        return $this->success(new ScheduleResource($schedule), 'Time slot updated successfully');
    }

    /**
     * Delete a time slot.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/appointments/schedules/slots/{id}',
        summary: 'Delete time slot',
        description: 'Delete a time slot',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Schedules']
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
        description: 'Time slot ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Time slot deleted successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Time slot not found')]
    public function destroySlot(int $id): JsonResponse
    {
        $schedule = AppointmentSchedule::find($id);

        if (!$schedule) {
            return $this->error('Time slot not found', 404);
        }

        $this->scheduleService->deleteSchedule($schedule);

        return $this->success(null, 'Time slot deleted successfully');
    }

    /**
     * Block a time slot.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/schedules/slots/{id}/block',
        summary: 'Block time slot',
        description: 'Block a time slot (set status to inactive)',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Schedules']
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
        description: 'Time slot ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Time slot blocked successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Time slot not found')]
    public function blockSlot(int $id): JsonResponse
    {
        try {
            $schedule = $this->scheduleService->blockSchedule($id);
            return $this->success(new ScheduleResource($schedule), 'Time slot blocked successfully');
        } catch (\Exception $e) {
            return $this->error('Time slot not found', 404);
        }
    }

    /**
     * Unblock a time slot.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/schedules/slots/{id}/unblock',
        summary: 'Unblock time slot',
        description: 'Unblock a time slot (set status to active)',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Schedules']
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
        description: 'Time slot ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Time slot unblocked successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Time slot not found')]
    public function unblockSlot(int $id): JsonResponse
    {
        try {
            $schedule = $this->scheduleService->unblockSchedule($id);
            return $this->success(new ScheduleResource($schedule), 'Time slot unblocked successfully');
        } catch (\Exception $e) {
            return $this->error('Time slot not found', 404);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Day Type Management
    |--------------------------------------------------------------------------
    */

    /**
     * List all day types.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/appointments/schedules/day-types',
        summary: 'List day types',
        description: 'Get all day types (Morning, Afternoon, Evening, etc.)',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Schedules']
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
        description: 'Day types retrieved successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function dayTypes(): JsonResponse
    {
        $dayTypes = $this->scheduleService->getAllDayTypes();
        return $this->success($dayTypes, 'Day types retrieved successfully');
    }

    /**
     * Create a day type.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/schedules/day-types',
        summary: 'Create day type',
        description: 'Create a new day type',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Schedules']
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
            required: ['title'],
            properties: [
                new OA\Property(property: 'title', type: 'string', example: 'Morning'),
                new OA\Property(property: 'status', type: 'boolean', example: true),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Day type created successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function storeDayType(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
        ]);

        $dayType = $this->scheduleService->createDayType($request->all());

        return $this->success($dayType, 'Day type created successfully', 201);
    }

    /**
     * Update a day type.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/appointments/schedules/day-types/{id}',
        summary: 'Update day type',
        description: 'Update an existing day type',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Schedules']
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
        description: 'Day type ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'title', type: 'string', example: 'Morning'),
                new OA\Property(property: 'status', type: 'boolean', example: true),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Day type updated successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Day type not found')]
    public function updateDayType(Request $request, int $id): JsonResponse
    {
        $dayType = AppointmentDayType::find($id);

        if (!$dayType) {
            return $this->error('Day type not found', 404);
        }

        $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'boolean'],
        ]);

        $dayType = $this->scheduleService->updateDayType($dayType, $request->all());

        return $this->success($dayType, 'Day type updated successfully');
    }

    /**
     * Delete a day type.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/appointments/schedules/day-types/{id}',
        summary: 'Delete day type',
        description: 'Delete a day type',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Schedules']
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
        description: 'Day type ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Day type deleted successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Day type not found')]
    public function destroyDayType(int $id): JsonResponse
    {
        $dayType = AppointmentDayType::find($id);

        if (!$dayType) {
            return $this->error('Day type not found', 404);
        }

        $this->scheduleService->deleteDayType($dayType);

        return $this->success(null, 'Day type deleted successfully');
    }
}
