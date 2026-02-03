<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tenant\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Tenant\Admin\AvatarRequest;
use App\Http\Requests\Tenant\Admin\PasswordRequest;
use App\Http\Requests\Tenant\Admin\ProfileRequest;
use App\Http\Requests\Tenant\Admin\TwoFactorRequest;
use App\Http\Resources\Tenant\AdminProfileResource;
use App\Models\Admin;
use App\Services\TwoFactorAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin Profile Controller
 *
 * Handles admin profile management within tenant context.
 *
 * @package App\Http\Controllers\Api\V1\Tenant\Admin
 */
#[OA\Tag(
    name: 'Tenant Admin Profile',
    description: 'Tenant admin profile management endpoints'
)]
final class ProfileController extends BaseApiController
{
    public function __construct(
        private readonly TwoFactorAuthService $twoFactorService,
    ) {}

    /**
     * Get current admin profile.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/profile',
        summary: 'Get admin profile',
        description: 'Get the current authenticated admin\'s profile',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Profile'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Profile retrieved successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/AdminProfileResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function show(): JsonResponse
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        return $this->success(
            new AdminProfileResource($user),
            'Profile retrieved successfully'
        );
    }

    /**
     * Update admin profile.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/profile',
        summary: 'Update admin profile',
        description: 'Update the current authenticated admin\'s profile',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Profile'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TenantAdminProfileRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Profile updated successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/AdminProfileResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(ProfileRequest $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $data = $request->validatedData();
        $user->update($data);

        Log::info('Admin profile updated', ['admin_id' => $user->id]);

        return $this->success(
            new AdminProfileResource($user->fresh()),
            'Profile updated successfully'
        );
    }

    /**
     * Change admin password.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/profile/change-password',
        summary: 'Change password',
        description: 'Change the current authenticated admin\'s password',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Profile'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string', format: 'password', example: 'OldPassword123!'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewPassword123!'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewPassword123!'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Password changed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Password changed successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error or incorrect current password'),
        ]
    )]
    public function changePassword(PasswordRequest $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        // Verify current password
        if (!Hash::check($request->input('current_password'), $user->password)) {
            return $this->validationError(
                ['current_password' => ['The current password is incorrect.']],
                'Password verification failed'
            );
        }

        $user->update([
            'password' => Hash::make($request->input('password')),
        ]);

        Log::info('Admin password changed', ['admin_id' => $user->id]);

        return $this->success(null, 'Password changed successfully');
    }

    /**
     * Update admin avatar.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/profile/avatar',
        summary: 'Update avatar',
        description: 'Update the current authenticated admin\'s avatar',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Profile'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['avatar_id'],
                properties: [
                    new OA\Property(property: 'avatar_id', type: 'integer', example: 123, description: 'Media ID of the avatar image'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Avatar updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Avatar updated successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/AdminProfileResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateAvatar(AvatarRequest $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $user->update([
            'image' => $request->input('avatar_id'),
        ]);

        Log::info('Admin avatar updated', ['admin_id' => $user->id]);

        return $this->success(
            new AdminProfileResource($user->fresh()),
            'Avatar updated successfully'
        );
    }

    /**
     * Get 2FA setup information.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/profile/2fa',
        summary: 'Get 2FA status',
        description: 'Get two-factor authentication status and setup information',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Profile'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '2FA status retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '2FA status retrieved'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'enabled', type: 'boolean', example: false),
                                new OA\Property(property: 'confirmed_at', type: 'string', format: 'date-time', nullable: true),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function twoFactorSetup(): JsonResponse
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        $enabled = !empty($user->google_2fa_secret) && $user->google_2fa_enable;

        return $this->success([
            'enabled' => $enabled,
            'confirmed_at' => $enabled ? $user->updated_at?->toISOString() : null,
        ], '2FA status retrieved');
    }

    /**
     * Setup and enable 2FA.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/profile/2fa/enable',
        summary: 'Enable 2FA',
        description: 'Setup and enable two-factor authentication',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Profile'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'code', type: 'string', example: '123456', description: 'OTP code to verify (required if secret already generated)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '2FA enabled or setup initiated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '2FA enabled successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'secret', type: 'string', example: 'JBSWY3DPEHPK3PXP', nullable: true),
                                new OA\Property(property: 'qr_code_url', type: 'string', example: 'data:image/svg+xml;base64,...', nullable: true),
                                new OA\Property(property: 'enabled', type: 'boolean', example: true),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: '2FA already enabled'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Invalid verification code'),
        ]
    )]
    public function enableTwoFactor(TwoFactorRequest $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        // Check if already enabled
        if (!empty($user->google_2fa_secret) && $user->google_2fa_enable) {
            return $this->error('Two-factor authentication is already enabled.', 400);
        }

        // If code is provided, verify and enable
        if ($request->has('code')) {
            $tempSecret = $this->twoFactorService->getTemporarySecret($user);
            
            if (!$tempSecret) {
                return $this->error('Please initiate 2FA setup first.', 400);
            }

            if (!$this->twoFactorService->verifyCode($tempSecret, $request->input('code'))) {
                return $this->validationError(
                    ['code' => ['Invalid verification code.']],
                    'Invalid verification code'
                );
            }

            // Enable 2FA
            $user->update([
                'google_2fa_secret' => $tempSecret,
                'google_2fa_enable' => true,
            ]);

            $this->twoFactorService->clearTemporarySecret($user);

            Log::info('Admin 2FA enabled', ['admin_id' => $user->id]);

            return $this->success([
                'enabled' => true,
            ], '2FA enabled successfully');
        }

        // Generate new secret and QR code
        $secret = $this->twoFactorService->generateSecretKey();
        $this->twoFactorService->storeTemporarySecret($user, $secret);
        $qrCodeUrl = $this->twoFactorService->generateQrCodeDataUrl($user, $secret);

        return $this->success([
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'enabled' => false,
        ], 'Scan the QR code with your authenticator app');
    }

    /**
     * Disable 2FA.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/profile/2fa/disable',
        summary: 'Disable 2FA',
        description: 'Disable two-factor authentication (requires password confirmation)',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Profile'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['password'],
                properties: [
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'YourPassword123!'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: '2FA disabled successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: '2FA disabled successfully'),
                    ]
                )
            ),
            new OA\Response(response: 400, description: '2FA not enabled'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Incorrect password'),
        ]
    )]
    public function disableTwoFactor(PasswordRequest $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            return $this->error('Unauthenticated', 401);
        }

        // Check if 2FA is enabled
        if (empty($user->google_2fa_secret) || !$user->google_2fa_enable) {
            return $this->error('Two-factor authentication is not enabled.', 400);
        }

        // Verify password
        $password = $request->input('password') ?? $request->input('current_password');
        if (!Hash::check($password, $user->password)) {
            return $this->validationError(
                ['password' => ['The password is incorrect.']],
                'Password verification failed'
            );
        }

        // Disable 2FA
        $user->update([
            'google_2fa_secret' => null,
            'google_2fa_enable' => false,
        ]);

        Log::info('Admin 2FA disabled', ['admin_id' => $user->id]);

        return $this->success(null, '2FA disabled successfully');
    }

    /**
     * Get the authenticated user (admin or user based on guard).
     *
     * @return \App\Models\Admin|\App\Models\User|null
     */
    private function getAuthenticatedUser()
    {
        // Try different guards
        $guards = ['api_admin', 'api_user', 'sanctum'];
        
        foreach ($guards as $guard) {
            $user = auth($guard)->user();
            if ($user) {
                return $user;
            }
        }

        return null;
    }
}
