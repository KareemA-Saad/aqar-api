<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HotelBooking\Http\Requests\StoreHotelRequest;
use Modules\HotelBooking\Http\Requests\UpdateHotelRequest;
use Modules\HotelBooking\Http\Resources\HotelResource;
use Modules\HotelBooking\Http\Resources\HotelCollection;
use Modules\HotelBooking\Services\HotelService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Hotel Management', description: 'Hotel management endpoints for administrators')]
class HotelController extends Controller
{
    protected HotelService $hotelService;

    public function __construct(HotelService $hotelService)
    {
        $this->hotelService = $hotelService;
    }

    #[OA\Get(
        path: '/api/v1/admin/hotels',
        summary: 'List all hotels',
        description: 'Get paginated list of all hotels with optional filters',
        tags: ['Admin Hotel Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'city', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'country', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Hotels list retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(Request $request): HotelCollection
    {
        $hotels = $this->hotelService->getHotels($request->all());
        return new HotelCollection($hotels);
    }

    #[OA\Post(
        path: '/api/v1/admin/hotels',
        summary: 'Create a new hotel',
        description: 'Create a new hotel with the provided details',
        tags: ['Admin Hotel Management'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreHotelRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Hotel created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function store(StoreHotelRequest $request): JsonResponse
    {
        $hotel = $this->hotelService->createHotel($request->validated());

        return response()->json([
            'success' => true,
            'message' => __('Hotel created successfully.'),
            'data' => new HotelResource($hotel),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/hotels/{id}',
        summary: 'Get hotel details',
        description: 'Get detailed information about a specific hotel',
        tags: ['Admin Hotel Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Hotel details retrieved successfully'),
            new OA\Response(response: 404, description: 'Hotel not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $hotel = $this->hotelService->getHotel($id);

        if (!$hotel) {
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

    #[OA\Put(
        path: '/api/v1/admin/hotels/{id}',
        summary: 'Update hotel',
        description: 'Update an existing hotel',
        tags: ['Admin Hotel Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateHotelRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Hotel updated successfully'),
            new OA\Response(response: 404, description: 'Hotel not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function update(UpdateHotelRequest $request, int $id): JsonResponse
    {
        $hotel = $this->hotelService->updateHotel($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => __('Hotel updated successfully.'),
            'data' => new HotelResource($hotel),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/hotels/{id}',
        summary: 'Delete hotel',
        description: 'Delete a hotel (soft delete)',
        tags: ['Admin Hotel Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Hotel deleted successfully'),
            new OA\Response(response: 404, description: 'Hotel not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $this->hotelService->deleteHotel($id);

        return response()->json([
            'success' => true,
            'message' => __('Hotel deleted successfully.'),
        ]);
    }

    #[OA\Patch(
        path: '/api/v1/admin/hotels/{id}/toggle-status',
        summary: 'Toggle hotel status',
        description: 'Toggle the active/inactive status of a hotel',
        tags: ['Admin Hotel Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Hotel status toggled successfully'),
            new OA\Response(response: 404, description: 'Hotel not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function toggleStatus(int $id): JsonResponse
    {
        $hotel = $this->hotelService->toggleStatus($id);

        return response()->json([
            'success' => true,
            'message' => __('Hotel status updated successfully.'),
            'data' => [
                'id' => $hotel->id,
                'status' => $hotel->status,
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/hotels/{id}/images',
        summary: 'Sync hotel images',
        description: 'Add or update hotel images',
        tags: ['Admin Hotel Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'images', type: 'array', items: new OA\Items(type: 'string')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Hotel images synced successfully'),
            new OA\Response(response: 404, description: 'Hotel not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function syncImages(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'string',
        ]);

        $this->hotelService->syncHotelImages($id, $request->images);

        return response()->json([
            'success' => true,
            'message' => __('Hotel images synced successfully.'),
        ]);
    }
}
