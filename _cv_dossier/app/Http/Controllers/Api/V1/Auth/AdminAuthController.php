<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\AdminResource;
use App\Mail\PasswordResetMail;
use App\Models\Admin;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use OpenApi\Attributes as OA;

/**
 * Admin Authentication Controller
 *
 * Handles authentication for platform administrators.
 * Uses the api_admin guard with Admin model.
 *
 * @package App\Http\Controllers\Api\V1\Auth
 */
final class AdminAuthController extends BaseApiController
{
    private const PASSWORD_RESET_TABLE = 'password_reset_tokens';

    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Authenticate admin and return token.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     *
     * @unauthenticated
     */
    #[OA\Post(
        path: '/api/v1/admin/auth/login',
        summary: 'Admin Login',
        description: 'Authenticate an admin user and receive a bearer token',
        tags: ['Admin Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['credential', 'password'],
            properties: [
                new OA\Property(property: 'credential', type: 'string', example: 'admin@aqar.com', description: 'Email or username'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'SecurePassword123!', description: 'Admin password'),
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
                        new OA\Property(property: 'admin', ref: '#/components/schemas/AdminResource'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abcdef123456...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', example: '2024-12-31T23:59:59.000000Z'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Invalid credentials',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Invalid credentials'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The credential field is required.'),
                new OA\Property(
                    property: 'errors',
                    properties: [
                        new OA\Property(property: 'credential', type: 'array', items: new OA\Items(type: 'string', example: 'The credential field is required.')),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->authenticateAdmin(
                $request->getCredential(),
                $request->getPassword()
            );

            return $this->success([
                'admin' => new AdminResource($result['admin']->load(['roles', 'permissions'])),
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'expires_at' => $result['expires_at'],
            ], 'Login successful');
        } catch (AuthenticationException $e) {
            return $this->error('Invalid credentials', 401);
        }
    }

    /**
     * Revoke current token (logout).
     *
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/admin/auth/logout',
        summary: 'Admin Logout',
        description: 'Revoke the current admin authentication token',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Authentication']
    )]
    #[OA\Response(
        response: 200,
        description: 'Logged out successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Logged out successfully'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    public function logout(): JsonResponse
    {
        $admin = auth('api_admin')->user();

        if (!$admin) {
            return $this->unauthorized();
        }

        $this->authService->logout($admin);

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Get authenticated admin profile.
     *
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/admin/auth/me',
        summary: 'Get Admin Profile',
        description: 'Retrieve the authenticated admin profile with roles and permissions',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Authentication']
    )]
    #[OA\Response(
        response: 200,
        description: 'Admin profile retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Admin profile retrieved'),
                new OA\Property(property: 'data', ref: '#/components/schemas/AdminResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    public function me(): JsonResponse
    {
        $admin = auth('api_admin')->user();

        if (!$admin) {
            return $this->unauthorized();
        }

        return $this->success(
            new AdminResource($admin->load(['roles', 'permissions'])),
            'Admin profile retrieved'
        );
    }

    /**
     * Refresh token - revoke current and issue new.
     *
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/admin/auth/refresh-token',
        summary: 'Refresh Admin Token',
        description: 'Revoke current token and issue a new one',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Authentication']
    )]
    #[OA\Response(
        response: 200,
        description: 'Token refreshed successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Token refreshed successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: '2|xyz789456...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', example: '2024-12-31T23:59:59.000000Z'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    public function refreshToken(): JsonResponse
    {
        $admin = auth('api_admin')->user();

        if (!$admin) {
            return $this->unauthorized();
        }

        $token = $this->authService->refreshToken(
            $admin,
            'admin-token',
            $this->authService->getAdminAbilities()
        );

        return $this->success([
            'token' => $token['token'],
            'token_type' => 'Bearer',
            'expires_at' => $token['expires_at'],
        ], 'Token refreshed successfully');
    }

    /**
     * Send password reset email.
     *
     * @param ForgotPasswordRequest $request
     * @return JsonResponse
     *
     * @unauthenticated
     */
    #[OA\Post(
        path: '/api/v1/admin/auth/forgot-password',
        summary: 'Admin Forgot Password',
        description: 'Request a password reset link via email. Always returns success to prevent email enumeration.',
        tags: ['Admin Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@aqar.com', description: 'Admin email address'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Password reset link sent (or not found, but same response for security)',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'If an account exists with this email, you will receive a password reset link.'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The email field is required.'),
                new OA\Property(
                    property: 'errors',
                    properties: [
                        new OA\Property(property: 'email', type: 'array', items: new OA\Items(type: 'string', example: 'The email field is required.')),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->getEmail();
        $admin = Admin::where('email', $email)->first();

        // Always return success to prevent email enumeration
        if (!$admin) {
            return $this->success(
                null,
                'If an account exists with this email, you will receive a password reset link.'
            );
        }

        $token = $this->authService->createPasswordResetToken(
            self::PASSWORD_RESET_TABLE,
            $email
        );

        // Build reset URL (frontend should handle this route)
        $resetUrl = config('app.frontend_url', config('app.url'))
            . '/admin/reset-password?token=' . $token . '&email=' . urlencode($email);

        Mail::to($email)->queue(new PasswordResetMail(
            userName: $admin->name,
            resetToken: $token,
            resetUrl: $resetUrl,
        ));

        return $this->success(
            null,
            'If an account exists with this email, you will receive a password reset link.'
        );
    }

    /**
     * Reset password with token.
     *
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     *
     * @unauthenticated
     */
    #[OA\Post(
        path: '/api/v1/admin/auth/reset-password',
        summary: 'Admin Reset Password',
        description: 'Reset admin password using the token received via email',
        tags: ['Admin Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'token', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@aqar.com', description: 'Admin email address'),
                new OA\Property(property: 'token', type: 'string', example: 'abc123def456...', description: 'Password reset token from email'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewSecurePassword123!', description: 'New password (min 8 characters)'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewSecurePassword123!', description: 'Password confirmation'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Password reset successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Password reset successfully. Please login with your new password.'),
                new OA\Property(property: 'data', type: 'null', example: null),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Invalid or expired reset token',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Invalid or expired reset token'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The password confirmation does not match.'),
                new OA\Property(
                    property: 'errors',
                    properties: [
                        new OA\Property(property: 'password', type: 'array', items: new OA\Items(type: 'string', example: 'The password confirmation does not match.')),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $isValid = $this->authService->verifyPasswordResetToken(
            self::PASSWORD_RESET_TABLE,
            $request->getEmail(),
            $request->getToken()
        );

        if (!$isValid) {
            return $this->error('Invalid or expired reset token', 400);
        }

        $this->authService->resetPassword(
            Admin::class,
            $request->getEmail(),
            $request->getPassword()
        );

        // Clean up used token
        \DB::table(self::PASSWORD_RESET_TABLE)
            ->where('email', $request->getEmail())
            ->delete();

        return $this->success(null, 'Password reset successfully. Please login with your new password.');
    }
}

