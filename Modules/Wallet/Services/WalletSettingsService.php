<?php

declare(strict_types=1);

namespace Modules\Wallet\Services;

use Modules\Wallet\Entities\WalletSettings;

class WalletSettingsService
{
    /**
     * Get wallet settings by user ID
     */
    public function getSettingsByUserId(int $userId): ?WalletSettings
    {
        return WalletSettings::where('user_id', $userId)->first();
    }

    /**
     * Get or create wallet settings for user
     */
    public function getOrCreateSettings(int $userId): WalletSettings
    {
        return WalletSettings::firstOrCreate(
            ['user_id' => $userId],
            [
                'renew_package' => 0,
                'wallet_alert' => 0,
                'minimum_amount' => 10,
            ]
        );
    }

    /**
     * Update wallet settings
     */
    public function updateSettings(int $userId, array $data): bool
    {
        $settings = $this->getOrCreateSettings($userId);
        
        return $settings->update([
            'renew_package' => $data['renew_package'] ?? $settings->renew_package,
            'wallet_alert' => $data['wallet_alert'] ?? $settings->wallet_alert,
            'minimum_amount' => $data['minimum_amount'] ?? $settings->minimum_amount,
        ]);
    }

    /**
     * Toggle auto renew package
     */
    public function toggleAutoRenew(int $userId): bool
    {
        $settings = $this->getOrCreateSettings($userId);
        $settings->renew_package = !$settings->renew_package;
        
        return $settings->save();
    }

    /**
     * Toggle wallet alert
     */
    public function toggleWalletAlert(int $userId): bool
    {
        $settings = $this->getOrCreateSettings($userId);
        $settings->wallet_alert = !$settings->wallet_alert;
        
        return $settings->save();
    }

    /**
     * Update minimum amount threshold
     */
    public function updateMinimumAmount(int $userId, float $amount): bool
    {
        $settings = $this->getOrCreateSettings($userId);
        $settings->minimum_amount = $amount;
        
        return $settings->save();
    }

    /**
     * Delete wallet settings
     */
    public function deleteSettings(int $userId): bool
    {
        $settings = WalletSettings::where('user_id', $userId)->first();
        if (!$settings) {
            return false;
        }

        return $settings->delete();
    }
}
