<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HotelBooking\Http\Requests\SearchRoomRequest;
use Modules\HotelBooking\Http\Resources\HotelResource;
use Modules\HotelBooking\Http\Resources\HotelCollection;
use Modules\HotelBooking\Http\Resources\RoomTypeResource;
use Modules\HotelBooking\Services\HotelService;
use Modules\HotelBooking\Services\RoomSearchService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Hotels', description: 'Public hotel browsing endpoints')]
class HotelController extends Controller
{
    protected HotelService $hotelService;
    protected RoomSearchService $searchService;

    public function __construct(HotelService $hotelService, RoomSearchService $searchService)
    {
        $this->hotelService = $hotelService;
        $this->searchService = $searchService;
    }

    #[OA\Get(
        path: '/api/v1/hotels',
        summary: 'List hotels',
        description: 'Get paginated list of active hotels',
        tags: ['Hotels'],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'city', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'country', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'min_stars', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Hotels list retrieved'),
        ]
    )]
    public function index(Request $request): HotelCollection
    {
        $filters = array_merge($request->all(), ['status' => 1]);
        $hotels = $this->hotelService->getHotels($filters);

        return new HotelCollection($hotels);
    }

    #[OA\Get(
        path: '/api/v1/hotels/{slug}',
        summary: 'Get hotel by slug',
        description: 'Get detailed information about a specific hotel',
        tags: ['Hotels'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Hotel details retrieved'),
            new OA\Response(response: 404, description: 'Hotel not found'),
        ]
    )]
    public function show(string $slug): JsonResponse
    {
        $hotel = $this->hotelService->getHotelBySlug($slug);

        if (!$hotel || !$hotel->status) {
            return response()->json([
                'success' => false,
                'message' => __('Hotel not found.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new HotelResource($hotel),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/hotels/search',
        summary: 'Search hotels',
        description: 'Search for hotels with available rooms',
        tags: ['Hotels'],
        parameters: [
            new OA\Parameter(name: 'check_in_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'check_out_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'guests', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'rooms', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'city', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'min_price', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'max_price', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'min_stars', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['price_low', 'price_high', 'rating', 'stars'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Search results'),
        ]
    )]
    public function search(SearchRoomRequest $request): JsonResponse
    {
        $results = $this->searchService->searchHotels($request->validated());

        return response()->json([
            'success' => true,
            'data' => HotelResource::collection($results),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/hotels/{slug}/rooms',
        summary: 'Get available rooms for hotel',
        description: 'Get available room types for a hotel within date range',
        tags: ['Hotels'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'check_in_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'check_out_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'guests', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'rooms', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Available rooms retrieved'),
            new OA\Response(response: 404, description: 'Hotel not found'),
        ]
    )]
    public function availableRooms(Request $request, string $slug): JsonResponse
    {
        $request->validate([
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
            'guests' => 'sometimes|integer|min:1',
            'rooms' => 'sometimes|integer|min:1',
        ]);

        $hotel = $this->hotelService->getHotelBySlug($slug);

        if (!$hotel || !$hotel->status) {
            return response()->json([
                'success' => false,
                'message' => __('Hotel not found.'),
            ], 404);
        }

        $rooms = $this->searchService->getAvailableRoomTypesForHotel(
            $hotel->id,
            $request->check_in_date,
            $request->check_out_date,
            $request->guests ?? 2,
            $request->rooms ?? 1
        );

        return response()->json([
            'success' => true,
            'data' => [
                'hotel' => new HotelResource($hotel),
                'room_types' => RoomTypeResource::collection($rooms),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/hotels/suggestions',
        summary: 'Get search suggestions',
        description: 'Get hotel and city suggestions based on query',
        tags: ['Hotels'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['all', 'hotel', 'city'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Suggestions retrieved'),
        ]
    )]
    public function suggestions(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
            'type' => 'sometimes|string|in:all,hotel,city',
        ]);

        $suggestions = $this->searchService->getSuggestions(
            $request->q,
            $request->type ?? 'all'
        );

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/hotels/popular-destinations',
        summary: 'Get popular destinations',
        description: 'Get list of popular hotel destinations',
        tags: ['Hotels'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Popular destinations retrieved'),
        ]
    )]
    public function popularDestinations(Request $request): JsonResponse
    {
        $destinations = $this->searchService->getPopularDestinations($request->limit ?? 10);

        return response()->json([
            'success' => true,
            'data' => $destinations,
        ]);
    }
}
