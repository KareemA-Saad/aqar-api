<?php

declare(strict_types=1);

namespace Modules\Wallet\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Wallet\Http\Requests\AddFundsRequest;
use Modules\Wallet\Http\Requests\DeductFundsRequest;
use Modules\Wallet\Http\Requests\UpdateWalletSettingsRequest;
use Modules\Wallet\Http\Resources\WalletHistoryResource;
use Modules\Wallet\Http\Resources\WalletResource;
use Modules\Wallet\Http\Resources\WalletSettingsResource;
use Modules\Wallet\Services\WalletHistoryService;
use Modules\Wallet\Services\WalletService;
use Modules\Wallet\Services\WalletSettingsService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Frontend - Wallet', description: 'User wallet endpoints')]
class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly WalletHistoryService $walletHistoryService,
        private readonly WalletSettingsService $walletSettingsService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/frontend/wallet',
        summary: 'Get authenticated user wallet',
        security: [['sanctum' => []]],
        tags: ['Frontend - Wallet'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Wallet retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/WalletResource')
            ),
        ]
    )]
    public function show(): JsonResponse
    {
        $userId = Auth::id();
        $wallet = $this->walletService->getOrCreateWallet($userId);
        
        return response()->json([
            'success' => true,
            'data' => new WalletResource($wallet->load('walletSettings')),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/frontend/wallet/history',
        summary: 'Get authenticated user wallet transaction history',
        security: [['sanctum' => []]],
        tags: ['Frontend - Wallet'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Wallet history retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/WalletHistoryResource')),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function history(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $perPage = (int) $request->input('per_page', 15);
        
        $histories = $this->walletHistoryService->getUserHistory($userId, $perPage);
        
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

    #[OA\Post(
        path: '/api/v1/frontend/wallet/add-funds',
        summary: 'Add funds to wallet',
        security: [['sanctum' => []]],
        tags: ['Frontend - Wallet'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/AddFundsRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Funds added successfully'),
            new OA\Response(response: 400, description: 'Failed to add funds'),
        ]
    )]
    public function addFunds(AddFundsRequest $request): JsonResponse
    {
        $userId = Auth::id();
        $validated = $request->validated();
        
        $success = $this->walletService->addFunds(
            $userId,
            $validated['amount'],
            [
                'payment_gateway' => $validated['payment_gateway'] ?? null,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'payment_status' => 'completed',
            ]
        );
        
        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add funds',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Funds added successfully',
        ]);
    }

    #[OA\Get(
        path: '/api/v1/frontend/wallet/settings',
        summary: 'Get wallet settings',
        security: [['sanctum' => []]],
        tags: ['Frontend - Wallet'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Wallet settings retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/WalletSettingsResource')
            ),
        ]
    )]
    public function getSettings(): JsonResponse
    {
        $userId = Auth::id();
        $settings = $this->walletSettingsService->getOrCreateSettings($userId);
        
        return response()->json([
            'success' => true,
            'data' => new WalletSettingsResource($settings),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/frontend/wallet/settings',
        summary: 'Update wallet settings',
        security: [['sanctum' => []]],
        tags: ['Frontend - Wallet'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateWalletSettingsRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Wallet settings updated successfully'),
        ]
    )]
    public function updateSettings(UpdateWalletSettingsRequest $request): JsonResponse
    {
        $userId = Auth::id();
        $this->walletSettingsService->updateSettings($userId, $request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Wallet settings updated successfully',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/frontend/wallet/settings/toggle-auto-renew',
        summary: 'Toggle auto-renew package setting',
        security: [['sanctum' => []]],
        tags: ['Frontend - Wallet'],
        responses: [
            new OA\Response(response: 200, description: 'Auto-renew toggled successfully'),
        ]
    )]
    public function toggleAutoRenew(): JsonResponse
    {
        $userId = Auth::id();
        $this->walletSettingsService->toggleAutoRenew($userId);
        
        return response()->json([
            'success' => true,
            'message' => 'Auto-renew setting toggled successfully',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/frontend/wallet/settings/toggle-alert',
        summary: 'Toggle wallet alert setting',
        security: [['sanctum' => []]],
        tags: ['Frontend - Wallet'],
        responses: [
            new OA\Response(response: 200, description: 'Wallet alert toggled successfully'),
        ]
    )]
    public function toggleAlert(): JsonResponse
    {
        $userId = Auth::id();
        $this->walletSettingsService->toggleWalletAlert($userId);
        
        return response()->json([
            'success' => true,
            'message' => 'Wallet alert setting toggled successfully',
        ]);
    }
}
