<?php

declare(strict_types=1);

namespace Modules\Wallet\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Wallet\Http\Requests\BulkWalletRequest;
use Modules\Wallet\Http\Requests\UpdateWalletBalanceRequest;
use Modules\Wallet\Http\Resources\WalletResource;
use Modules\Wallet\Services\WalletService;
use Modules\Wallet\Entities\Wallet;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Wallet', description: 'Wallet management endpoints')]
class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/wallets',
        summary: 'Get paginated list of wallets',
        tags: ['Admin - Wallet'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'user_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'min_balance', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'max_balance', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['balance', 'created_at', 'updated_at'])),
            new OA\Parameter(name: 'sort_order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Wallets retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/WalletResource')),
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(property: 'links', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $wallets = $this->walletService->getWallets($request->all());
        
        return response()->json([
            'success' => true,
            'data' => WalletResource::collection($wallets),
            'meta' => [
                'current_page' => $wallets->currentPage(),
                'last_page' => $wallets->lastPage(),
                'per_page' => $wallets->perPage(),
                'total' => $wallets->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/wallets/{id}',
        summary: 'Get wallet by ID',
        tags: ['Admin - Wallet'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Wallet retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/WalletResource')
            ),
            new OA\Response(response: 404, description: 'Wallet not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $wallet = $this->walletService->getWalletById($id);
        
        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new WalletResource($wallet),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/wallets/{id}/balance',
        summary: 'Update wallet balance',
        tags: ['Admin - Wallet'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateWalletBalanceRequest')
        ),
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Balance updated successfully'),
            new OA\Response(response: 404, description: 'Wallet not found'),
        ]
    )]
    public function updateBalance(UpdateWalletBalanceRequest $request, int $id): JsonResponse
    {
        $updated = $this->walletService->updateBalance($id, $request->validated()['balance']);
        
        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Wallet balance updated successfully',
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/wallets/{id}/status',
        summary: 'Update wallet status',
        tags: ['Admin - Wallet'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'integer', example: 1),
                ]
            )
        ),
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Status updated successfully'),
            new OA\Response(response: 404, description: 'Wallet not found'),
        ]
    )]
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate(['status' => 'required|integer|in:0,1']);
        
        $updated = $this->walletService->updateStatus($id, $request->input('status'));
        
        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Wallet status updated successfully',
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/wallets/{id}',
        summary: 'Delete wallet',
        tags: ['Admin - Wallet'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Wallet deleted successfully'),
            new OA\Response(response: 404, description: 'Wallet not found'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->walletService->deleteWallet($id);
        
        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Wallet deleted successfully',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/wallets/bulk',
        summary: 'Perform bulk operations on wallets',
        tags: ['Admin - Wallet'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BulkWalletRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Bulk operation completed successfully'),
        ]
    )]
    public function bulkAction(BulkWalletRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $walletIds = $validated['wallet_ids'];

        $count = match ($action) {
            'delete' => $this->walletService->bulkDelete($walletIds),
            'activate' => $this->walletService->bulkActivate($walletIds),
            'deactivate' => $this->walletService->bulkDeactivate($walletIds),
        };

        return response()->json([
            'success' => true,
            'message' => "Successfully {$action}d {$count} wallet(s)",
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/wallets/statistics/overview',
        summary: 'Get wallet statistics',
        tags: ['Admin - Wallet'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statistics retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_wallets', type: 'integer', example: 100),
                        new OA\Property(property: 'active_wallets', type: 'integer', example: 85),
                        new OA\Property(property: 'inactive_wallets', type: 'integer', example: 15),
                        new OA\Property(property: 'total_balance', type: 'number', example: 15000.50),
                        new OA\Property(property: 'average_balance', type: 'number', example: 150.50),
                        new OA\Property(property: 'low_balance_count', type: 'integer', example: 5),
                    ]
                )
            ),
        ]
    )]
    public function statistics(): JsonResponse
    {
        $statistics = $this->walletService->getStatistics();
        
        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/wallets/low-balance/list',
        summary: 'Get wallets with low balance',
        tags: ['Admin - Wallet'],
        parameters: [
            new OA\Parameter(name: 'threshold', in: 'query', schema: new OA\Schema(type: 'number', default: 10)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Low balance wallets retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/WalletResource')),
                    ]
                )
            ),
        ]
    )]
    public function lowBalanceWallets(Request $request): JsonResponse
    {
        $threshold = (float) $request->input('threshold', 10);
        $limit = (int) $request->input('limit', 10);
        
        $wallets = $this->walletService->getLowBalanceWallets($threshold, $limit);
        
        return response()->json([
            'success' => true,
            'data' => WalletResource::collection($wallets),
        ]);
    }
}
