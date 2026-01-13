<?php

declare(strict_types=1);

namespace Modules\TwoFactorAuthentication\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\TwoFactorAuthentication\Http\Requests\DisableTwoFactorRequest;
use Modules\TwoFactorAuthentication\Http\Requests\VerifyTwoFactorRequest;
use Modules\TwoFactorAuthentication\Http\Resources\TwoFactorAuthResource;
use Modules\TwoFactorAuthentication\Services\TwoFactorAuthService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Frontend - Two Factor Auth', description: 'User 2FA management endpoints')]
class TwoFactorAuthController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthService $twoFactorAuthService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/frontend/two-factor-auth/status',
        summary: 'Get authenticated user 2FA status',
        security: [['sanctum' => []]],
        tags: ['Frontend - Two Factor Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: '2FA status retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/TwoFactorAuthResource')
            ),
        ]
    )]
    public function status(): JsonResponse
    {
        $userId = Auth::id();
        $settings = $this->twoFactorAuthService->getOrCreateSettings($userId);
        
        return response()->json([
            'success' => true,
            'data' => new TwoFactorAuthResource($settings),
        ]);
    }

    #[OA\Post(
        path: '/api/v1/frontend/two-factor-auth/generate-secret',
        summary: 'Generate new 2FA secret and QR code',
        security: [['sanctum' => []]],
        tags: ['Frontend - Two Factor Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Secret generated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'secret', type: 'string', example: 'ABCDEFGHIJKLMNOP'),
                        new OA\Property(property: 'qr_code_url', type: 'string', example: 'otpauth://totp/...'),
                    ]
                )
            ),
        ]
    )]
    public function generateSecret(): JsonResponse
    {
        $userId = Auth::id();
        $result = $this->twoFactorAuthService->generateSecretKey($userId);
        
        return response()->json([
            'success' => true,
            'message' => 'Secret generated. Scan QR code with Google Authenticator',
            'data' => $result,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/frontend/two-factor-auth/enable',
        summary: 'Enable 2FA after verifying code',
        security: [['sanctum' => []]],
        tags: ['Frontend - Two Factor Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/VerifyTwoFactorRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: '2FA enabled successfully'),
            new OA\Response(response: 400, description: 'Invalid code or setup incomplete'),
        ]
    )]
    public function enable(VerifyTwoFactorRequest $request): JsonResponse
    {
        $userId = Auth::id();
        $enabled = $this->twoFactorAuthService->enableTwoFactor($userId, $request->validated()['code']);
        
        if (!$enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid code or 2FA setup incomplete',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication enabled successfully',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/frontend/two-factor-auth/disable',
        summary: 'Disable 2FA with password verification',
        security: [['sanctum' => []]],
        tags: ['Frontend - Two Factor Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/DisableTwoFactorRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: '2FA disabled successfully'),
            new OA\Response(response: 400, description: 'Invalid password'),
        ]
    )]
    public function disable(DisableTwoFactorRequest $request): JsonResponse
    {
        $userId = Auth::id();
        $disabled = $this->twoFactorAuthService->disableTwoFactor($userId, $request->validated()['password']);
        
        if (!$disabled) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password or 2FA not set up',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication disabled successfully',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/frontend/two-factor-auth/verify',
        summary: 'Verify 2FA code',
        security: [['sanctum' => []]],
        tags: ['Frontend - Two Factor Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/VerifyTwoFactorRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Code verified successfully'),
            new OA\Response(response: 400, description: 'Invalid code'),
        ]
    )]
    public function verify(VerifyTwoFactorRequest $request): JsonResponse
    {
        $userId = Auth::id();
        $valid = $this->twoFactorAuthService->verifyCode($userId, $request->validated()['code']);
        
        if (!$valid) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid authentication code',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Code verified successfully',
        ]);
    }
}
