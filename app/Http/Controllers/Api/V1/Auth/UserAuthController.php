<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SocialLoginRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\TenantResource;
use App\Http\Resources\UserResource;
use App\Mail\EmailVerificationMail;
use App\Mail\PasswordResetMail;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use OpenApi\Attributes as OA;

/**
 * User Authentication Controller
 *
 * Handles authentication for landlord users / tenant owners.
 * Uses the api_user guard with User model.
 *
 * @package App\Http\Controllers\Api\V1\Auth
 */
final class UserAuthController extends BaseApiController
{
    private const PASSWORD_RESET_TABLE = 'password_reset_tokens';

    public function __construct(
        private readonly AuthService $authService,
    ) {}

    /**
     * Register a new user.
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     *
     * @unauthenticated
     */
    #[OA\Post(
        path: '/api/v1/auth/register',
        summary: 'User Registration',
        description: 'Register a new landlord/tenant owner user. Email verification required.',
        tags: ['User Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'email', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'John Doe', description: 'Full name'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com', description: 'Email address'),
                new OA\Property(property: 'username', type: 'string', example: 'johndoe', description: 'Username (optional)', nullable: true),
                new OA\Property(property: 'mobile', type: 'string', example: '+1234567890', description: 'Mobile number (optional)', nullable: true),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'SecurePass123!', description: 'Password (min 8 characters)'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'SecurePass123!', description: 'Password confirmation'),
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
                        new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abcdef123456...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', example: '2024-12-31T23:59:59.000000Z'),
                        new OA\Property(property: 'email_verification_required', type: 'boolean', example: true),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The email has already been taken.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->registerUser($request->validatedData());

        /** @var User $user */
        $user = $result['user'];

        // Send verification email
        Mail::to($user->email)->queue(new EmailVerificationMail(
            userName: $user->name,
            verificationCode: $user->email_verify_token,
        ));

        return $this->created([
            'user' => new UserResource($user),
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'expires_at' => $result['expires_at'],
            'email_verification_required' => true,
        ], 'Registration successful. Please verify your email.');
    }

    /**
     * Authenticate user and return token.
     *
     * @param LoginRequest $request
     * @return JsonResponse
     *
     * @unauthenticated
     */
    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'User Login',
        description: 'Authenticate a landlord/tenant owner user and receive bearer token',
        tags: ['User Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['credential', 'password'],
            properties: [
                new OA\Property(property: 'credential', type: 'string', example: 'user@example.com', description: 'Email or username'),
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
                        new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                        new OA\Property(property: 'token', type: 'string', example: '2|xyz789456...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'email_verified', type: 'boolean', example: true),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Invalid credentials')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->authenticateUser(
                $request->getCredential(),
                $request->getPassword()
            );

            /** @var User $user */
            $user = $result['user'];

            return $this->success([
                'user' => new UserResource($user->load(['tenants.domains', 'latestPaymentLog'])),
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'expires_at' => $result['expires_at'],
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
        path: '/api/v1/auth/logout',
        summary: 'User Logout',
        description: 'Revoke the current user authentication token',
        security: [['sanctum_user' => []]],
        tags: ['User Authentication']
    )]
    #[OA\Response(response: 200, description: 'Logged out successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function logout(): JsonResponse
    {
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $this->authService->logout($user);

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * Get authenticated user profile.
     *
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/auth/me',
        summary: 'Get User Profile',
        description: 'Retrieve the authenticated user profile with tenants and payment info',
        security: [['sanctum_user' => []]],
        tags: ['User Authentication']
    )]
    #[OA\Response(
        response: 200,
        description: 'User profile retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'User profile retrieved'),
                new OA\Property(property: 'data', ref: '#/components/schemas/UserResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function me(): JsonResponse
    {
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        return $this->success(
            new UserResource($user->load(['tenants.domains', 'latestPaymentLog'])),
            'User profile retrieved'
        );
    }

    /**
     * Refresh token - revoke current and issue new.
     *
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/auth/refresh-token',
        summary: 'Refresh User Token',
        description: 'Revoke current token and issue a new one',
        security: [['sanctum_user' => []]],
        tags: ['User Authentication']
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
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function refreshToken(): JsonResponse
    {
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $token = $this->authService->refreshToken(
            $user,
            'user-token',
            $this->authService->getUserAbilities()
        );

        return $this->success([
            'token' => $token['token'],
            'token_type' => 'Bearer',
            'expires_at' => $token['expires_at'],
        ], 'Token refreshed successfully');
    }

    /**
     * Verify email with token.
     *
     * @param VerifyEmailRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/auth/verify-email',
        summary: 'Verify Email',
        description: 'Verify user email address with code sent via email',
        security: [['sanctum_user' => []]],
        tags: ['User Authentication']
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
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        if ($user->email_verified) {
            return $this->success(null, 'Email already verified');
        }

        $verified = $this->authService->verifyEmail(
            User::class,
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
        path: '/api/v1/auth/resend-verification',
        summary: 'Resend Verification Email',
        description: 'Resend email verification code',
        security: [['sanctum_user' => []]],
        tags: ['User Authentication']
    )]
    #[OA\Response(response: 200, description: 'Verification email sent or already verified')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function resendVerification(): JsonResponse
    {
        $user = auth('api_user')->user();

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
        path: '/api/v1/auth/forgot-password',
        summary: 'User Forgot Password',
        description: 'Request a password reset link via email. Always returns success to prevent email enumeration.',
        tags: ['User Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Password reset link sent (or not found, same response for security)')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $email = $request->getEmail();
        $user = User::where('email', $email)->first();

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

        // Build reset URL (frontend should handle this route)
        $resetUrl = config('app.frontend_url', config('app.url'))
            . '/reset-password?token=' . $token . '&email=' . urlencode($email);

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
        path: '/api/v1/auth/reset-password',
        summary: 'User Reset Password',
        description: 'Reset user password using the token received via email',
        tags: ['User Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'token', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                new OA\Property(property: 'token', type: 'string', example: 'abc123def456...', description: 'Password reset token from email'),
                new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewSecurePass123!', description: 'New password'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewSecurePass123!'),
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Password reset successfully')]
    #[OA\Response(response: 400, description: 'Invalid or expired reset token')]
    #[OA\Response(response: 422, description: 'Validation error')]
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
            User::class,
            $request->getEmail(),
            $request->getPassword()
        );

        // Clean up used token
        \DB::table(self::PASSWORD_RESET_TABLE)
            ->where('email', $request->getEmail())
            ->delete();

        return $this->success(null, 'Password reset successfully. Please login with your new password.');
    }

    /**
     * Social login (Google/Facebook).
     *
     * @param SocialLoginRequest $request
     * @return JsonResponse
     *
     * @unauthenticated
     */
    #[OA\Post(
        path: '/api/v1/auth/social-login',
        summary: 'Social Login',
        description: 'Login or register using social providers (Google/Facebook). Requires OAuth2 access token from provider.',
        tags: ['User Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['provider', 'access_token'],
            properties: [
                new OA\Property(property: 'provider', type: 'string', enum: ['google', 'facebook'], example: 'google', description: 'Social provider name'),
                new OA\Property(property: 'access_token', type: 'string', example: 'ya29.a0AfH6SM...', description: 'OAuth2 access token from provider'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Login/registration successful',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Login successful'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
                        new OA\Property(property: 'is_new_user', type: 'boolean', example: false, description: 'Whether this is a new registration'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 400, description: 'Unable to retrieve email from provider')]
    #[OA\Response(response: 401, description: 'Social authentication failed')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function socialLogin(SocialLoginRequest $request): JsonResponse
    {
        $provider = $request->getProvider();
        $accessToken = $request->getAccessToken();

        try {
            // Get user info from social provider
            $socialUser = Socialite::driver($provider)
                ->stateless()
                ->userFromToken($accessToken);

            if (!$socialUser->getEmail()) {
                return $this->error('Unable to retrieve email from ' . ucfirst($provider), 400);
            }

            $result = $this->authService->socialLogin($provider, [
                'id' => $socialUser->getId(),
                'name' => $socialUser->getName() ?? explode('@', $socialUser->getEmail())[0],
                'email' => $socialUser->getEmail(),
            ]);

            /** @var User $user */
            $user = $result['user'];

            return $this->success([
                'user' => new UserResource($user->load(['tenants.domains', 'latestPaymentLog'])),
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'expires_at' => $result['expires_at'],
                'is_new_user' => $result['is_new'],
            ], $result['is_new'] ? 'Account created successfully' : 'Login successful');
        } catch (\Exception $e) {
            return $this->error('Social authentication failed: ' . $e->getMessage(), 401);
        }
    }
}

