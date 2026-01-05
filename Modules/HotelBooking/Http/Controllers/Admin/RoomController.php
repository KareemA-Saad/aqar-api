<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HotelBooking\Http\Requests\StoreRoomRequest;
use Modules\HotelBooking\Http\Requests\BlockRoomRequest;
use Modules\HotelBooking\Http\Resources\RoomResource;
use Modules\HotelBooking\Http\Resources\RoomCollection;
use Modules\HotelBooking\Services\RoomService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Room Management', description: 'Room management endpoints for administrators')]
class RoomController extends Controller
{
    protected RoomService $roomService;

    public function __construct(RoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    #[OA\Get(
        path: '/api/v1/admin/room-types/{roomTypeId}/rooms',
        summary: 'List rooms for a room type',
        description: 'Get all rooms for a specific room type',
        tags: ['Admin Room Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'roomTypeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Rooms retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(int $roomTypeId): JsonResponse
    {
        $rooms = $this->roomService->getRoomsByRoomType($roomTypeId);

        return response()->json([
            'success' => true,
            'data' => RoomResource::collection($rooms),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/rooms',
        summary: 'Create a new room',
        description: 'Create a new room for a room type',
        tags: ['Admin Room Management'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreRoomRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Room created successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function store(StoreRoomRequest $request): JsonResponse
    {
        $room = $this->roomService->createRoom($request->validated());

        return response()->json([
            'success' => true,
            'message' => __('Room created successfully.'),
            'data' => new RoomResource($room),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/rooms/{id}',
        summary: 'Get room details',
        description: 'Get detailed information about a specific room',
        tags: ['Admin Room Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Room details retrieved successfully'),
            new OA\Response(response: 404, description: 'Room not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $room = $this->roomService->getRoom($id);

        if (!$room) {
            return response()->json([
                'success' => false,
                'message' => __('Room not found.'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new RoomResource($room),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/rooms/{id}',
        summary: 'Update room',
        description: 'Update an existing room',
        tags: ['Admin Room Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreRoomRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Room updated successfully'),
            new OA\Response(response: 404, description: 'Room not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function update(StoreRoomRequest $request, int $id): JsonResponse
    {
        $room = $this->roomService->updateRoom($id, $request->validated());

        return response()->json([
            'success' => true,
            'message' => __('Room updated successfully.'),
            'data' => new RoomResource($room),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/rooms/{id}',
        summary: 'Delete room',
        description: 'Delete a room',
        tags: ['Admin Room Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Room deleted successfully'),
            new OA\Response(response: 404, description: 'Room not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $this->roomService->deleteRoom($id);

        return response()->json([
            'success' => true,
            'message' => __('Room deleted successfully.'),
        ]);
    }

    #[OA\Patch(
        path: '/api/v1/admin/rooms/{id}/toggle-status',
        summary: 'Toggle room status',
        description: 'Toggle the active/inactive status of a room',
        tags: ['Admin Room Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Room status toggled successfully'),
            new OA\Response(response: 404, description: 'Room not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function toggleStatus(int $id): JsonResponse
    {
        $room = $this->roomService->toggleStatus($id);

        return response()->json([
            'success' => true,
            'message' => __('Room status updated successfully.'),
            'data' => [
                'id' => $room->id,
                'status' => $room->status,
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/rooms/{id}/block',
        summary: 'Block room for dates',
        description: 'Block a room for specific dates (maintenance, renovation, etc.)',
        tags: ['Admin Room Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BlockRoomRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Room blocked successfully'),
            new OA\Response(response: 404, description: 'Room not found'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function block(BlockRoomRequest $request, int $id): JsonResponse
    {
        $this->roomService->blockRoom(
            $id,
            $request->start_date,
            $request->end_date,
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => __('Room blocked successfully.'),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/rooms/{id}/unblock',
        summary: 'Unblock room for dates',
        description: 'Remove block on a room for specific dates',
        tags: ['Admin Room Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'start_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Room unblocked successfully'),
            new OA\Response(response: 404, description: 'Room not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function unblock(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $this->roomService->unblockRoom($id, $request->start_date, $request->end_date);

        return response()->json([
            'success' => true,
            'message' => __('Room unblocked successfully.'),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/rooms/{id}/booked-dates',
        summary: 'Get booked dates',
        description: 'Get all booked dates for a room within a date range',
        tags: ['Admin Room Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'start_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Booked dates retrieved successfully'),
            new OA\Response(response: 404, description: 'Room not found'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function bookedDates(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $dates = $this->roomService->getBookedDates($id, $request->start_date, $request->end_date);

        return response()->json([
            'success' => true,
            'data' => $dates,
        ]);
    }
}
