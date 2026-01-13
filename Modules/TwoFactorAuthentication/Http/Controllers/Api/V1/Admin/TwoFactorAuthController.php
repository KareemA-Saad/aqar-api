<?php

declare(strict_types=1);

namespace Modules\TwoFactorAuthentication\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\TwoFactorAuthentication\Http\Resources\TwoFactorAuthResource;
use Modules\TwoFactorAuthentication\Services\TwoFactorAuthService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Two Factor Auth', description: '2FA administration endpoints')]
class TwoFactorAuthController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthService $twoFactorAuthService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/two-factor-auth/users',
        summary: 'Get users with 2FA enabled',
        tags: ['Admin - Two Factor Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Users with 2FA retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TwoFactorAuthResource')),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function usersWithTwoFactor(): JsonResponse
    {
        $users = $this->twoFactorAuthService->getUsersWithTwoFactorEnabled();
        
        return response()->json([
            'success' => true,
            'data' => TwoFactorAuthResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/two-factor-auth/statistics',
        summary: 'Get 2FA statistics',
        tags: ['Admin - Two Factor Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statistics retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_users_with_2fa_setup', type: 'integer', example: 50),
                        new OA\Property(property: 'users_with_2fa_enabled', type: 'integer', example: 35),
                        new OA\Property(property: 'users_with_2fa_disabled', type: 'integer', example: 15),
                        new OA\Property(property: 'percentage_enabled', type: 'number', example: 70.0),
                    ]
                )
            ),
        ]
    )]
    public function statistics(): JsonResponse
    {
        $statistics = $this->twoFactorAuthService->getStatistics();
        
        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/two-factor-auth/disable/{userId}',
        summary: 'Admin force disable 2FA for user',
        tags: ['Admin - Two Factor Auth'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: '2FA disabled successfully'),
            new OA\Response(response: 404, description: 'User 2FA settings not found'),
        ]
    )]
    public function adminDisable(int $userId): JsonResponse
    {
        $disabled = $this->twoFactorAuthService->adminDisableTwoFactor($userId);
        
        if (!$disabled) {
            return response()->json([
                'success' => false,
                'message' => 'User 2FA settings not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => '2FA disabled successfully for user',
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/two-factor-auth/user/{userId}',
        summary: 'Get user 2FA settings',
        tags: ['Admin - Two Factor Auth'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '2FA settings retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/TwoFactorAuthResource')
            ),
            new OA\Response(response: 404, description: 'Settings not found'),
        ]
    )]
    public function getUserSettings(int $userId): JsonResponse
    {
        $settings = $this->twoFactorAuthService->getUserSettings($userId);
        
        if (!$settings) {
            return response()->json([
                'success' => false,
                'message' => 'User 2FA settings not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new TwoFactorAuthResource($settings->load('user')),
        ]);
    }
}
