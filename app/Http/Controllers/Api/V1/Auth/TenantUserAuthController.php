<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\TenantRegisterRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\TenantUserResource;
use App\Mail\EmailVerificationMail;
use App\Mail\PasswordResetMail;
use App\Models\TenantUser;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use OpenApi\Attributes as OA;

/**
 * Tenant User Authentication Controller
 *
 * Handles authentication for end-users within a tenant context.
 * Uses the api_tenant_user guard with TenantUser model.
 *
 * IMPORTANT: This controller requires tenant context to be initialized
 * via the ResolveTenantFromToken middleware or tenant identification.
 *
 * @package App\Http\Controllers\Api\V1\Auth
 */
final class TenantUserAuthController extends BaseApiController
{
    /**
     * Password reset table in tenant database.
     */
    private const PASSWORD_RESET_TABLE = 'password_reset_tokens';

    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Get current tenant ID from request.
     * Tenant should be resolved by middleware.
     *
     * @param Request $request
     * @return string|null
     */
    private function getTenantId(Request $request): ?string
    {
        // Try to get from route parameter first
        $tenantId = $request->route('tenant');

        // Or from header
        if (!$tenantId) {
            $tenantId = $request->header('X-Tenant-ID');
        }

        // Or from subdomain (if applicable)
        if (!$tenantId) {
            $host = $request->getHost();
            $parts = explode('.', $host);
            if (count($parts) > 2) {
                $tenantId = $parts[0];
            }
        }

        return $tenantId;
    }

    /**
     * Register a new tenant user.
     *
     * @param TenantRegisterRequest $request
     * @return JsonResponse
     *
     * @unauthenticated
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/auth/register',
        summary: 'Tenant User Registration',
        description: 'Register a new end-user within a tenant. Requires tenant context via route parameter.',
        tags: ['Tenant User Authentication']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID (subdomain)',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'email', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Jane Smith'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@acme-corp.com'),
                new OA\Property(property: 'username', type: 'string', example: 'janesmith', nullable: true),
                new OA\Property(property: 'mobile', type: 'string', example: '+1234567890', nullable: true),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'SecurePass123!'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'SecurePass123!'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Registration successful',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Registration successful. Please verify your email.'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/TenantUserResource'),
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'tenant_id', type: 'string', example: 'acme-corp'),
                        new OA\Property(property: 'email_verification_required', type: 'boolean', example: true),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Tenant context required')]
    #[OA\Response(response: 422, description: 'Validation error (email already registered)')]
    public function register(TenantRegisterRequest $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request);

        if (!$tenantId) {
            return $this->error('Tenant context required', 400);
        }

        // Check if email already exists in tenant
        $existingUser = TenantUser::where('email', $request->input('email'))->first();
        if ($existingUser) {
            return $this->validationError(
                ['email' => ['This email is already registered.']],
                'Validation failed'
            );
        }

        $result = $this->authService->registerTenantUser(
            $request->validatedData(),
            $tenantId
        );

        /** @var TenantUser $user */
        $user = $result['user'];

        // Send verification email
        Mail::to($user->email)->queue(new EmailVerificationMail(
            userName: $user->name,
            verificationCode: $user->email_verify_token,
        ));

        return $this->created([
            'user' => new TenantUserResource($user),
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'expires_at' => $result['expires_at'],
            'tenant_id' => $result['tenant_id'],
            'email_verification_required' => true,
        ], 'Registration successful. Please verify your email.');
    }

    /**
     * Authenticate tenant user and return token.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     *
     * @unauthenticated
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/auth/login',
        summary: 'Tenant User Login',
        description: 'Authenticate a tenant end-user and receive bearer token',
        tags: ['Tenant User Authentication']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID (subdomain)',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['credential', 'password'],
            properties: [
                new OA\Property(property: 'credential', type: 'string', example: 'jane@acme-corp.com', description: 'Email or username'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'SecurePass123!'),
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
                        new OA\Property(property: 'user', ref: '#/components/schemas/TenantUserResource'),
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'tenant_id', type: 'string', example: 'acme-corp'),
                        new OA\Property(property: 'email_verified', type: 'boolean', example: true),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Tenant context required')]
    #[OA\Response(response: 401, description: 'Invalid credentials')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function login(LoginRequest $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request);

        if (!$tenantId) {
            return $this->error('Tenant context required', 400);
        }

        try {
            $result = $this->authService->authenticateTenantUser(
                $request->getCredential(),
                $request->getPassword(),
                $tenantId
            );

            /** @var TenantUser $user */
            $user = $result['user'];

            return $this->success([
                'user' => new TenantUserResource($user),
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'expires_at' => $result['expires_at'],
                'tenant_id' => $result['tenant_id'],
                'email_verified' => $user->email_verified,
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
        path: '/api/v1/tenant/{tenant}/auth/logout',
        summary: 'Tenant User Logout',
        description: 'Revoke the current tenant user authentication token',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant User Authentication']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID (subdomain)',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Response(response: 200, description: 'Logged out successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function logout(): JsonResponse
    {
        $user = auth('api_tenant_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $this->authService->logout($user);

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Get authenticated tenant user profile.
     *
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/auth/me',
        summary: 'Get Tenant User Profile',
        description: 'Retrieve the authenticated tenant user profile',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant User Authentication']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID (subdomain)',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Response(
        response: 200,
        description: 'User profile retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'User profile retrieved'),
                new OA\Property(property: 'data', ref: '#/components/schemas/TenantUserResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function me(): JsonResponse
    {
        $user = auth('api_tenant_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        return $this->success(
            new TenantUserResource($user),
            'User profile retrieved'
        );
    }

    /**
     * Refresh token - revoke current and issue new.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/auth/refresh-token',
        summary: 'Refresh Tenant User Token',
        description: 'Revoke current token and issue a new one',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant User Authentication']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID (subdomain)',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
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
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'tenant_id', type: 'string', example: 'acme-corp'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Tenant context required')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function refreshToken(Request $request): JsonResponse
    {
        $user = auth('api_tenant_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $tenantId = $this->getTenantId($request);

        if (!$tenantId) {
            // Try to extract from current token
            /** @var \Laravel\Sanctum\PersonalAccessToken|null $currentToken */
            $currentToken = $user->currentAccessToken();
            if ($currentToken && str_starts_with($currentToken->name, 'tenant-')) {
                // Extract tenant ID from token name: tenant-{id}-token
                preg_match('/tenant-(.+)-token/', $currentToken->name, $matches);
                $tenantId = $matches[1] ?? null;
            }
        }

        if (!$tenantId) {
            return $this->error('Tenant context required', 400);
        }

        $tokenName = "tenant-{$tenantId}-token";
        $abilities = $this->authService->getTenantUserAbilities($tenantId);

        $token = $this->authService->refreshToken($user, $tokenName, $abilities);

        return $this->success([
            'token' => $token['token'],
            'token_type' => 'Bearer',
            'expires_at' => $token['expires_at'],
            'tenant_id' => $tenantId,
        ], 'Token refreshed successfully');
    }

    /**
     * Verify email with token.
     *
     * @param VerifyEmailRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/auth/verify-email',
        summary: 'Verify Tenant User Email',
        description: 'Verify email address with code sent via email',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant User Authentication']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID (subdomain)',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['token'],
            properties: [
                new OA\Property(property: 'token', type: 'string', example: '123456', description: 'Verification code from email'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Email verified successfully or already verified')]
    #[OA\Response(response: 400, description: 'Invalid verification code')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = auth('api_tenant_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        if ($user->email_verified) {
            return $this->success(null, 'Email already verified');
        }

        $verified = $this->authService->verifyEmail(
            TenantUser::class,
            $user->id,
            $request->getToken()
        );

        if (!$verified) {
            return $this->error('Invalid verification code', 400);
        }

        return $this->success(null, 'Email verified successfully');
    }

    /**
     * Resend email verification.
     *
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/auth/resend-verification',
        summary: 'Resend Tenant User Verification Email',
        description: 'Resend email verification code',
        security: [['sanctum_tenant_user' => []]],
        tags: ['Tenant User Authentication']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID (subdomain)',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Response(response: 200, description: 'Verification email sent or already verified')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function resendVerification(): JsonResponse
    {
        $user = auth('api_tenant_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        if ($user->email_verified) {
            return $this->success(null, 'Email already verified');
        }

        $token = $this->authService->regenerateVerificationToken($user);

        Mail::to($user->email)->queue(new EmailVerificationMail(
            userName: $user->name,
            verificationCode: $token,
        ));

        return $this->success(null, 'Verification email sent');
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
        path: '/api/v1/tenant/{tenant}/auth/forgot-password',
        summary: 'Tenant User Forgot Password',
        description: 'Request a password reset link via email. Always returns success to prevent email enumeration.',
        tags: ['Tenant User Authentication']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID (subdomain)',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@acme-corp.com'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Password reset link sent (or not found, same response for security)')]
    #[OA\Response(response: 400, description: 'Tenant context required')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request);

        if (!$tenantId) {
            return $this->error('Tenant context required', 400);
        }

        $email = $request->getEmail();
        $user = TenantUser::where('email', $email)->first();

        // Always return success to prevent email enumeration
        if (!$user) {
            return $this->success(
                null,
                'If an account exists with this email, you will receive a password reset link.'
            );
        }

        $token = $this->authService->createPasswordResetToken(
            self::PASSWORD_RESET_TABLE,
            $email
        );

        // Build reset URL with tenant context
        $resetUrl = config('app.frontend_url', config('app.url'))
            . "/tenant/{$tenantId}/reset-password?token=" . $token . '&email=' . urlencode($email);

        Mail::to($email)->queue(new PasswordResetMail(
            userName: $user->name,
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
        path: '/api/v1/tenant/{tenant}/auth/reset-password',
        summary: 'Tenant User Reset Password',
        description: 'Reset tenant user password using the token received via email',
        tags: ['Tenant User Authentication']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID (subdomain)',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'token', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@acme-corp.com'),
                new OA\Property(property: 'token', type: 'string', example: 'abc123def456...', description: 'Password reset token from email'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewSecurePass123!'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewSecurePass123!'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Password reset successfully')]
    #[OA\Response(response: 400, description: 'Invalid or expired reset token or tenant context required')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $tenantId = $this->getTenantId($request);

        if (!$tenantId) {
            return $this->error('Tenant context required', 400);
        }

        $isValid = $this->authService->verifyPasswordResetToken(
            self::PASSWORD_RESET_TABLE,
            $request->getEmail(),
            $request->getToken()
        );

        if (!$isValid) {
            return $this->error('Invalid or expired reset token', 400);
        }

        $this->authService->resetPassword(
            TenantUser::class,
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

