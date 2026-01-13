<?php

declare(strict_types=1);

namespace Modules\Wallet\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Wallet\Http\Requests\UpdateWalletSettingsRequest;
use Modules\Wallet\Http\Resources\WalletSettingsResource;
use Modules\Wallet\Services\WalletSettingsService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Wallet Settings', description: 'Wallet settings management endpoints')]
class WalletSettingsController extends Controller
{
    public function __construct(
        private readonly WalletSettingsService $walletSettingsService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/wallet-settings/user/{userId}',
        summary: 'Get wallet settings by user ID',
        tags: ['Admin - Wallet Settings'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Wallet settings retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/WalletSettingsResource')
            ),
            new OA\Response(response: 404, description: 'Wallet settings not found'),
        ]
    )]
    public function show(int $userId): JsonResponse
    {
        $settings = $this->walletSettingsService->getOrCreateSettings($userId);
        
        return response()->json([
            'success' => true,
            'data' => new WalletSettingsResource($settings),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/wallet-settings/user/{userId}',
        summary: 'Update wallet settings',
        tags: ['Admin - Wallet Settings'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateWalletSettingsRequest')
        ),
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Wallet settings updated successfully'),
        ]
    )]
    public function update(UpdateWalletSettingsRequest $request, int $userId): JsonResponse
    {
        $this->walletSettingsService->updateSettings($userId, $request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Wallet settings updated successfully',
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/wallet-settings/user/{userId}',
        summary: 'Delete wallet settings',
        tags: ['Admin - Wallet Settings'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Wallet settings deleted successfully'),
            new OA\Response(response: 404, description: 'Wallet settings not found'),
        ]
    )]
    public function destroy(int $userId): JsonResponse
    {
        $deleted = $this->walletSettingsService->deleteSettings($userId);
        
        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet settings not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Wallet settings deleted successfully',
        ]);
    }
}
