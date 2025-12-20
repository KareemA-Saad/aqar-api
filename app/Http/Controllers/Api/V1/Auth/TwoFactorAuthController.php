<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\TwoFactor\Disable2FARequest;
use App\Http\Requests\TwoFactor\Enable2FARequest;
use App\Http\Requests\TwoFactor\Verify2FARequest;
use App\Http\Resources\TrustedDeviceResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

/**
 * Two-Factor Authentication Controller
 *
 * Handles 2FA setup, management, and verification for users.
 * Uses the api_user guard with User model.
 *
 * @package App\Http\Controllers\Api\V1\Auth
 */
#[OA\Tag(
    name: 'Two-Factor Authentication',
    description: 'Two-factor authentication management endpoints (Guard: api_user)'
)]
final class TwoFactorAuthController extends BaseApiController
{
    public function __construct(
        private readonly TwoFactorAuthService $twoFactorService,
        private readonly AuthService $authService,
    ) {}

    /**
     * Get 2FA status for the authenticated user.
     */
    #[OA\Get(
        path: '/api/v1/2fa/status',
        summary: 'Get 2FA Status',
        description: 'Returns the current two-factor authentication status for the user.',
        security: [['sanctum_user' => []]],
        tags: ['Two-Factor Authentication']
    )]
    #[OA\Response(
        response: 200,
        description: '2FA status retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'enabled', type: 'boolean', example: true),
                        new OA\Property(property: 'confirmed_at', type: 'string', format: 'date-time', example: '2024-12-17T10:00:00.000000Z', nullable: true),
                        new OA\Property(property: 'trusted_devices_count', type: 'integer', example: 2),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    public function status(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api_user')->user();

        return $this->success([
            'enabled' => $user->hasTwoFactorEnabled(),
            'confirmed_at' => $user->two_factor_confirmed_at?->toISOString(),
            'trusted_devices_count' => $user->trustedDevices()->valid()->count(),
        ]);
    }

    /**
     * Setup 2FA - Generate secret and QR code.
     */
    #[OA\Post(
        path: '/api/v1/2fa/setup',
        summary: 'Setup 2FA',
        description: 'Generates a new 2FA secret and returns a QR code to scan with authenticator app.',
        security: [['sanctum_user' => []]],
        tags: ['Two-Factor Authentication']
    )]
    #[OA\Response(
        response: 200,
        description: 'QR code generated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Scan the QR code with your authenticator app'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'secret', type: 'string', example: 'JBSWY3DPEHPK3PXP'),
                        new OA\Property(property: 'qr_code_url', type: 'string', example: 'data:image/svg+xml;base64,...'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 400, description: '2FA already enabled')]
    public function setup(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api_user')->user();

        // Check if 2FA is already enabled
        if ($user->hasTwoFactorEnabled()) {
            return $this->error('Two-factor authentication is already enabled.', 400);
        }

        // Generate new secret
        $secret = $this->twoFactorService->generateSecretKey();

        // Store temporary secret for verification step
        $this->twoFactorService->storeTemporarySecret($user, $secret);

        // Generate QR code
        $qrCodeUrl = $this->twoFactorService->generateQrCodeDataUrl($user, $secret);

        Log::info('2FA setup initiated', ['user_id' => $user->id]);

        return $this->success([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
        ], 'Scan the QR code with your authenticator app');
    }

    /**
     * Enable 2FA - Verify code and activate.
     */
    #[OA\Post(
        path: '/api/v1/2fa/enable',
        summary: 'Enable 2FA',
        description: 'Verifies the OTP code from authenticator app and enables 2FA for the account.',
        security: [['sanctum_user' => []]],
        tags: ['Two-Factor Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['code'],
            properties: [
                new OA\Property(property: 'code', type: 'string', example: '123456', description: '6-digit code from authenticator app'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '2FA enabled successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Two-factor authentication has been enabled.'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'enabled', type: 'boolean', example: true),
                        new OA\Property(property: 'confirmed_at', type: 'string', format: 'date-time'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 400, description: '2FA already enabled or setup not initiated')]
    #[OA\Response(response: 422, description: 'Invalid verification code')]
    public function enable(Enable2FARequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api_user')->user();

        // Check if 2FA is already enabled
        if ($user->hasTwoFactorEnabled()) {
            return $this->error('Two-factor authentication is already enabled.', 400);
        }

        // Get temporary secret
        $secret = $this->twoFactorService->getTemporarySecret($user);
        if (!$secret) {
            return $this->error('Please initiate 2FA setup first.', 400);
        }

        // Verify the code
        if (!$this->twoFactorService->verifyCode($secret, $request->getCode())) {
            return $this->error('Invalid verification code. Please try again.', 422);
        }

        // Enable 2FA
        $user->enableTwoFactor($secret);

        // Clear temporary secret
        $this->twoFactorService->clearTemporarySecret($user);

        Log::info('2FA enabled', ['user_id' => $user->id]);

        return $this->success([
            'enabled' => true,
            'confirmed_at' => $user->fresh()->two_factor_confirmed_at->toISOString(),
        ], 'Two-factor authentication has been enabled.');
    }

    /**
     * Disable 2FA.
     */
    #[OA\Post(
        path: '/api/v1/2fa/disable',
        summary: 'Disable 2FA',
        description: 'Disables two-factor authentication. Requires password confirmation.',
        security: [['sanctum_user' => []]],
        tags: ['Two-Factor Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['password'],
            properties: [
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'CurrentPassword123!', description: 'Current account password'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: '2FA disabled successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Two-factor authentication has been disabled.'),
            ]
        )
    )]
    #[OA\Response(response: 400, description: '2FA not enabled')]
    #[OA\Response(response: 422, description: 'Invalid password')]
    public function disable(Disable2FARequest $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api_user')->user();

        // Check if 2FA is enabled
        if (!$user->hasTwoFactorEnabled()) {
            return $this->error('Two-factor authentication is not enabled.', 400);
        }

        // Password is validated by the request's current_password rule
        // Disable 2FA
        $user->disableTwoFactor();

        Log::info('2FA disabled', ['user_id' => $user->id]);

        return $this->success(null, 'Two-factor authentication has been disabled.');
    }

    /**
     * Verify 2FA code during login.
     */
    #[OA\Post(
        path: '/api/v1/auth/2fa/verify',
        summary: 'Verify 2FA during Login',
        description: 'Completes the login process by verifying the 2FA code. Use this after receiving a requires_2fa response from login.',
        tags: ['Two-Factor Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['two_factor_token', 'code'],
            properties: [
                new OA\Property(property: 'two_factor_token', type: 'string', example: '2fa_abc123...', description: 'Token received from login response'),
                new OA\Property(property: 'code', type: 'string', example: '123456', description: '6-digit code from authenticator app'),
                new OA\Property(property: 'remember_device', type: 'boolean', example: true, description: 'Remember this device for 30 days'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Login successful',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Login successful'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                        new OA\Property(property: 'token', type: 'string', example: '2|xyz789...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'device_token', type: 'string', example: 'dev_abc123...', nullable: true, description: 'Device token if remember_device was true'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Invalid or expired token')]
    #[OA\Response(response: 422, description: 'Invalid verification code')]
    #[OA\Response(response: 429, description: 'Too many attempts')]
    public function verify(Verify2FARequest $request): JsonResponse
    {
        // Validate the 2FA token
        $user = $this->twoFactorService->validateTwoFactorToken($request->getTwoFactorToken());

        if (!$user) {
            return $this->error('Invalid or expired two-factor token. Please login again.', 401);
        }

        // Verify the OTP code
        $verification = $this->twoFactorService->verifyUserCode($user, $request->getCode());

        if (!$verification['success']) {
            $statusCode = str_contains($verification['message'], 'Too many') ? 429 : 422;
            return $this->error($verification['message'], $statusCode);
        }

        // Consume the 2FA token
        $this->twoFactorService->consumeTwoFactorToken($request->getTwoFactorToken());

        // Generate auth token
        $token = $this->authService->authenticateUserDirect($user);

        $response = [
            'user' => new UserResource($user->load(['tenants.domains', 'latestPaymentLog'])),
            'token' => $token['token'],
            'token_type' => 'Bearer',
            'expires_at' => $token['expires_at'],
            'email_verified' => $user->email_verified,
        ];

        // Handle "Remember this device"
        if ($request->shouldRememberDevice()) {
            $deviceToken = $this->twoFactorService->createTrustedDevice(
                $user,
                $request->userAgent(),
                $request->ip()
            );
            $response['device_token'] = $deviceToken;
        }

        Log::info('2FA verification successful', ['user_id' => $user->id]);

        return $this->success($response, 'Login successful');
    }

    /**
     * Get trusted devices.
     */
    #[OA\Get(
        path: '/api/v1/2fa/devices',
        summary: 'List Trusted Devices',
        description: 'Returns a list of trusted devices for the authenticated user.',
        security: [['sanctum_user' => []]],
        tags: ['Two-Factor Authentication']
    )]
    #[OA\Response(
        response: 200,
        description: 'Trusted devices retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/TrustedDeviceResource')
                ),
            ]
        )
    )]
    public function devices(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api_user')->user();

        $devices = $this->twoFactorService->getTrustedDevices($user);

        return $this->success(TrustedDeviceResource::collection($devices));
    }

    /**
     * Revoke a trusted device.
     */
    #[OA\Delete(
        path: '/api/v1/2fa/devices/{deviceId}',
        summary: 'Revoke Trusted Device',
        description: 'Removes a device from the trusted devices list.',
        security: [['sanctum_user' => []]],
        tags: ['Two-Factor Authentication']
    )]
    #[OA\Parameter(
        name: 'deviceId',
        in: 'path',
        required: true,
        description: 'The ID of the trusted device to revoke',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Device revoked successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Device has been removed from trusted devices.'),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Device not found')]
    public function revokeDevice(int $deviceId): JsonResponse
    {
        /** @var User $user */
        $user = auth('api_user')->user();

        $revoked = $this->twoFactorService->revokeTrustedDevice($user, $deviceId);

        if (!$revoked) {
            return $this->error('Device not found.', 404);
        }

        return $this->success(null, 'Device has been removed from trusted devices.');
    }

    /**
     * Revoke all trusted devices.
     */
    #[OA\Delete(
        path: '/api/v1/2fa/devices',
        summary: 'Revoke All Trusted Devices',
        description: 'Removes all devices from the trusted devices list.',
        security: [['sanctum_user' => []]],
        tags: ['Two-Factor Authentication']
    )]
    #[OA\Response(
        response: 200,
        description: 'All devices revoked successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'All trusted devices have been revoked.'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'revoked_count', type: 'integer', example: 3),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    public function revokeAllDevices(): JsonResponse
    {
        /** @var User $user */
        $user = auth('api_user')->user();

        $count = $this->twoFactorService->revokeAllTrustedDevices($user);

        return $this->success([
            'revoked_count' => $count,
        ], 'All trusted devices have been revoked.');
    }
}
