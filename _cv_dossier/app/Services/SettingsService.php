<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\StaticOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Settings Service
 *
 * Handles all settings-related business logic including:
 * - Get/Set individual settings
 * - Bulk get/set operations
 * - Settings groups management
 * - Cache management
 */
final class SettingsService
{
    /**
     * Cache TTL in seconds (24 hours).
     */
    private const CACHE_TTL = 86400;

    /**
     * Cache key prefix for settings.
     */
    private const CACHE_PREFIX = 'static_option_';

    /**
     * Settings groups configuration with their keys.
     */
    public const SETTINGS_GROUPS = [
        'general' => [
            'site_title',
            'site_tag_line',
            'site_logo',
            'site_white_logo',
            'site_favicon',
            'breadcrumb_left_image',
            'breadcrumb_right_image',
            'site_breadcrumb_image',
            'timezone',
            'date_display_style',
            'maintenance_mode',
            'dark_mode_for_admin_panel',
            'backend_preloader',
            'language_selector_status',
            'mouse_cursor_effect_status',
            'section_title_extra_design_status',
            'site_force_ssl_redirection',
            'table_list_data_orderable_status',
        ],
        'email' => [
            'site_global_email',
            'site_smtp_host',
            'site_smtp_username',
            'site_smtp_password',
            'site_smtp_port',
            'site_smtp_encryption',
            'site_smtp_driver',
            'mail_from_name',
            'tenant_site_global_email',
        ],
        'seo' => [
            'site_meta_title',
            'site_meta_tags',
            'site_meta_keywords',
            'site_meta_description',
            'site_og_meta_title',
            'site_og_meta_description',
            'site_og_meta_image',
            'site_canonical_settings',
        ],
        'payment' => [
            'site_global_currency',
            'site_global_payment_gateway',
            'site_currency_symbol_position',
            'site_default_payment_gateway',
            'currency_amount_type_status',
            'site_custom_currency_symbol',
            'coupon_apply_status',
            'site_usd_to_ngn_exchange_rate',
            'site_euro_to_ngn_exchange_rate',
        ],
        'tenant' => [
            'default_theme',
            'auto_create_database',
            'trial_days',
            'tenant_email_verification_status',
            'user_email_verify_status',
            'guest_order_system_status',
        ],
        'appearance' => [
            'main_color_one',
            'main_color_one_rgb',
            'main_color_two',
            'main_color_two_rba',
            'main_color_three',
            'heading_color',
            'heading_color_rgb',
            'secondary_color',
            'bg_light_one',
            'bg_light_two',
            'bg_dark_one',
            'bg_dark_two',
            'paragraph_color',
            'paragraph_color_two',
            'paragraph_color_three',
            'paragraph_color_four',
            'global_navbar_variant',
            'global_footer_variant',
        ],
        'typography' => [
            'body_font_family',
            'body_font_variant',
            'heading_font',
            'heading_font_family',
            'heading_font_variant',
            'custom_font',
            'custom_heading_font',
            'custom_body_font',
        ],
        'third_party' => [
            'site_google_analytics',
            'site_disqus_key',
            'tawk_api_key',
            'site_third_party_tracking_code',
            'site_google_captcha_v3_site_key',
            'site_google_captcha_v3_secret_key',
            'google_client_id',
            'google_client_secret',
            'facebook_client_id',
            'facebook_client_secret',
            'social_facebook_status',
            'social_google_status',
            'google_adsense_publisher_id',
            'google_adsense_customer_id',
            'site_third_party_tracking_code_just_after_head',
            'site_third_party_tracking_code_just_after_body',
            'site_third_party_tracking_code_just_before_body_close',
        ],
        'gdpr' => [
            'site_gdpr_cookie_enabled',
            'site_gdpr_cookie_expire',
            'site_gdpr_cookie_delay',
        ],
        'pages' => [
            'home_page',
            'shop_page',
            'pricing_plan',
            'job_page',
            'donation_page',
            'event_page',
            'knowledgebase_page',
            'terms_condition_page',
            'privacy_policy_page',
        ],
    ];

    /**
     * Sensitive keys that should be masked in responses.
     */
    private const SENSITIVE_KEYS = [
        'site_smtp_password',
        'google_client_secret',
        'facebook_client_secret',
        'site_google_captcha_v3_secret_key',
    ];

    /**
     * Get a single setting value.
     *
     * @param string $key Setting key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            self::CACHE_PREFIX . $key,
            self::CACHE_TTL,
            function () use ($key, $default) {
                $option = StaticOption::where('option_name', $key)->first();
                return $option?->option_value ?? $default;
            }
        );
    }

    /**
     * Set a single setting value.
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @return bool
     */
    public function set(string $key, mixed $value): bool
    {
        try {
            StaticOption::updateOrCreate(
                ['option_name' => $key],
                ['option_value' => $value]
            );

            // Clear cache for this key
            Cache::forget(self::CACHE_PREFIX . $key);

            Log::info('Setting updated', ['key' => $key]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update setting', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get multiple settings by keys.
     *
     * @param array<string> $keys Array of setting keys
     * @param bool $maskSensitive Whether to mask sensitive values
     * @return array<string, mixed>
     */
    public function getBulk(array $keys, bool $maskSensitive = true): array
    {
        $settings = [];

        foreach ($keys as $key) {
            $value = $this->get($key);

            if ($maskSensitive && in_array($key, self::SENSITIVE_KEYS, true) && $value !== null) {
                $value = '********';
            }

            $settings[$key] = $value;
        }

        return $settings;
    }

    /**
     * Set multiple settings at once.
     *
     * @param array<string, mixed> $settings Key-value pairs of settings
     * @return bool
     */
    public function setBulk(array $settings): bool
    {
        try {
            foreach ($settings as $key => $value) {
                $this->set($key, $value);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update bulk settings', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get all settings for a specific group.
     *
     * @param string $group Group name
     * @param bool $maskSensitive Whether to mask sensitive values
     * @return array<string, mixed>
     */
    public function getByGroup(string $group, bool $maskSensitive = true): array
    {
        if (!isset(self::SETTINGS_GROUPS[$group])) {
            return [];
        }

        return $this->getBulk(self::SETTINGS_GROUPS[$group], $maskSensitive);
    }

    /**
     * Set all settings for a specific group.
     *
     * @param string $group Group name
     * @param array<string, mixed> $settings Key-value pairs of settings
     * @return bool
     */
    public function setByGroup(string $group, array $settings): bool
    {
        if (!isset(self::SETTINGS_GROUPS[$group])) {
            return false;
        }

        // Only allow keys that belong to this group
        $allowedKeys = self::SETTINGS_GROUPS[$group];
        $filteredSettings = array_filter(
            $settings,
            fn ($key) => in_array($key, $allowedKeys, true),
            ARRAY_FILTER_USE_KEY
        );

        return $this->setBulk($filteredSettings);
    }

    /**
     * Get all available settings groups.
     *
     * @return array<string, array<string>>
     */
    public function getGroups(): array
    {
        return self::SETTINGS_GROUPS;
    }

    /**
     * Get all settings organized by groups.
     *
     * @param bool $maskSensitive Whether to mask sensitive values
     * @return array<string, array<string, mixed>>
     */
    public function getAllGrouped(bool $maskSensitive = true): array
    {
        $result = [];

        foreach (array_keys(self::SETTINGS_GROUPS) as $group) {
            $result[$group] = $this->getByGroup($group, $maskSensitive);
        }

        return $result;
    }

    /**
     * Clear all settings cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        // Get all settings keys
        $allKeys = array_merge(...array_values(self::SETTINGS_GROUPS));

        foreach ($allKeys as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        }

        // Also clear any additional cached options
        $options = StaticOption::pluck('option_name')->toArray();
        foreach ($options as $key) {
            Cache::forget(self::CACHE_PREFIX . $key);
            Cache::forget($key); // Legacy cache key format
        }

        Log::info('Settings cache cleared');
    }

    /**
     * Clear cache for a specific key.
     *
     * @param string $key Setting key
     * @return void
     */
    public function clearCacheForKey(string $key): void
    {
        Cache::forget(self::CACHE_PREFIX . $key);
        Cache::forget($key); // Legacy cache key format
    }

    /**
     * Check if a key is sensitive.
     *
     * @param string $key Setting key
     * @return bool
     */
    public function isSensitiveKey(string $key): bool
    {
        return in_array($key, self::SENSITIVE_KEYS, true);
    }

    /**
     * Get the group name for a setting key.
     *
     * @param string $key Setting key
     * @return string|null
     */
    public function getGroupForKey(string $key): ?string
    {
        foreach (self::SETTINGS_GROUPS as $group => $keys) {
            if (in_array($key, $keys, true)) {
                return $group;
            }
        }

        return null;
    }

    /**
     * Validate if keys belong to a specific group.
     *
     * @param string $group Group name
     * @param array<string> $keys Keys to validate
     * @return array{valid: array<string>, invalid: array<string>}
     */
    public function validateKeysForGroup(string $group, array $keys): array
    {
        if (!isset(self::SETTINGS_GROUPS[$group])) {
            return ['valid' => [], 'invalid' => $keys];
        }

        $groupKeys = self::SETTINGS_GROUPS[$group];
        $valid = [];
        $invalid = [];

        foreach ($keys as $key) {
            if (in_array($key, $groupKeys, true)) {
                $valid[] = $key;
            } else {
                $invalid[] = $key;
            }
        }

        return ['valid' => $valid, 'invalid' => $invalid];
    }

    /**
     * Get SMTP configuration (with password masked).
     *
     * @return array<string, mixed>
     */
    public function getSmtpConfig(): array
    {
        return [
            'driver' => $this->get('site_smtp_driver', 'smtp'),
            'host' => $this->get('site_smtp_host'),
            'port' => $this->get('site_smtp_port', '587'),
            'username' => $this->get('site_smtp_username'),
            'password' => '********', // Always masked
            'encryption' => $this->get('site_smtp_encryption', 'tls'),
            'from_email' => $this->get('site_global_email'),
            'from_name' => $this->get('mail_from_name'),
        ];
    }

    /**
     * Update SMTP configuration.
     *
     * @param array<string, mixed> $config SMTP configuration
     * @return bool
     */
    public function updateSmtpConfig(array $config): bool
    {
        $mappings = [
            'driver' => 'site_smtp_driver',
            'host' => 'site_smtp_host',
            'port' => 'site_smtp_port',
            'username' => 'site_smtp_username',
            'password' => 'site_smtp_password',
            'encryption' => 'site_smtp_encryption',
            'from_email' => 'site_global_email',
            'from_name' => 'mail_from_name',
        ];

        $settings = [];
        foreach ($config as $key => $value) {
            if (isset($mappings[$key]) && $value !== null) {
                // Skip password if it's masked
                if ($key === 'password' && $value === '********') {
                    continue;
                }
                $settings[$mappings[$key]] = $value;
            }
        }

        return $this->setBulk($settings);
    }

    /**
     * Search settings by key pattern.
     *
     * @param string $pattern Search pattern
     * @return Collection<int, StaticOption>
     */
    public function search(string $pattern): Collection
    {
        return StaticOption::where('option_name', 'like', "%{$pattern}%")->get();
    }

    /**
     * Delete a setting.
     *
     * @param string $key Setting key
     * @return bool
     */
    public function delete(string $key): bool
    {
        try {
            StaticOption::where('option_name', $key)->delete();
            $this->clearCacheForKey($key);

            Log::info('Setting deleted', ['key' => $key]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete setting', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Check if a setting exists.
     *
     * @param string $key Setting key
     * @return bool
     */
    public function exists(string $key): bool
    {
        return StaticOption::where('option_name', $key)->exists();
    }
}
