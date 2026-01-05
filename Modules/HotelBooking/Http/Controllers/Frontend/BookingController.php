<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HotelBooking\Http\Requests\InitBookingRequest;
use Modules\HotelBooking\Http\Requests\CreateBookingRequest;
use Modules\HotelBooking\Http\Requests\CalculateBookingRequest;
use Modules\HotelBooking\Http\Requests\CancelBookingRequest;
use Modules\HotelBooking\Http\Resources\BookingResource;
use Modules\HotelBooking\Http\Resources\RoomHoldResource;
use Modules\HotelBooking\Services\BookingService;
use Modules\HotelBooking\Services\RoomHoldService;
use Modules\HotelBooking\Services\PricingService;
use Modules\HotelBooking\Services\HotelPaymentService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Bookings', description: 'Booking creation and management endpoints')]
class BookingController extends Controller
{
    protected BookingService $bookingService;
    protected RoomHoldService $holdService;
    protected PricingService $pricingService;
    protected HotelPaymentService $paymentService;

    public function __construct(
        BookingService $bookingService,
        RoomHoldService $holdService,
        PricingService $pricingService,
        HotelPaymentService $paymentService
    ) {
        $this->bookingService = $bookingService;
        $this->holdService = $holdService;
        $this->pricingService = $pricingService;
        $this->paymentService = $paymentService;
    }

    #[OA\Post(
        path: '/api/v1/bookings/calculate',
        summary: 'Calculate booking price',
        description: 'Calculate total price for a multi-room booking',
        tags: ['Bookings'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CalculateBookingRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Price calculation'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function calculate(CalculateBookingRequest $request): JsonResponse
    {
        $pricing = $this->pricingService->calculateMultiRoomPrice(
            $request->rooms,
            $request->check_in_date,
            $request->check_out_date,
            [
                'extras' => $request->extras ?? [],
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $pricing,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/bookings/init',
        summary: 'Initialize booking (create room holds)',
        description: 'Create temporary room holds during checkout process',
        tags: ['Bookings'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/InitBookingRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Room holds created'),
            new OA\Response(response: 400, description: 'Rooms not available'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function init(InitBookingRequest $request): JsonResponse
    {
        $holdToken = $this->holdService->createHolds(
            $request->rooms,
            $request->check_in_date,
            $request->check_out_date
        );

        if (!$holdToken) {
            return response()->json([
                'success' => false,
                'message' => __('Selected rooms are no longer available. Please try again.'),
            ], 400);
        }

        $summary = $this->holdService->getHoldSummary($holdToken);

        return response()->json([
            'success' => true,
            'message' => __('Rooms held for 15 minutes.'),
            'data' => $summary,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/bookings/hold/{token}',
        summary: 'Get hold status',
        description: 'Get current status of room holds',
        tags: ['Bookings'],
        parameters: [
            new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Hold status'),
            new OA\Response(response: 404, description: 'Hold not found or expired'),
        ]
    )]
    public function holdStatus(string $token): JsonResponse
    {
        $summary = $this->holdService->getHoldSummary($token);

        if (!$summary) {
            return response()->json([
                'success' => false,
                'message' => __('Room hold not found or has expired.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/bookings/hold/{token}/extend',
        summary: 'Extend hold time',
        description: 'Extend room hold duration',
        tags: ['Bookings'],
        parameters: [
            new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Hold extended'),
            new OA\Response(response: 400, description: 'Cannot extend hold'),
        ]
    )]
    public function extendHold(string $token): JsonResponse
    {
        $extended = $this->holdService->extendHold($token);

        if (!$extended) {
            return response()->json([
                'success' => false,
                'message' => __('Unable to extend hold. It may have expired.'),
            ], 400);
        }

        $summary = $this->holdService->getHoldSummary($token);

        return response()->json([
            'success' => true,
            'message' => __('Hold extended for 15 more minutes.'),
            'data' => $summary,
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/bookings/hold/{token}',
        summary: 'Release hold',
        description: 'Release room holds (cancel checkout)',
        tags: ['Bookings'],
        parameters: [
            new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Hold released'),
        ]
    )]
    public function releaseHold(string $token): JsonResponse
    {
        $this->holdService->releaseHolds($token);

        return response()->json([
            'success' => true,
            'message' => __('Room holds released.'),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/bookings',
        summary: 'Create booking',
        description: 'Create a booking from room holds',
        tags: ['Bookings'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CreateBookingRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Booking created'),
            new OA\Response(response: 400, description: 'Hold expired or rooms unavailable'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(CreateBookingRequest $request): JsonResponse
    {
        $booking = $this->bookingService->createBookingFromHold(
            $request->hold_token,
            $request->validated(),
            auth()->id()
        );

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => __('Room hold has expired. Please start the booking process again.'),
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => __('Booking created successfully. Please complete payment.'),
            'data' => new BookingResource($booking),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/bookings/{code}',
        summary: 'Get booking by code',
        description: 'Get booking details by booking code',
        tags: ['Bookings'],
        parameters: [
            new OA\Parameter(name: 'code', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Booking details'),
            new OA\Response(response: 404, description: 'Booking not found'),
        ]
    )]
    public function show(string $code): JsonResponse
    {
        $booking = $this->bookingService->getBookingByCode($code);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => __('Booking not found.'),
            ], 404);
        }

        // Check if user is authorized (owner or admin)
        if ($booking->user_id && auth()->id() !== $booking->user_id && !auth()->user()?->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => __('Unauthorized.'),
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => new BookingResource($booking),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/bookings/my-bookings',
        summary: 'Get user bookings',
        description: 'Get bookings for authenticated user',
        tags: ['Bookings'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'User bookings'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function myBookings(Request $request): JsonResponse
    {
        $bookings = $this->bookingService->getUserBookings(auth()->id(), $request->all());

        return response()->json([
            'success' => true,
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/bookings/upcoming',
        summary: 'Get upcoming bookings',
        description: 'Get upcoming bookings for authenticated user',
        tags: ['Bookings'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Upcoming bookings'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function upcoming(): JsonResponse
    {
        $bookings = $this->bookingService->getUpcomingBookings(auth()->id());

        return response()->json([
            'success' => true,
            'data' => BookingResource::collection($bookings),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/bookings/{code}/cancel',
        summary: 'Cancel booking',
        description: 'Cancel a booking',
        tags: ['Bookings'],
        parameters: [
            new OA\Parameter(name: 'code', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CancelBookingRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Booking cancelled'),
            new OA\Response(response: 400, description: 'Cannot cancel booking'),
            new OA\Response(response: 404, description: 'Booking not found'),
        ]
    )]
    public function cancel(CancelBookingRequest $request, string $code): JsonResponse
    {
        $booking = $this->bookingService->getBookingByCode($code);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => __('Booking not found.'),
            ], 404);
        }

        // Check authorization
        if ($booking->user_id && auth()->id() !== $booking->user_id && !auth()->user()?->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => __('Unauthorized.'),
            ], 403);
        }

        try {
            $result = $this->bookingService->cancelBooking(
                $booking->id,
                $request->reason,
                true
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

    #[OA\Get(
        path: '/api/v1/bookings/payment-methods',
        summary: 'Get payment methods',
        description: 'Get available payment methods',
        tags: ['Bookings'],
        responses: [
            new OA\Response(response: 200, description: 'Payment methods'),
        ]
    )]
    public function paymentMethods(): JsonResponse
    {
        $methods = $this->paymentService->getAvailablePaymentMethods();

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/bookings/{code}/pay',
        summary: 'Process payment',
        description: 'Process payment for a booking',
        tags: ['Bookings'],
        parameters: [
            new OA\Parameter(name: 'code', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'payment_method', type: 'string'),
                    new OA\Property(property: 'payment_data', type: 'object'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Payment processed'),
            new OA\Response(response: 400, description: 'Payment failed'),
            new OA\Response(response: 404, description: 'Booking not found'),
        ]
    )]
    public function pay(Request $request, string $code): JsonResponse
    {
        $request->validate([
            'payment_method' => 'required|string',
            'payment_data' => 'sometimes|array',
        ]);

        $booking = $this->bookingService->getBookingByCode($code);

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => __('Booking not found.'),
            ], 404);
        }

        try {
            $result = $this->paymentService->processPayment(
                $booking->id,
                $request->payment_method,
                $request->payment_data ?? []
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[OA\Post(
        path: '/api/v1/bookings/webhook/{gateway}',
        summary: 'Payment webhook',
        description: 'Handle payment gateway webhooks',
        tags: ['Bookings'],
        parameters: [
            new OA\Parameter(name: 'gateway', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Webhook processed'),
        ]
    )]
    public function webhook(Request $request, string $gateway): JsonResponse
    {
        $result = $this->paymentService->handleWebhook($gateway, $request);

        return response()->json($result);
    }
}
