<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Entities\AppointmentPaymentLog;
use Modules\Appointment\Http\Requests\Api\V1\UpdateBookingStatusRequest;
use Modules\Appointment\Http\Requests\Api\V1\RescheduleBookingRequest;
use Modules\Appointment\Http\Resources\BookingCollection;
use Modules\Appointment\Http\Resources\BookingResource;
use Modules\Appointment\Services\AppointmentBookingService;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin Booking Controller
 *
 * Manages appointment bookings/orders within a tenant context.
 *
 * @package Modules\Appointment\Http\Controllers\Api\V1\Admin
 */
#[OA\Tag(
    name: 'Tenant Admin - Appointment Bookings',
    description: 'Manage appointment bookings within a tenant'
)]
final class BookingController extends BaseApiController
{
    public function __construct(
        private readonly AppointmentBookingService $bookingService,
    ) {}

    /**
     * List all bookings with pagination and filters.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/appointments/bookings',
        summary: 'List bookings',
        description: 'Get paginated list of appointment bookings with optional filters',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Bookings']
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
        description: 'Search by name, email, phone, or transaction ID',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'appointment_id',
        in: 'query',
        description: 'Filter by appointment ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'user_id',
        in: 'query',
        description: 'Filter by user ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        description: 'Filter by booking status',
        schema: new OA\Schema(type: 'string', enum: ['pending', 'confirmed', 'complete', 'cancelled', 'rejected'])
    )]
    #[OA\Parameter(
        name: 'payment_status',
        in: 'query',
        description: 'Filter by payment status',
        schema: new OA\Schema(type: 'string', enum: ['pending', 'complete', 'failed'])
    )]
    #[OA\Parameter(
        name: 'payment_gateway',
        in: 'query',
        description: 'Filter by payment gateway',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'date_from',
        in: 'query',
        description: 'Filter by appointment date from (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'date_to',
        in: 'query',
        description: 'Filter by appointment date to (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'sort_by',
        in: 'query',
        description: 'Sort field',
        schema: new OA\Schema(type: 'string', enum: ['name', 'email', 'appointment_date', 'total_amount', 'status', 'created_at'])
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
        description: 'Bookings retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Bookings retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BookingCollection'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'appointment_id',
            'user_id',
            'status',
            'payment_status',
            'payment_gateway',
            'date_from',
            'date_to',
            'sort_by',
            'sort_order',
        ]);

        $perPage = min((int) $request->input('per_page', 15), 100);

        $bookings = $this->bookingService->getBookings($filters, $perPage);

        return $this->success(
            new BookingCollection($bookings),
            'Bookings retrieved successfully'
        );
    }

    /**
     * Get a single booking.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/appointments/bookings/{id}',
        summary: 'Get booking',
        description: 'Get a single booking by ID',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Bookings']
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
        description: 'Booking ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Booking retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Booking retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BookingResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    public function show(int $id): JsonResponse
    {
        $booking = $this->bookingService->findById($id);

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        return $this->success(
            new BookingResource($booking),
            'Booking retrieved successfully'
        );
    }

    /**
     * Update booking status.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/appointments/bookings/{id}/status',
        summary: 'Update booking status',
        description: 'Update the status of a booking',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Bookings']
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
        description: 'Booking ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['status'],
            properties: [
                new OA\Property(property: 'status', type: 'string', enum: ['pending', 'confirmed', 'complete', 'cancelled', 'rejected']),
                new OA\Property(property: 'reason', type: 'string', nullable: true, description: 'Reason for status change (for cancellation/rejection)'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Booking status updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Booking status updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BookingResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function updateStatus(UpdateBookingStatusRequest $request, int $id): JsonResponse
    {
        $booking = AppointmentPaymentLog::find($id);

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        $booking = $this->bookingService->updateStatus(
            $booking,
            $request->status,
            $request->reason
        );

        return $this->success(
            new BookingResource($booking),
            'Booking status updated successfully'
        );
    }

    /**
     * Confirm a booking.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/bookings/{id}/confirm',
        summary: 'Confirm booking',
        description: 'Confirm a pending booking',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Bookings']
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
        description: 'Booking ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Booking confirmed successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    public function confirm(int $id): JsonResponse
    {
        $booking = AppointmentPaymentLog::find($id);

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        $booking = $this->bookingService->confirmBooking($booking);

        return $this->success(
            new BookingResource($booking),
            'Booking confirmed successfully'
        );
    }

    /**
     * Complete a booking.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/bookings/{id}/complete',
        summary: 'Complete booking',
        description: 'Mark a booking as completed',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Bookings']
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
        description: 'Booking ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Booking completed successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    public function complete(int $id): JsonResponse
    {
        $booking = AppointmentPaymentLog::find($id);

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        $booking = $this->bookingService->completeBooking($booking);

        return $this->success(
            new BookingResource($booking),
            'Booking completed successfully'
        );
    }

    /**
     * Cancel a booking.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/bookings/{id}/cancel',
        summary: 'Cancel booking',
        description: 'Cancel a booking',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Bookings']
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
        description: 'Booking ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'reason', type: 'string', nullable: true, description: 'Cancellation reason'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Booking cancelled successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    public function cancel(Request $request, int $id): JsonResponse
    {
        $booking = AppointmentPaymentLog::find($id);

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        $booking = $this->bookingService->cancelBooking($booking, $request->reason);

        return $this->success(
            new BookingResource($booking),
            'Booking cancelled successfully'
        );
    }

    /**
     * Reschedule a booking.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/bookings/{id}/reschedule',
        summary: 'Reschedule booking',
        description: 'Reschedule a booking to a new date/time',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Bookings']
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
        description: 'Booking ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/RescheduleBookingRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Booking rescheduled successfully'
    )]
    #[OA\Response(response: 400, description: 'Slot not available')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function reschedule(RescheduleBookingRequest $request, int $id): JsonResponse
    {
        $booking = AppointmentPaymentLog::find($id);

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        try {
            $booking = $this->bookingService->rescheduleBooking(
                $booking,
                $request->appointment_date,
                $request->appointment_time
            );

            return $this->success(
                new BookingResource($booking),
                'Booking rescheduled successfully'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Approve manual payment.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/bookings/{id}/approve-payment',
        summary: 'Approve manual payment',
        description: 'Approve a manual payment and confirm the booking',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Bookings']
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
        description: 'Booking ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Payment approved successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    public function approvePayment(int $id): JsonResponse
    {
        $booking = AppointmentPaymentLog::find($id);

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        $booking = $this->bookingService->approveManualPayment($booking);

        return $this->success(
            new BookingResource($booking),
            'Payment approved successfully'
        );
    }

    /**
     * Delete a booking.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/appointments/bookings/{id}',
        summary: 'Delete booking',
        description: 'Delete a booking record',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Bookings']
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
        description: 'Booking ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Booking deleted successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    public function destroy(int $id): JsonResponse
    {
        $booking = AppointmentPaymentLog::find($id);

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        $this->bookingService->deleteBooking($booking);

        return $this->success(null, 'Booking deleted successfully');
    }

    /**
     * Get booking statistics.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/appointments/bookings/stats',
        summary: 'Get booking statistics',
        description: 'Get booking statistics and analytics',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Bookings']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'date_from',
        in: 'query',
        description: 'Filter by date from (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'date_to',
        in: 'query',
        description: 'Filter by date to (Y-m-d)',
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
        description: 'Statistics retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Statistics retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'total_bookings', type: 'integer', example: 100),
                        new OA\Property(property: 'pending', type: 'integer', example: 10),
                        new OA\Property(property: 'confirmed', type: 'integer', example: 30),
                        new OA\Property(property: 'completed', type: 'integer', example: 50),
                        new OA\Property(property: 'cancelled', type: 'integer', example: 10),
                        new OA\Property(property: 'total_revenue', type: 'number', example: 5000.00),
                        new OA\Property(property: 'pending_revenue', type: 'number', example: 500.00),
                        new OA\Property(property: 'by_gateway', type: 'object'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function stats(Request $request): JsonResponse
    {
        $filters = $request->only(['date_from', 'date_to', 'appointment_id']);

        $stats = $this->bookingService->getStatistics($filters);

        return $this->success($stats, 'Statistics retrieved successfully');
    }

    /**
     * Bulk update booking status.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/appointments/bookings/bulk-status',
        summary: 'Bulk update status',
        description: 'Update status for multiple bookings at once',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Appointment Bookings']
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
            required: ['ids', 'status'],
            properties: [
                new OA\Property(
                    property: 'ids',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    example: [1, 2, 3]
                ),
                new OA\Property(property: 'status', type: 'string', enum: ['pending', 'confirmed', 'complete', 'cancelled', 'rejected']),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Bookings updated successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function bulkStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:appointment_payment_logs,id'],
            'status' => ['required', 'string', 'in:pending,confirmed,complete,cancelled,rejected'],
        ]);

        $count = $this->bookingService->bulkUpdateStatus($request->ids, $request->status);

        return $this->success(null, "{$count} bookings updated successfully");
    }
}
