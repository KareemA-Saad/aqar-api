<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\HotelBooking\Http\Requests\UpdateInventoryRequest;
use Modules\HotelBooking\Http\Requests\BulkInventoryRequest;
use Modules\HotelBooking\Http\Resources\InventoryResource;
use Modules\HotelBooking\Services\InventoryService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin Inventory Management', description: 'Inventory and pricing management endpoints')]
class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    #[OA\Get(
        path: '/api/v1/admin/room-types/{roomTypeId}/inventory',
        summary: 'Get inventory for room type',
        description: 'Get inventory data for a room type within a date range',
        tags: ['Admin Inventory Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'roomTypeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'start_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Inventory retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(Request $request, int $roomTypeId): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $inventory = $this->inventoryService->getInventory(
            $roomTypeId,
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'success' => true,
            'data' => InventoryResource::collection($inventory),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/room-types/{roomTypeId}/inventory/{date}',
        summary: 'Update inventory for a date',
        description: 'Update inventory (price, availability) for a specific date',
        tags: ['Admin Inventory Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'roomTypeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'date', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateInventoryRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Inventory updated successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function update(UpdateInventoryRequest $request, int $roomTypeId, string $date): JsonResponse
    {
        $inventory = $this->inventoryService->updateInventory($roomTypeId, $date, $request->validated());

        return response()->json([
            'success' => true,
            'message' => __('Inventory updated successfully.'),
            'data' => new InventoryResource($inventory),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/room-types/{roomTypeId}/inventory/bulk',
        summary: 'Bulk update inventory',
        description: 'Update inventory for a date range',
        tags: ['Admin Inventory Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'roomTypeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BulkInventoryRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Inventory bulk updated successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function bulkUpdate(BulkInventoryRequest $request, int $roomTypeId): JsonResponse
    {
        $count = $this->inventoryService->bulkUpdateInventory(
            $roomTypeId,
            $request->start_date,
            $request->end_date,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => __(':count inventory records updated.', ['count' => $count]),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/room-types/{roomTypeId}/inventory/initialize',
        summary: 'Initialize inventory',
        description: 'Initialize inventory for a room type for the next N days',
        tags: ['Admin Inventory Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'roomTypeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'total_rooms', type: 'integer'),
                    new OA\Property(property: 'base_price', type: 'number'),
                    new OA\Property(property: 'days', type: 'integer', default: 365),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Inventory initialized successfully'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function initialize(Request $request, int $roomTypeId): JsonResponse
    {
        $request->validate([
            'total_rooms' => 'required|integer|min:1',
            'base_price' => 'required|numeric|min:0',
            'days' => 'sometimes|integer|min:1|max:730',
        ]);

        $count = $this->inventoryService->initializeInventory(
            $roomTypeId,
            $request->total_rooms,
            $request->base_price,
            $request->days ?? 365
        );

        return response()->json([
            'success' => true,
            'message' => __(':count inventory records created.', ['count' => $count]),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/room-types/{roomTypeId}/inventory/block',
        summary: 'Block dates',
        description: 'Block room type for specific dates',
        tags: ['Admin Inventory Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'roomTypeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'start_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'reason', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Dates blocked successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function blockDates(Request $request, int $roomTypeId): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'reason' => 'sometimes|string|max:255',
        ]);

        $count = $this->inventoryService->blockDates(
            $roomTypeId,
            $request->start_date,
            $request->end_date,
            $request->reason
        );

        return response()->json([
            'success' => true,
            'message' => __(':count dates blocked.', ['count' => $count]),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/room-types/{roomTypeId}/inventory/unblock',
        summary: 'Unblock dates',
        description: 'Unblock room type for specific dates',
        tags: ['Admin Inventory Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'roomTypeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
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
            new OA\Response(response: 200, description: 'Dates unblocked successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function unblockDates(Request $request, int $roomTypeId): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $count = $this->inventoryService->unblockDates(
            $roomTypeId,
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'success' => true,
            'message' => __(':count dates unblocked.', ['count' => $count]),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/room-types/{roomTypeId}/inventory/seasonal-pricing',
        summary: 'Set seasonal pricing',
        description: 'Set special pricing for a date range (optionally specific days of week)',
        tags: ['Admin Inventory Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'roomTypeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'start_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'end_date', type: 'string', format: 'date'),
                    new OA\Property(property: 'price', type: 'number'),
                    new OA\Property(property: 'days_of_week', type: 'array', items: new OA\Items(type: 'integer')),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Seasonal pricing set successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function setSeasonalPricing(Request $request, int $roomTypeId): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'price' => 'required|numeric|min:0',
            'days_of_week' => 'sometimes|array',
            'days_of_week.*' => 'integer|min:0|max:6',
        ]);

        $count = $this->inventoryService->setSeasonalPricing(
            $roomTypeId,
            $request->start_date,
            $request->end_date,
            $request->price,
            $request->days_of_week
        );

        return response()->json([
            'success' => true,
            'message' => __(':count dates updated with new pricing.', ['count' => $count]),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/room-types/{roomTypeId}/inventory/calendar',
        summary: 'Get calendar view',
        description: 'Get monthly calendar view of inventory',
        tags: ['Admin Inventory Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'roomTypeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'month', in: 'query', required: true, schema: new OA\Schema(type: 'string', example: '2024-01')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Calendar view retrieved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function calendar(Request $request, int $roomTypeId): JsonResponse
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $calendar = $this->inventoryService->getCalendarView($roomTypeId, $request->month);

        return response()->json([
            'success' => true,
            'data' => $calendar,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/room-types/{roomTypeId}/inventory/statistics',
        summary: 'Get occupancy statistics',
        description: 'Get occupancy and revenue statistics for a date range',
        tags: ['Admin Inventory Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'roomTypeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'start_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'end_date', in: 'query', required: true, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Statistics retrieved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function statistics(Request $request, int $roomTypeId): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $stats = $this->inventoryService->getOccupancyStats(
            $roomTypeId,
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/room-types/{roomTypeId}/inventory/sync',
        summary: 'Sync inventory with room count',
        description: 'Sync inventory total rooms with actual room count',
        tags: ['Admin Inventory Management'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'roomTypeId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Inventory synced successfully'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function sync(int $roomTypeId): JsonResponse
    {
        $this->inventoryService->syncInventoryWithRoomCount($roomTypeId);

        return response()->json([
            'success' => true,
            'message' => __('Inventory synced with room count.'),
        ]);
    }
}
