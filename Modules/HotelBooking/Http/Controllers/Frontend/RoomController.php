<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HotelBooking\Http\Requests\SearchRoomRequest;
use Modules\HotelBooking\Http\Resources\RoomTypeResource;
use Modules\HotelBooking\Services\RoomSearchService;
use Modules\HotelBooking\Services\RoomTypeService;
use Modules\HotelBooking\Services\PricingService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Rooms', description: 'Public room browsing and search endpoints')]
class RoomController extends Controller
{
    protected RoomSearchService $searchService;
    protected RoomTypeService $roomTypeService;
    protected PricingService $pricingService;

    public function __construct(
        RoomSearchService $searchService,
        RoomTypeService $roomTypeService,
        PricingService $pricingService
    ) {
        $this->searchService = $searchService;
        $this->roomTypeService = $roomTypeService;
        $this->pricingService = $pricingService;
    }

    #[OA\Get(
        path: '/api/v1/rooms/search',
        summary: 'Search available rooms',
        description: 'Search for available room types across all hotels',
        tags: ['Rooms'],
        parameters: [
            new OA\Parameter(name: 'check_in_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'check_out_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'guests', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'rooms', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'hotel_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'city', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'min_price', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'max_price', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'amenities', in: 'query', schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'integer'))),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['price_low', 'price_high', 'rating', 'guests'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Search results'),
        ]
    )]
    public function search(SearchRoomRequest $request): JsonResponse
    {
        $results = $this->searchService->searchAvailableRooms($request->validated());

        return response()->json([
            'success' => true,
            'data' => RoomTypeResource::collection($results),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/rooms/{id}',
        summary: 'Get room type details',
        description: 'Get detailed information about a specific room type',
        tags: ['Rooms'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Room type details'),
            new OA\Response(response: 404, description: 'Room type not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $roomType = $this->roomTypeService->getRoomType($id);

        if (!$roomType || !$roomType->status) {
            return response()->json([
                'success' => false,
                'message' => __('Room type not found.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new RoomTypeResource($roomType),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/rooms/{id}/availability',
        summary: 'Get room availability calendar',
        description: 'Get availability calendar for a room type',
        tags: ['Rooms'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'month', in: 'query', required: true, schema: new OA\Schema(type: 'string', example: '2024-01')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Availability calendar'),
            new OA\Response(response: 404, description: 'Room type not found'),
        ]
    )]
    public function availability(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $roomType = $this->roomTypeService->getRoomType($id);

        if (!$roomType || !$roomType->status) {
            return response()->json([
                'success' => false,
                'message' => __('Room type not found.'),
            ], 404);
        }

        $calendar = $this->searchService->getAvailabilityCalendar($id, $request->month);

        return response()->json([
            'success' => true,
            'data' => [
                'room_type' => new RoomTypeResource($roomType),
                'calendar' => $calendar,
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/rooms/{id}/price',
        summary: 'Calculate room price',
        description: 'Calculate total price for a room type',
        tags: ['Rooms'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'check_in_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'check_out_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'quantity', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'meal_plan', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Price calculation'),
            new OA\Response(response: 404, description: 'Room type not found'),
        ]
    )]
    public function calculatePrice(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'quantity' => 'sometimes|integer|min:1|max:10',
            'meal_plan' => 'sometimes|string|in:room_only,breakfast,half_board,full_board,all_inclusive',
            'adults' => 'sometimes|integer|min:1',
        ]);

        $roomType = $this->roomTypeService->getRoomType($id);

        if (!$roomType || !$roomType->status) {
            return response()->json([
                'success' => false,
                'message' => __('Room type not found.'),
            ], 404);
        }

        $pricing = $this->pricingService->calculateRoomPrice(
            $id,
            $request->check_in_date,
            $request->check_out_date,
            $request->quantity ?? 1,
            [
                'meal_plan' => $request->meal_plan,
                'adults' => $request->adults ?? 2,
            ]
        );

        return response()->json([
            'success' => true,
            'data' => $pricing,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/rooms/meal-plans',
        summary: 'Get available meal plans',
        description: 'Get list of available meal plans with prices',
        tags: ['Rooms'],
        responses: [
            new OA\Response(response: 200, description: 'Meal plans list'),
        ]
    )]
    public function mealPlans(): JsonResponse
    {
        $mealPlans = $this->pricingService->getAvailableMealPlans();

        return response()->json([
            'success' => true,
            'data' => $mealPlans,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/rooms/extras',
        summary: 'Get available extras',
        description: 'Get list of available extras/add-ons with prices',
        tags: ['Rooms'],
        responses: [
            new OA\Response(response: 200, description: 'Extras list'),
        ]
    )]
    public function extras(): JsonResponse
    {
        $extras = $this->pricingService->getAvailableExtras();

        return response()->json([
            'success' => true,
            'data' => $extras,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/rooms/check-availability',
        summary: 'Check room availability',
        description: 'Check if rooms are available for specific dates',
        tags: ['Rooms'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'room_type_id', type: 'integer'),
                    new OA\Property(property: 'check_in_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'check_out_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'quantity', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Availability status'),
        ]
    )]
    public function checkAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'room_type_id' => 'required|integer|exists:room_types,id',
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'quantity' => 'sometimes|integer|min:1|max:10',
        ]);

        $available = $this->searchService->checkAvailability(
            $request->room_type_id,
            $request->check_in_date,
            $request->check_out_date,
            $request->quantity ?? 1
        );

        return response()->json([
            'success' => true,
            'data' => [
                'available' => $available,
                'room_type_id' => $request->room_type_id,
                'check_in_date' => $request->check_in_date,
                'check_out_date' => $request->check_out_date,
                'quantity' => $request->quantity ?? 1,
            ],
        ]);
    }
}
