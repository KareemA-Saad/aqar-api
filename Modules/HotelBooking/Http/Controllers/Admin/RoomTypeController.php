<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HotelBooking\Http\Requests\StoreRoomTypeRequest;
use Modules\HotelBooking\Http\Resources\RoomTypeResource;
use Modules\HotelBooking\Services\RoomTypeService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Room Type Management', description: 'Room type management endpoints for administrators')]
class RoomTypeController extends Controller
{
    protected RoomTypeService $roomTypeService;

    public function __construct(RoomTypeService $roomTypeService)
    {
        $this->roomTypeService = $roomTypeService;
    }

    #[OA\Get(
        path: '/api/v1/admin/hotels/{hotelId}/room-types',
        summary: 'List room types for a hotel',
        description: 'Get all room types for a specific hotel',
        tags: ['Admin Room Type Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'hotelId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Room types retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(int $hotelId): JsonResponse
    {
        $roomTypes = $this->roomTypeService->getRoomTypesByHotel($hotelId);

        return response()->json([
            'success' => true,
            'data' => RoomTypeResource::collection($roomTypes),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/room-types',
        summary: 'Create a new room type',
        description: 'Create a new room type for a hotel',
        tags: ['Admin Room Type Management'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreRoomTypeRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Room type created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function store(StoreRoomTypeRequest $request): JsonResponse
    {
        $roomType = $this->roomTypeService->createRoomType($request->validated());

        return response()->json([
            'success' => true,
            'message' => __('Room type created successfully.'),
            'data' => new RoomTypeResource($roomType),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/room-types/{id}',
        summary: 'Get room type details',
        description: 'Get detailed information about a specific room type',
        tags: ['Admin Room Type Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Room type details retrieved successfully'),
            new OA\Response(response: 404, description: 'Room type not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $roomType = $this->roomTypeService->getRoomType($id);

        if (!$roomType) {
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

    #[OA\Put(
        path: '/api/v1/admin/room-types/{id}',
        summary: 'Update room type',
        description: 'Update an existing room type',
        tags: ['Admin Room Type Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreRoomTypeRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Room type updated successfully'),
            new OA\Response(response: 404, description: 'Room type not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function update(StoreRoomTypeRequest $request, int $id): JsonResponse
    {
        $roomType = $this->roomTypeService->updateRoomType($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => __('Room type updated successfully.'),
            'data' => new RoomTypeResource($roomType),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/room-types/{id}',
        summary: 'Delete room type',
        description: 'Delete a room type',
        tags: ['Admin Room Type Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Room type deleted successfully'),
            new OA\Response(response: 404, description: 'Room type not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $this->roomTypeService->deleteRoomType($id);

        return response()->json([
            'success' => true,
            'message' => __('Room type deleted successfully.'),
        ]);
    }
}
