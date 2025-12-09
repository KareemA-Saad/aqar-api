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

