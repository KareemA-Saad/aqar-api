<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TrustedDevice;
use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * TwoFactorAuthService handles all 2FA business logic.
 *
 * Responsibilities:
 * - Secret key generation
 * - QR code generation
 * - OTP verification
 * - Trusted device management
 * - 2FA token generation for login flow
 */
final class TwoFactorAuthService
{
    /**
     * Two-factor authentication token prefix.
     */
    private const TWO_FACTOR_TOKEN_PREFIX = '2fa_';

    /**
     * Two-factor token expiration in minutes.
     */
    private const TWO_FACTOR_TOKEN_EXPIRATION = 5;

    /**
     * Trusted device expiration in days.
     */
    private const TRUSTED_DEVICE_EXPIRATION_DAYS = 30;

    /**
     * Maximum OTP verification attempts before lockout.
     */
    private const MAX_OTP_ATTEMPTS = 5;

    /**
     * OTP lockout duration in minutes.
     */
    private const OTP_LOCKOUT_MINUTES = 15;

    /**
     * App name for QR code display.
     */
    private string $appName;

    public function __construct(
        private readonly Google2FA $google2FA,
    ) {
        $this->appName = config('app.name', 'AQAR');
    }

    /**
     * Generate a new 2FA secret key for setup.
     *
     * @return string The generated secret key
     */
    public function generateSecretKey(): string
    {
        return $this->google2FA->generateSecretKey();
    }

    /**
     * Generate QR code data URL for the authenticator app.
     *
     * @param User $user
     * @param string $secret
     * @return string SVG data URL
     */
    public function generateQrCodeDataUrl(User $user, string $secret): string
    {
        $qrCodeUrl = $this->google2FA->getQRCodeUrl(
            $this->appName,
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);
        $svg = $writer->writeString($qrCodeUrl);

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Verify the OTP code against the user's secret.
     *
     * @param string $secret The user's 2FA secret
     * @param string $code The OTP code to verify
     * @return bool
     */
    public function verifyCode(string $secret, string $code): bool
    {
        return $this->google2FA->verifyKey($secret, $code);
    }

    /**
     * Verify OTP code for a specific user during login.
     * Includes rate limiting to prevent brute force attacks.
     *
     * @param User $user
     * @param string $code
     * @return array{success: bool, message: string}
     */
    public function verifyUserCode(User $user, string $code): array
    {
        $cacheKey = "2fa_attempts_{$user->id}";

        // Check if user is locked out
        $attempts = Cache::get($cacheKey, 0);
        if ($attempts >= self::MAX_OTP_ATTEMPTS) {
            $lockoutKey = "2fa_lockout_{$user->id}";
            if (Cache::has($lockoutKey)) {
                return [
                    'success' => false,
                    'message' => 'Too many failed attempts. Please try again later.',
                ];
            }
        }

        // Verify the code
        $isValid = $this->verifyCode($user->google2fa_secret, $code);

        if (!$isValid) {
            // Increment failed attempts
            $newAttempts = $attempts + 1;
            Cache::put($cacheKey, $newAttempts, now()->addMinutes(self::OTP_LOCKOUT_MINUTES));

            if ($newAttempts >= self::MAX_OTP_ATTEMPTS) {
                Cache::put("2fa_lockout_{$user->id}", true, now()->addMinutes(self::OTP_LOCKOUT_MINUTES));

                Log::warning('2FA lockout triggered', ['user_id' => $user->id]);

                return [
                    'success' => false,
                    'message' => 'Too many failed attempts. Please try again in ' . self::OTP_LOCKOUT_MINUTES . ' minutes.',
                ];
            }

            return [
                'success' => false,
                'message' => 'Invalid verification code.',
            ];
        }

        // Clear failed attempts on success
        Cache::forget($cacheKey);
        Cache::forget("2fa_lockout_{$user->id}");

        return [
            'success' => true,
            'message' => 'Code verified successfully.',
        ];
    }

    /**
     * Store temporary secret for 2FA setup process.
     *
     * @param User $user
     * @param string $secret
     * @return void
     */
    public function storeTemporarySecret(User $user, string $secret): void
    {
        Cache::put(
            "2fa_setup_{$user->id}",
            $secret,
            now()->addMinutes(15) // 15 minutes to complete setup
        );
    }

    /**
     * Get temporary secret for 2FA setup process.
     *
     * @param User $user
     * @return string|null
     */
    public function getTemporarySecret(User $user): ?string
    {
        return Cache::get("2fa_setup_{$user->id}");
    }

    /**
     * Clear temporary secret after setup.
     *
     * @param User $user
     * @return void
     */
    public function clearTemporarySecret(User $user): void
    {
        Cache::forget("2fa_setup_{$user->id}");
    }

    /**
     * Generate a temporary 2FA token for the login flow.
     * This token is returned when credentials are valid but 2FA is required.
     *
     * @param User $user
     * @return string
     */
    public function generateTwoFactorToken(User $user): string
    {
        $token = self::TWO_FACTOR_TOKEN_PREFIX . Str::random(64);

        Cache::put(
            $this->getTwoFactorTokenCacheKey($token),
            $user->id,
            now()->addMinutes(self::TWO_FACTOR_TOKEN_EXPIRATION)
        );

        return $token;
    }

    /**
     * Validate a 2FA token and return the associated user.
     *
     * @param string $token
     * @return User|null
     */
    public function validateTwoFactorToken(string $token): ?User
    {
        $cacheKey = $this->getTwoFactorTokenCacheKey($token);
        $userId = Cache::get($cacheKey);

        if (!$userId) {
            return null;
        }

        return User::find($userId);
    }

    /**
     * Consume (invalidate) a 2FA token after successful verification.
     *
     * @param string $token
     * @return void
     */
    public function consumeTwoFactorToken(string $token): void
    {
        Cache::forget($this->getTwoFactorTokenCacheKey($token));
    }

    /**
     * Create a trusted device for "Remember this device" feature.
     *
     * @param User $user
     * @param string|null $userAgent
     * @param string|null $ipAddress
     * @return string The device token to store in client
     */
    public function createTrustedDevice(User $user, ?string $userAgent = null, ?string $ipAddress = null): string
    {
        $deviceToken = Str::random(64);
        $deviceName = $this->parseDeviceName($userAgent);

        TrustedDevice::create([
            'user_id' => $user->id,
            'device_token' => $deviceToken,
            'device_name' => $deviceName,
            'user_agent' => $userAgent ? Str::limit($userAgent, 500) : null,
            'ip_address' => $ipAddress,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(self::TRUSTED_DEVICE_EXPIRATION_DAYS),
        ]);

        Log::info('Trusted device created', [
            'user_id' => $user->id,
            'device_name' => $deviceName,
        ]);

        return $deviceToken;
    }

    /**
     * Verify if a device token is trusted for a user.
     *
     * @param User $user
     * @param string $deviceToken
     * @return bool
     */
    public function isTrustedDevice(User $user, string $deviceToken): bool
    {
        $device = TrustedDevice::where('user_id', $user->id)
            ->where('device_token', $deviceToken)
            ->valid()
            ->first();

        if ($device) {
            // Update last used timestamp
            $device->touchLastUsed();
            return true;
        }

        return false;
    }

    /**
     * Revoke a specific trusted device.
     *
     * @param User $user
     * @param int $deviceId
     * @return bool
     */
    public function revokeTrustedDevice(User $user, int $deviceId): bool
    {
        $deleted = TrustedDevice::where('user_id', $user->id)
            ->where('id', $deviceId)
            ->delete();

        if ($deleted) {
            Log::info('Trusted device revoked', [
                'user_id' => $user->id,
                'device_id' => $deviceId,
            ]);
        }

        return $deleted > 0;
    }

    /**
     * Revoke all trusted devices for a user.
     *
     * @param User $user
     * @return int Number of devices revoked
     */
    public function revokeAllTrustedDevices(User $user): int
    {
        $count = $user->trustedDevices()->delete();

        Log::info('All trusted devices revoked', [
            'user_id' => $user->id,
            'count' => $count,
        ]);

        return $count;
    }

    /**
     * Get all trusted devices for a user.
     *
     * @param User $user
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrustedDevices(User $user)
    {
        return $user->trustedDevices()
            ->valid()
            ->orderByDesc('last_used_at')
            ->get();
    }

    /**
     * Clean up expired trusted devices.
     *
     * @return int Number of devices deleted
     */
    public function cleanupExpiredDevices(): int
    {
        return TrustedDevice::expired()->delete();
    }

    /**
     * Parse a human-readable device name from user agent.
     *
     * @param string|null $userAgent
     * @return string
     */
    private function parseDeviceName(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Unknown Device';
        }

        // Simple browser/device detection
        $browser = 'Unknown Browser';
        $os = 'Unknown OS';

        // Detect browser
        if (str_contains($userAgent, 'Chrome') && !str_contains($userAgent, 'Edg')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari') && !str_contains($userAgent, 'Chrome')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Edg')) {
            $browser = 'Edge';
        } elseif (str_contains($userAgent, 'Opera') || str_contains($userAgent, 'OPR')) {
            $browser = 'Opera';
        }

        // Detect OS
        if (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Mac OS')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            $os = 'iOS';
        }

        return "{$browser} on {$os}";
    }

    /**
     * Get cache key for 2FA token.
     *
     * @param string $token
     * @return string
     */
    private function getTwoFactorTokenCacheKey(string $token): string
    {
        return 'two_factor_token_' . hash('sha256', $token);
    }
}
