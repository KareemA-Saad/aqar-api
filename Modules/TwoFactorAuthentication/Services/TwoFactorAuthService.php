<?php

declare(strict_types=1);

namespace Modules\TwoFactorAuthentication\Services;

use Modules\TwoFactorAuthentication\Entities\LoginSecurity;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Hash;

class TwoFactorAuthService
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Get user's 2FA settings
     */
    public function getUserSettings(int $userId): ?LoginSecurity
    {
        return LoginSecurity::where('user_id', $userId)->first();
    }

    /**
     * Get or create user's 2FA settings
     */
    public function getOrCreateSettings(int $userId): LoginSecurity
    {
        return LoginSecurity::firstOrCreate(
            ['user_id' => $userId],
            [
                'google2fa_enable' => false,
                'google2fa_secret' => null,
            ]
        );
    }

    /**
     * Generate new secret key for user
     */
    public function generateSecretKey(int $userId): array
    {
        $secret = $this->google2fa->generateSecretKey();
        
        $settings = $this->getOrCreateSettings($userId);
        $settings->update([
            'google2fa_secret' => $secret,
        ]);

        return [
            'secret' => $secret,
            'qr_code_url' => $this->getQRCodeUrl($userId, $secret),
        ];
    }

    /**
     * Get QR code URL for Google Authenticator
     */
    public function getQRCodeUrl(int $userId, ?string $secret = null): string
    {
        if (!$secret) {
            $settings = $this->getUserSettings($userId);
            $secret = $settings?->google2fa_secret;
        }

        if (!$secret) {
            throw new \Exception('No secret key found for user');
        }

        $user = \App\Models\User::find($userId);
        $companyName = config('app.name', 'Laravel');
        
        return $this->google2fa->getQRCodeUrl(
            $companyName,
            $user->email,
            $secret
        );
    }

    /**
     * Enable 2FA for user after verifying code
     */
    public function enableTwoFactor(int $userId, string $code): bool
    {
        $settings = $this->getUserSettings($userId);
        
        if (!$settings || !$settings->google2fa_secret) {
            return false;
        }

        // Verify the code first
        if (!$this->verifyCode($userId, $code)) {
            return false;
        }

        return $settings->update(['google2fa_enable' => true]);
    }

    /**
     * Disable 2FA for user
     */
    public function disableTwoFactor(int $userId, string $password): bool
    {
        $user = \App\Models\User::find($userId);
        
        if (!$user || !Hash::check($password, $user->password)) {
            return false;
        }

        $settings = $this->getUserSettings($userId);
        if (!$settings) {
            return false;
        }

        return $settings->update([
            'google2fa_enable' => false,
            'google2fa_secret' => null,
        ]);
    }

    /**
     * Verify 2FA code
     */
    public function verifyCode(int $userId, string $code): bool
    {
        $settings = $this->getUserSettings($userId);
        
        if (!$settings || !$settings->google2fa_secret) {
            return false;
        }

        return $this->google2fa->verifyKey($settings->google2fa_secret, $code);
    }

    /**
     * Check if user has 2FA enabled
     */
    public function isTwoFactorEnabled(int $userId): bool
    {
        $settings = $this->getUserSettings($userId);
        return $settings ? (bool) $settings->google2fa_enable : false;
    }

    /**
     * Get all users with 2FA enabled (admin only)
     */
    public function getUsersWithTwoFactorEnabled()
    {
        return LoginSecurity::with('user')
            ->where('google2fa_enable', true)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    /**
     * Get 2FA statistics
     */
    public function getStatistics(): array
    {
        $total = LoginSecurity::count();
        $enabled = LoginSecurity::where('google2fa_enable', true)->count();
        
        return [
            'total_users_with_2fa_setup' => $total,
            'users_with_2fa_enabled' => $enabled,
            'users_with_2fa_disabled' => $total - $enabled,
            'percentage_enabled' => $total > 0 ? round(($enabled / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Admin force disable 2FA for user
     */
    public function adminDisableTwoFactor(int $userId): bool
    {
        $settings = $this->getUserSettings($userId);
        if (!$settings) {
            return false;
        }

        return $settings->update([
            'google2fa_enable' => false,
            'google2fa_secret' => null,
        ]);
    }
}
