<?php

declare(strict_types=1);

namespace Modules\Wallet\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Wallet\Http\Requests\BulkWalletHistoryRequest;
use Modules\Wallet\Http\Resources\WalletHistoryResource;
use Modules\Wallet\Services\WalletHistoryService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Wallet History', description: 'Wallet transaction history management endpoints')]
class WalletHistoryController extends Controller
{
    public function __construct(
        private readonly WalletHistoryService $walletHistoryService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/wallet-histories',
        summary: 'Get paginated list of wallet transaction histories',
        tags: ['Admin - Wallet History'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'user_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'payment_gateway', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'payment_status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'min_amount', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'max_amount', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['amount', 'created_at', 'updated_at'])),
            new OA\Parameter(name: 'sort_order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Wallet histories retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/WalletHistoryResource')),
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(property: 'links', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $histories = $this->walletHistoryService->getHistories($request->all());
        
        return response()->json([
            'success' => true,
            'data' => WalletHistoryResource::collection($histories),
            'meta' => [
                'current_page' => $histories->currentPage(),
                'last_page' => $histories->lastPage(),
                'per_page' => $histories->perPage(),
                'total' => $histories->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/wallet-histories/{id}',
        summary: 'Get wallet history by ID',
        tags: ['Admin - Wallet History'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Wallet history retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/WalletHistoryResource')
            ),
            new OA\Response(response: 404, description: 'Wallet history not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $history = $this->walletHistoryService->getHistoryById($id);
        
        if (!$history) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet history not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new WalletHistoryResource($history),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/wallet-histories/{id}/status',
        summary: 'Update payment status',
        tags: ['Admin - Wallet History'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['payment_status'],
                properties: [
                    new OA\Property(property: 'payment_status', type: 'string', example: 'completed'),
                ]
            )
        ),
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Payment status updated successfully'),
            new OA\Response(response: 404, description: 'Wallet history not found'),
        ]
    )]
    public function updatePaymentStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'payment_status' => 'required|string|in:pending,completed,failed',
        ]);
        
        $updated = $this->walletHistoryService->updatePaymentStatus($id, $request->input('payment_status'));
        
        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet history not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment status updated successfully',
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/wallet-histories/{id}',
        summary: 'Delete wallet history',
        tags: ['Admin - Wallet History'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Wallet history deleted successfully'),
            new OA\Response(response: 404, description: 'Wallet history not found'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->walletHistoryService->deleteHistory($id);
        
        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet history not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Wallet history deleted successfully',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/wallet-histories/bulk',
        summary: 'Perform bulk operations on wallet histories',
        tags: ['Admin - Wallet History'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BulkWalletHistoryRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Bulk operation completed successfully'),
        ]
    )]
    public function bulkAction(BulkWalletHistoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $historyIds = $validated['history_ids'];

        $count = match ($action) {
            'delete' => $this->walletHistoryService->bulkDelete($historyIds),
        };

        return response()->json([
            'success' => true,
            'message' => "Successfully {$action}d {$count} wallet histor(ies)",
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/wallet-histories/statistics/overview',
        summary: 'Get wallet transaction statistics',
        tags: ['Admin - Wallet History'],
        parameters: [
            new OA\Parameter(name: 'user_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statistics retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_transactions', type: 'integer', example: 150),
                        new OA\Property(property: 'total_amount', type: 'number', example: 5000.00),
                        new OA\Property(property: 'completed_transactions', type: 'integer', example: 140),
                        new OA\Property(property: 'pending_transactions', type: 'integer', example: 8),
                        new OA\Property(property: 'failed_transactions', type: 'integer', example: 2),
                        new OA\Property(property: 'average_transaction', type: 'number', example: 33.33),
                    ]
                )
            ),
        ]
    )]
    public function statistics(Request $request): JsonResponse
    {
        $statistics = $this->walletHistoryService->getStatistics($request->all());
        
        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }
}
