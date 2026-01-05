<?php

declare(strict_types=1);

use App\Models\StaticOption;
use Illuminate\Support\Facades\Cache;

if (!function_exists('get_static_option')) {
    /**
     * Get a static option value by key.
     *
     * @param string $key The option key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    function get_static_option(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            "static_option_{$key}",
            now()->addHours(24),
            function () use ($key, $default) {
                try {
                    $option = StaticOption::where('option_name', $key)->first();
                    return $option?->option_value ?? $default;
                } catch (\Exception $e) {
                    return $default;
                }
            }
        );
    }
}

if (!function_exists('get_static_option_central')) {
    /**
     * Get a central static option value by key.
     * For multi-tenant systems, this fetches from the central/landlord database.
     *
     * @param string $key The option key
     * @param mixed $default Default value if not found
     * @return mixed
     */
    function get_static_option_central(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            "static_option_central_{$key}",
            now()->addHours(24),
            function () use ($key, $default) {
                try {
                    // Check if StaticOptionCentral model exists
                    if (class_exists(\App\Models\StaticOptionCentral::class)) {
                        $option = \App\Models\StaticOptionCentral::where('option_name', $key)->first();
                        return $option?->option_value ?? $default;
                    }

                    // Fallback to regular StaticOption if central model doesn't exist
                    return get_static_option($key, $default);
                } catch (\Exception $e) {
                    return $default;
                }
            }
        );
    }
}

if (!function_exists('set_static_option')) {
    /**
     * Set a static option value.
     *
     * @param string $key The option key
     * @param mixed $value The option value
     * @return bool
     */
    function set_static_option(string $key, mixed $value): bool
    {
        try {
            StaticOption::updateOrCreate(
                ['option_name' => $key],
                ['option_value' => $value]
            );

            // Clear the cache for this key
            Cache::forget("static_option_{$key}");
            Cache::forget($key); // Legacy cache key format

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to set static option', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

if (!function_exists('set_static_option_central')) {
    /**
     * Set a central static option value.
     * For multi-tenant systems, this saves to the central/landlord database.
     *
     * @param string $key The option key
     * @param mixed $value The option value
     * @return bool
     */
    function set_static_option_central(string $key, mixed $value): bool
    {
        try {
            // Check if StaticOptionCentral model exists
            if (class_exists(\App\Models\StaticOptionCentral::class)) {
                \App\Models\StaticOptionCentral::updateOrCreate(
                    ['option_name' => $key],
                    ['option_value' => $value]
                );
            } else {
                // Fallback to regular StaticOption if central model doesn't exist
                return set_static_option($key, $value);
            }

            // Clear the cache for this key
            Cache::forget("static_option_central_{$key}");
            Cache::forget($key); // Legacy cache key format

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to set central static option', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

if (!function_exists('update_static_option')) {
    /**
     * Alias for set_static_option for backward compatibility.
     *
     * @param string $key The option key
     * @param mixed $value The option value
     * @return bool
     */
    function update_static_option(string $key, mixed $value): bool
    {
        return set_static_option($key, $value);
    }
}

if (!function_exists('update_static_option_central')) {
    /**
     * Alias for set_static_option_central for backward compatibility.
     *
     * @param string $key The option key
     * @param mixed $value The option value
     * @return bool
     */
    function update_static_option_central(string $key, mixed $value): bool
    {
        return set_static_option_central($key, $value);
    }
}

if (!function_exists('delete_static_option')) {
    /**
     * Delete a static option.
     *
     * @param string $key The option key
     * @return bool
     */
    function delete_static_option(string $key): bool
    {
        try {
            StaticOption::where('option_name', $key)->delete();

            // Clear the cache for this key
            Cache::forget("static_option_{$key}");
            Cache::forget($key);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}

if (!function_exists('clear_static_option_cache')) {
    /**
     * Clear cache for a specific static option or all options.
     *
     * @param string|null $key The option key (null to clear all)
     * @return void
     */
    function clear_static_option_cache(?string $key = null): void
    {
        if ($key !== null) {
            Cache::forget("static_option_{$key}");
            Cache::forget("static_option_central_{$key}");
            Cache::forget($key);
        } else {
            // Clear all static option caches
            $options = StaticOption::pluck('option_name')->toArray();
            foreach ($options as $optionKey) {
                Cache::forget("static_option_{$optionKey}");
                Cache::forget("static_option_central_{$optionKey}");
                Cache::forget($optionKey);
            }
        }
    }
}

if (!function_exists('site_currency_symbol')) {
    /**
     * Get the site currency symbol.
     *
     * @param bool $text Whether to return text format
     * @return string
     */
    function site_currency_symbol(bool $text = false): string
    {
        $customSymbol = get_static_option('site_custom_currency_symbol');

        if (!empty($customSymbol)) {
            return $customSymbol;
        }

        $currency = get_static_option('site_global_currency', 'USD');

        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            'BDT' => '৳',
            'NGN' => '₦',
            'ZAR' => 'R',
            'BRL' => 'R$',
            'MYR' => 'RM',
            'IDR' => 'Rp',
            'SAR' => '﷼',
            'AED' => 'د.إ',
        ];

        return $symbols[$currency] ?? $currency;
    }
}

if (!function_exists('amount_with_currency_symbol')) {
    /**
     * Format amount with currency symbol.
     *
     * @param float|int|string $amount The amount
     * @param bool $text Whether to return text format
     * @return string
     */
    function amount_with_currency_symbol(float|int|string $amount, bool $text = false): string
    {
        $symbol = site_currency_symbol($text);
        $position = get_static_option('site_currency_symbol_position', 'left');
        $amount = number_format((float) $amount, 2, '.', ',');

        return $position === 'right'
            ? "{$amount}{$symbol}"
            : "{$symbol}{$amount}";
    }
}

if (!function_exists('site_title')) {
    /**
     * Get the site title.
     *
     * @return string
     */
    function site_title(): string
    {
        return get_static_option('site_title', config('app.name'));
    }
}

if (!function_exists('site_global_email')) {
    /**
     * Get the site global email.
     *
     * @return string|null
     */
    function site_global_email(): ?string
    {
        return get_static_option('site_global_email');
    }
}

if (!function_exists('default_lang')) {
    /**
     * Get the default language slug from the Language model.
     *
     * @return string
     */
    function default_lang(): string
    {
        try {
            return \App\Facades\GlobalLanguage::default_slug();
        } catch (\Exception $e) {
            return config('app.locale', 'en');
        }
    }
}

if (!function_exists('get_user_lang')) {
    /**
     * Get the current user's language slug.
     *
     * @return string
     */
    function get_user_lang(): string
    {
        try {
            return \App\Facades\GlobalLanguage::user_lang_slug();
        } catch (\Exception $e) {
            return default_lang();
        }
    }
}

if (!function_exists('default_lang_name')) {
    /**
     * Get the default language name.
     *
     * @return string
     */
    function default_lang_name(): string
    {
        try {
            $defaultLang = \App\Facades\GlobalLanguage::default();
            return $defaultLang?->name ?? 'English';
        } catch (\Exception $e) {
            return 'English';
        }
    }
}

if (!function_exists('user_lang_dir')) {
    /**
     * Get the current user's language direction (ltr/rtl).
     *
     * @return string
     */
    function user_lang_dir(): string
    {
        try {
            return \App\Facades\GlobalLanguage::user_lang_dir();
        } catch (\Exception $e) {
            return 'ltr';
        }
    }
}

if (!function_exists('default_lang_dir')) {
    /**
     * Get the default language direction (ltr/rtl).
     *
     * @return string
     */
    function default_lang_dir(): string
    {
        try {
            return \App\Facades\GlobalLanguage::default_dir();
        } catch (\Exception $e) {
            return 'ltr';
        }
    }
}

if (!function_exists('all_languages')) {
    /**
     * Get all languages or active languages.
     *
     * @param int|null $type 1 for active only, null for all
     * @return \Illuminate\Database\Eloquent\Collection
     */
    function all_languages(?int $type = 1): \Illuminate\Database\Eloquent\Collection
    {
        try {
            return \App\Facades\GlobalLanguage::all_languages($type);
        } catch (\Exception $e) {
            return new \Illuminate\Database\Eloquent\Collection();
        }
    }
}

if (!function_exists('setEnvValue')) {
    /**
     * Set environment values in .env file.
     *
     * @param array<string, mixed> $values Key-value pairs
     * @return void
     */
    function setEnvValue(array $values): void
    {
        $envFile = base_path('.env');

        if (!file_exists($envFile)) {
            return;
        }

        $envContent = file_get_contents($envFile);

        foreach ($values as $key => $value) {
            // Handle values with spaces
            if (preg_match('/\s/', (string) $value) && !preg_match('/^["\'].*["\']$/', (string) $value)) {
                $value = "\"{$value}\"";
            }

            $keyPattern = "/^{$key}=.*/m";

            if (preg_match($keyPattern, $envContent)) {
                $envContent = preg_replace($keyPattern, "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envFile, $envContent);
    }
}

if (!function_exists('addQuotes')) {
    /**
     * Add quotes to a string if it contains spaces.
     *
     * @param string $str The string
     * @return string
     */
    function addQuotes(string $str): string
    {
        if (preg_match('/\s/', $str)) {
            return "\"{$str}\"";
        }

        return $str;
    }
}
