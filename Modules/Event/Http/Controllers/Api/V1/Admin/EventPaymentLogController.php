<?php

declare(strict_types=1);

namespace Modules\Event\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Event\Entities\EventPaymentLog;
use Modules\Event\Http\Resources\EventPaymentLogResource;
use Modules\Event\Services\EventBookingService;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin Event Payment Log Controller
 */
#[OA\Tag(
    name: 'Tenant Admin - Event Bookings',
    description: 'Manage event bookings and payments within a tenant'
)]
final class EventPaymentLogController extends BaseApiController
{
    public function __construct(
        private readonly EventBookingService $bookingService,
    ) {}

    /**
     * List all bookings with filters.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/event-bookings',
        summary: 'List event bookings',
        description: 'Get paginated list of event bookings/payments',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Bookings']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'event_id', in: 'query', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'user_id', in: 'query', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1]))]
    #[OA\Parameter(name: 'check_in_status', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1]))]
    #[OA\Parameter(name: 'payment_gateway', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, maximum: 100))]
    #[OA\Response(response: 200, description: 'Bookings retrieved successfully')]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'event_id', 'user_id', 'status', 'check_in_status',
            'payment_gateway', 'search', 'date_from', 'date_to',
            'sort_by', 'sort_order'
        ]);

        $perPage = min((int) $request->input('per_page', 15), 100);
        $bookings = $this->bookingService->getBookings($filters, $perPage);

        return $this->paginated($bookings, EventPaymentLogResource::class, 'Event bookings retrieved successfully');
    }

    /**
     * Get a specific booking.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/event-bookings/{id}',
        summary: 'Get booking details',
        description: 'Get a specific event booking by ID',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Bookings']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Booking retrieved successfully')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    public function show(int $id): JsonResponse
    {
        $booking = EventPaymentLog::with(['event', 'user'])->find($id);

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        return $this->success(
            new EventPaymentLogResource($booking),
            'Event booking retrieved successfully'
        );
    }

    /**
     * Update payment status.
     */
    #[OA\Patch(
        path: '/api/v1/tenant/{tenant}/admin/event-bookings/{id}/status',
        summary: 'Update payment status',
        description: 'Approve or reject a booking payment',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Bookings']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['status'],
            properties: [
                new OA\Property(property: 'status', type: 'boolean', example: true)
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Payment status updated successfully')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['status' => 'required|boolean']);

        $booking = EventPaymentLog::find($id);

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        $updatedBooking = $this->bookingService->updatePaymentStatus($booking, $request->boolean('status'));

        return $this->success(
            new EventPaymentLogResource($updatedBooking),
            'Payment status updated successfully'
        );
    }

    /**
     * Check-in attendee.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/event-bookings/{id}/check-in',
        summary: 'Check-in attendee',
        description: 'Mark attendee as checked-in at the event',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Bookings']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Attendee checked-in successfully')]
    #[OA\Response(response: 400, description: 'Cannot check-in')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    public function checkIn(int $id): JsonResponse
    {
        $booking = EventPaymentLog::find($id);

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        try {
            $updatedBooking = $this->bookingService->checkInAttendee($booking);

            return $this->success(
                new EventPaymentLogResource($updatedBooking),
                'Attendee checked-in successfully'
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Undo check-in.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/event-bookings/{id}/check-in',
        summary: 'Undo check-in',
        description: 'Undo attendee check-in',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Bookings']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Check-in undone successfully')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    public function undoCheckIn(int $id): JsonResponse
    {
        $booking = EventPaymentLog::find($id);

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        $updatedBooking = $this->bookingService->undoCheckIn($booking);

        return $this->success(
            new EventPaymentLogResource($updatedBooking),
            'Check-in undone successfully'
        );
    }

    /**
     * Get booking by ticket code.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/event-bookings/ticket/{code}',
        summary: 'Get booking by ticket code',
        description: 'Find booking by ticket code for check-in',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Bookings']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'code', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Booking found')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    public function findByTicketCode(string $code): JsonResponse
    {
        $booking = $this->bookingService->getBookingByTicketCode($code);

        if (!$booking) {
            return $this->error('Booking not found with this ticket code', 404);
        }

        return $this->success(
            new EventPaymentLogResource($booking),
            'Booking found successfully'
        );
    }

    /**
     * Get revenue report.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/event-bookings/report',
        summary: 'Get revenue report',
        description: 'Generate revenue report for bookings',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Bookings']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'event_id', in: 'query', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Response(response: 200, description: 'Report generated successfully')]
    public function report(Request $request): JsonResponse
    {
        $filters = $request->only(['event_id', 'date_from', 'date_to']);
        $report = $this->bookingService->generateRevenueReport($filters);

        return $this->success($report, 'Revenue report generated successfully');
    }

    /**
     * Delete a booking.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/event-bookings/{id}',
        summary: 'Delete booking',
        description: 'Delete an event booking',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Bookings']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Booking deleted successfully')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    public function destroy(int $id): JsonResponse
    {
        $booking = EventPaymentLog::find($id);

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        // Return tickets if booking was approved
        if ($booking->status) {
            $booking->event->increment('available_ticket', $booking->ticket_qty);
        }

        $booking->delete();

        return $this->success(null, 'Event booking deleted successfully');
    }
}
