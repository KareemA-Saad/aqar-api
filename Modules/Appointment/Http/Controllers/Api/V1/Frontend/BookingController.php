<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Appointment\Entities\Appointment;
use Modules\Appointment\Entities\AppointmentPaymentLog;
use Modules\Appointment\Http\Requests\Api\V1\InitBookingRequest;
use Modules\Appointment\Http\Requests\Api\V1\StoreBookingRequest;
use Modules\Appointment\Http\Resources\BookingResource;
use Modules\Appointment\Http\Resources\SlotAvailabilityResource;
use Modules\Appointment\Services\AppointmentBookingService;
use Modules\Appointment\Services\SlotAvailabilityService;
use OpenApi\Attributes as OA;

/**
 * Frontend Booking Controller
 *
 * Handles customer-facing booking operations.
 *
 * @package Modules\Appointment\Http\Controllers\Api\V1\Frontend
 */
#[OA\Tag(
    name: 'Appointment - Booking',
    description: 'Book and manage appointment bookings'
)]
final class BookingController extends BaseApiController
{
    public function __construct(
        private readonly AppointmentBookingService $bookingService,
        private readonly SlotAvailabilityService $availabilityService,
    ) {}

    /**
     * Get available time slots for a date.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/appointments/{appointmentId}/available-slots',
        summary: 'Get available slots',
        description: 'Get available time slots for a specific appointment and date',
        tags: ['Appointment - Booking']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'appointmentId',
        in: 'path',
        required: true,
        description: 'Appointment ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'date',
        in: 'query',
        required: true,
        description: 'Date to check availability (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'sub_appointment_id',
        in: 'query',
        description: 'Sub-appointment ID (optional, for different duration services)',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Available slots retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Available slots retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'date', type: 'string', format: 'date', example: '2024-01-15'),
                        new OA\Property(property: 'day_name', type: 'string', example: 'Monday'),
                        new OA\Property(property: 'is_available', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'slots',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'time', type: 'string', example: '09:00'),
                                    new OA\Property(property: 'available', type: 'boolean', example: true),
                                    new OA\Property(property: 'booked_count', type: 'integer', example: 0),
                                ],
                                type: 'object'
                            )
                        ),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Appointment not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function availableSlots(Request $request, int $appointmentId): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        $appointment = Appointment::find($appointmentId);

        if (!$appointment || !$appointment->status) {
            return $this->error('Appointment not found', 404);
        }

        $date = $request->input('date');
        $subAppointmentId = $request->input('sub_appointment_id');

        $availability = $this->availabilityService->getAvailableSlots(
            $appointmentId,
            $date,
            $subAppointmentId
        );

        return $this->success($availability, 'Available slots retrieved successfully');
    }

    /**
     * Get availability for date range.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/appointments/{appointmentId}/availability',
        summary: 'Get availability calendar',
        description: 'Get availability overview for a date range (useful for calendar views)',
        tags: ['Appointment - Booking']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'appointmentId',
        in: 'path',
        required: true,
        description: 'Appointment ID',
        schema: new OA\Schema(type: 'integer')
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
    #[OA\Response(
        response: 200,
        description: 'Availability retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Availability retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'date', type: 'string', format: 'date'),
                            new OA\Property(property: 'is_available', type: 'boolean'),
                            new OA\Property(property: 'available_slots', type: 'integer'),
                            new OA\Property(property: 'total_slots', type: 'integer'),
                        ],
                        type: 'object'
                    )
                ),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Appointment not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function availability(Request $request, int $appointmentId): JsonResponse
    {
        $request->validate([
            'start_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $appointment = Appointment::find($appointmentId);

        if (!$appointment || !$appointment->status) {
            return $this->error('Appointment not found', 404);
        }

        $availability = $this->availabilityService->getDateRangeAvailability(
            $appointmentId,
            $request->input('start_date'),
            $request->input('end_date')
        );

        return $this->success($availability, 'Availability retrieved successfully');
    }

    /**
     * Initialize a booking (get pricing information).
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/appointments/{appointmentId}/booking/init',
        summary: 'Initialize booking',
        description: 'Calculate pricing for a potential booking before final submission',
        tags: ['Appointment - Booking']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'appointmentId',
        in: 'path',
        required: true,
        description: 'Appointment ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/InitBookingRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Booking initialized successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Booking initialized successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'base_price', type: 'number', example: 100.00),
                        new OA\Property(property: 'additional_services', type: 'number', example: 25.00),
                        new OA\Property(property: 'tax_amount', type: 'number', example: 12.50),
                        new OA\Property(property: 'tax_percentage', type: 'number', example: 10),
                        new OA\Property(property: 'discount_amount', type: 'number', example: 0),
                        new OA\Property(property: 'total_amount', type: 'number', example: 137.50),
                        new OA\Property(property: 'slot_available', type: 'boolean', example: true),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Slot not available')]
    #[OA\Response(response: 404, description: 'Appointment not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function init(InitBookingRequest $request, int $appointmentId): JsonResponse
    {
        $appointment = Appointment::with(['subAppointments', 'taxes'])->find($appointmentId);

        if (!$appointment || !$appointment->status) {
            return $this->error('Appointment not found', 404);
        }

        // Check slot availability
        $isAvailable = $this->availabilityService->isSlotAvailable(
            $appointmentId,
            $request->appointment_date,
            $request->appointment_time
        );

        if (!$isAvailable) {
            return $this->error('Selected time slot is not available', 400);
        }

        // Calculate pricing
        $pricing = $this->bookingService->calculatePricing(
            $appointment,
            $request->sub_appointment_id,
            $request->additional_services ?? []
        );

        $pricing['slot_available'] = true;

        return $this->success($pricing, 'Booking initialized successfully');
    }

    /**
     * Create a new booking.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/appointments/{appointmentId}/booking',
        summary: 'Create booking',
        description: 'Create a new appointment booking',
        tags: ['Appointment - Booking']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'appointmentId',
        in: 'path',
        required: true,
        description: 'Appointment ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StoreBookingRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Booking created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Booking created successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BookingResource'),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Slot not available or booking failed')]
    #[OA\Response(response: 404, description: 'Appointment not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function store(StoreBookingRequest $request, int $appointmentId): JsonResponse
    {
        $appointment = Appointment::with(['subAppointments', 'taxes'])->find($appointmentId);

        if (!$appointment || !$appointment->status) {
            return $this->error('Appointment not found', 404);
        }

        // Backend validation - check slot availability again
        $isAvailable = $this->availabilityService->isSlotAvailable(
            $appointmentId,
            $request->appointment_date,
            $request->appointment_time
        );

        if (!$isAvailable) {
            return $this->error('Selected time slot is no longer available', 400);
        }

        try {
            $booking = $this->bookingService->createBooking(
                $appointment,
                $request->validated()
            );

            return $this->success(
                new BookingResource($booking),
                'Booking created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get booking by transaction ID (for payment callbacks).
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/appointments/booking/transaction/{transactionId}',
        summary: 'Get booking by transaction',
        description: 'Get booking details by transaction ID',
        tags: ['Appointment - Booking']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'transactionId',
        in: 'path',
        required: true,
        description: 'Transaction ID',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Booking retrieved successfully'
    )]
    #[OA\Response(response: 404, description: 'Booking not found')]
    public function showByTransaction(string $transactionId): JsonResponse
    {
        $booking = AppointmentPaymentLog::with(['appointment', 'subAppointment', 'user'])
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        return $this->success(
            new BookingResource($booking),
            'Booking retrieved successfully'
        );
    }

    /**
     * Get user's bookings.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/appointments/my-bookings',
        summary: 'My bookings',
        description: 'Get current user\'s appointment bookings',
        security: [['sanctum' => []]],
        tags: ['Appointment - Booking']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        description: 'Filter by status',
        schema: new OA\Schema(type: 'string', enum: ['pending', 'confirmed', 'complete', 'cancelled'])
    )]
    #[OA\Parameter(
        name: 'upcoming',
        in: 'query',
        description: 'Show only upcoming appointments',
        schema: new OA\Schema(type: 'boolean', default: false)
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 10)
    )]
    #[OA\Response(
        response: 200,
        description: 'Bookings retrieved successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function myBookings(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = AppointmentPaymentLog::with(['appointment', 'subAppointment'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->boolean('upcoming')) {
            $query->where('appointment_date', '>=', now()->toDateString())
                ->whereIn('status', ['pending', 'confirmed']);
        }

        $perPage = min((int) $request->input('per_page', 10), 50);
        $bookings = $query->paginate($perPage);

        return $this->success([
            'bookings' => BookingResource::collection($bookings),
            'pagination' => [
                'total' => $bookings->total(),
                'per_page' => $bookings->perPage(),
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
            ],
        ], 'Bookings retrieved successfully');
    }

    /**
     * Get a single booking.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/appointments/my-bookings/{id}',
        summary: 'Get my booking',
        description: 'Get a single booking by ID (must belong to current user)',
        security: [['sanctum' => []]],
        tags: ['Appointment - Booking']
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
        description: 'Booking retrieved successfully'
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    public function showMyBooking(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $booking = AppointmentPaymentLog::with(['appointment', 'subAppointment', 'additionalLogs'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        return $this->success(
            new BookingResource($booking),
            'Booking retrieved successfully'
        );
    }

    /**
     * Cancel user's own booking.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/appointments/my-bookings/{id}/cancel',
        summary: 'Cancel my booking',
        description: 'Cancel a booking (must belong to current user)',
        security: [['sanctum' => []]],
        tags: ['Appointment - Booking']
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
    #[OA\Response(response: 400, description: 'Cannot cancel booking')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    public function cancelMyBooking(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $booking = AppointmentPaymentLog::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        // Check if booking can be cancelled
        if (in_array($booking->status, ['complete', 'cancelled'])) {
            return $this->error('This booking cannot be cancelled', 400);
        }

        // Check cancellation deadline (e.g., 24 hours before)
        $appointmentDateTime = $booking->appointment_date . ' ' . $booking->appointment_time;
        if (now()->diffInHours($appointmentDateTime, false) < 24) {
            return $this->error('Bookings must be cancelled at least 24 hours in advance', 400);
        }

        $booking = $this->bookingService->cancelBooking($booking, $request->reason);

        return $this->success(
            new BookingResource($booking),
            'Booking cancelled successfully'
        );
    }

    /**
     * Request to reschedule a booking.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/appointments/my-bookings/{id}/reschedule',
        summary: 'Reschedule my booking',
        description: 'Request to reschedule a booking to a new date/time',
        security: [['sanctum' => []]],
        tags: ['Appointment - Booking']
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
            required: ['appointment_date', 'appointment_time'],
            properties: [
                new OA\Property(property: 'appointment_date', type: 'string', format: 'date', example: '2024-01-20'),
                new OA\Property(property: 'appointment_time', type: 'string', example: '14:00'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Booking rescheduled successfully'
    )]
    #[OA\Response(response: 400, description: 'Cannot reschedule booking or slot not available')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function rescheduleMyBooking(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'appointment_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'appointment_time' => ['required', 'string'],
        ]);

        $user = $request->user();

        $booking = AppointmentPaymentLog::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        // Check if booking can be rescheduled
        if (in_array($booking->status, ['complete', 'cancelled'])) {
            return $this->error('This booking cannot be rescheduled', 400);
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
     * Update payment status (for payment gateway callbacks).
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/appointments/booking/payment-callback',
        summary: 'Payment callback',
        description: 'Handle payment gateway callback to update payment status',
        tags: ['Appointment - Booking']
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
            required: ['transaction_id', 'payment_status'],
            properties: [
                new OA\Property(property: 'transaction_id', type: 'string'),
                new OA\Property(property: 'payment_status', type: 'string', enum: ['pending', 'complete', 'failed']),
                new OA\Property(property: 'gateway_response', type: 'object', nullable: true),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Payment status updated successfully'
    )]
    #[OA\Response(response: 404, description: 'Booking not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function paymentCallback(Request $request): JsonResponse
    {
        $request->validate([
            'transaction_id' => ['required', 'string'],
            'payment_status' => ['required', 'string', 'in:pending,complete,failed'],
        ]);

        $booking = AppointmentPaymentLog::where('transaction_id', $request->transaction_id)->first();

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        $booking = $this->bookingService->updatePaymentStatus(
            $booking,
            $request->payment_status,
            $request->gateway_response ?? []
        );

        return $this->success(
            new BookingResource($booking),
            'Payment status updated successfully'
        );
    }
}
