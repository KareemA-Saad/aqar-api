<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HotelBooking\Http\Requests\UpdateBookingStatusRequest;
use Modules\HotelBooking\Http\Requests\CancelBookingRequest;
use Modules\HotelBooking\Http\Requests\ProcessRefundRequest;
use Modules\HotelBooking\Http\Resources\BookingResource;
use Modules\HotelBooking\Http\Resources\BookingCollection;
use Modules\HotelBooking\Services\BookingService;
use Modules\HotelBooking\Services\RefundService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Booking Management', description: 'Booking management endpoints for administrators')]
class BookingController extends Controller
{
    protected BookingService $bookingService;
    protected RefundService $refundService;

    public function __construct(BookingService $bookingService, RefundService $refundService)
    {
        $this->bookingService = $bookingService;
        $this->refundService = $refundService;
    }

    #[OA\Get(
        path: '/api/v1/admin/bookings',
        summary: 'List all bookings',
        description: 'Get paginated list of all bookings with optional filters',
        tags: ['Admin Booking Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'hotel_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'payment_status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'from_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to_date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Bookings list retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(Request $request): BookingCollection
    {
        $bookings = $this->bookingService->getBookings($request->all());
        return new BookingCollection($bookings);
    }

    #[OA\Get(
        path: '/api/v1/admin/bookings/{id}',
        summary: 'Get booking details',
        description: 'Get detailed information about a specific booking',
        tags: ['Admin Booking Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Booking details retrieved successfully'),
            new OA\Response(response: 404, description: 'Booking not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $booking = $this->bookingService->getBooking($id);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => __('Booking not found.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new BookingResource($booking),
        ]);
    }

    #[OA\Patch(
        path: '/api/v1/admin/bookings/{id}/status',
        summary: 'Update booking status',
        description: 'Update the status of a booking',
        tags: ['Admin Booking Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateBookingStatusRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Booking status updated successfully'),
            new OA\Response(response: 404, description: 'Booking not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function updateStatus(UpdateBookingStatusRequest $request, int $id): JsonResponse
    {
        $booking = $this->bookingService->updateStatus(
            $id,
            $request->status,
            $request->notes
        );

        return response()->json([
            'success' => true,
            'message' => __('Booking status updated successfully.'),
            'data' => new BookingResource($booking),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/bookings/{id}/confirm',
        summary: 'Confirm booking',
        description: 'Confirm a pending booking',
        tags: ['Admin Booking Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Booking confirmed successfully'),
            new OA\Response(response: 404, description: 'Booking not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function confirm(int $id): JsonResponse
    {
        $booking = $this->bookingService->confirmBooking($id);

        return response()->json([
            'success' => true,
            'message' => __('Booking confirmed successfully.'),
            'data' => new BookingResource($booking),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/bookings/{id}/check-in',
        summary: 'Check-in guest',
        description: 'Process check-in for a confirmed booking',
        tags: ['Admin Booking Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Guest checked in successfully'),
            new OA\Response(response: 400, description: 'Check-in not available'),
            new OA\Response(response: 404, description: 'Booking not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function checkIn(int $id): JsonResponse
    {
        try {
            $booking = $this->bookingService->checkIn($id);

            return response()->json([
                'success' => true,
                'message' => __('Guest checked in successfully.'),
                'data' => new BookingResource($booking),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[OA\Post(
        path: '/api/v1/admin/bookings/{id}/check-out',
        summary: 'Check-out guest',
        description: 'Process check-out for a checked-in booking',
        tags: ['Admin Booking Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Guest checked out successfully'),
            new OA\Response(response: 400, description: 'Check-out not available'),
            new OA\Response(response: 404, description: 'Booking not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function checkOut(int $id): JsonResponse
    {
        try {
            $booking = $this->bookingService->checkOut($id);

            return response()->json([
                'success' => true,
                'message' => __('Guest checked out successfully.'),
                'data' => new BookingResource($booking),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[OA\Post(
        path: '/api/v1/admin/bookings/{id}/cancel',
        summary: 'Cancel booking',
        description: 'Cancel a booking and optionally process refund',
        tags: ['Admin Booking Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CancelBookingRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Booking cancelled successfully'),
            new OA\Response(response: 400, description: 'Cancellation not allowed'),
            new OA\Response(response: 404, description: 'Booking not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function cancel(CancelBookingRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->bookingService->cancelBooking(
                $id,
                $request->reason,
                $request->process_refund ?? true
            );

            return response()->json([
                'success' => true,
                'message' => __('Booking cancelled successfully.'),
                'data' => [
                    'booking' => new BookingResource($result['booking']),
                    'refund_info' => $result['refund_info'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[OA\Post(
        path: '/api/v1/admin/bookings/{id}/no-show',
        summary: 'Mark as no-show',
        description: 'Mark a confirmed booking as no-show',
        tags: ['Admin Booking Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Booking marked as no-show'),
            new OA\Response(response: 400, description: 'Cannot mark as no-show'),
            new OA\Response(response: 404, description: 'Booking not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function markNoShow(int $id): JsonResponse
    {
        try {
            $booking = $this->bookingService->markNoShow($id);

            return response()->json([
                'success' => true,
                'message' => __('Booking marked as no-show.'),
                'data' => new BookingResource($booking),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[OA\Post(
        path: '/api/v1/admin/bookings/{id}/refund',
        summary: 'Process refund',
        description: 'Process refund for a cancelled booking',
        tags: ['Admin Booking Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ProcessRefundRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Refund processed successfully'),
            new OA\Response(response: 400, description: 'Cannot process refund'),
            new OA\Response(response: 404, description: 'Booking not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function processRefund(ProcessRefundRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->refundService->processRefund(
                $id,
                $request->amount,
                $request->reason
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[OA\Get(
        path: '/api/v1/admin/bookings/{id}/refund-eligibility',
        summary: 'Check refund eligibility',
        description: 'Check if a booking is eligible for refund and calculate amount',
        tags: ['Admin Booking Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Refund eligibility retrieved'),
            new OA\Response(response: 404, description: 'Booking not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function refundEligibility(int $id): JsonResponse
    {
        $eligibility = $this->refundService->isEligibleForRefund($id);

        return response()->json([
            'success' => true,
            'data' => $eligibility,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/bookings/today-arrivals',
        summary: 'Get today\'s arrivals',
        description: 'Get all bookings with check-in today',
        tags: ['Admin Booking Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'hotel_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Today\'s arrivals retrieved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function todayArrivals(Request $request): JsonResponse
    {
        $arrivals = $this->bookingService->getTodayArrivals($request->hotel_id);

        return response()->json([
            'success' => true,
            'data' => BookingResource::collection($arrivals),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/bookings/today-departures',
        summary: 'Get today\'s departures',
        description: 'Get all bookings with check-out today',
        tags: ['Admin Booking Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'hotel_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Today\'s departures retrieved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function todayDepartures(Request $request): JsonResponse
    {
        $departures = $this->bookingService->getTodayDepartures($request->hotel_id);

        return response()->json([
            'success' => true,
            'data' => BookingResource::collection($departures),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/bookings/in-house',
        summary: 'Get in-house guests',
        description: 'Get all currently checked-in guests',
        tags: ['Admin Booking Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'hotel_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'In-house guests retrieved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function inHouseGuests(Request $request): JsonResponse
    {
        $guests = $this->bookingService->getInHouseGuests($request->hotel_id);

        return response()->json([
            'success' => true,
            'data' => BookingResource::collection($guests),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/bookings/statistics',
        summary: 'Get booking statistics',
        description: 'Get booking statistics for a period',
        tags: ['Admin Booking Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'hotel_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'period', in: 'query', schema: new OA\Schema(type: 'string', enum: ['today', 'week', 'month', 'year'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Statistics retrieved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function statistics(Request $request): JsonResponse
    {
        $stats = $this->bookingService->getBookingStats(
            $request->hotel_id,
            $request->period ?? 'month'
        );

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
