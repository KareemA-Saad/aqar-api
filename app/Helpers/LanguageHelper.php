<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Language;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Language Helper Class
 *
 * Provides language-related helper methods for the application.
 * Used by the GlobalLanguage facade.
 *
 * @package App\Helpers
 */
class LanguageHelper
{
    /**
     * Cache TTL in seconds (10 minutes).
     */
    private const CACHE_TTL = 600;

    /**
     * Cached language instance.
     */
    private static ?Language $language = null;

    /**
     * Cached default language.
     */
    private static ?Language $default = null;

    /**
     * Cached user language slug.
     */
    private static ?string $user_lang_slug = null;

    /**
     * Cached default language slug.
     */
    private static ?string $default_slug = null;

    /**
     * Cached user language.
     */
    private static ?Language $user_lang = null;

    /**
     * Cached all languages collection.
     */
    private static ?Collection $all_language = null;

    /**
     * Create a new LanguageHelper instance.
     */
    public function __construct()
    {
        // Initialize if needed
    }

    /**
     * Get the user's current language.
     *
     * @return Language|null
     */
    public function user_lang(): ?Language
    {
        if (self::$user_lang === null) {
            $session_lang = session()->get('lang');
            if (!empty($session_lang) && $session_lang !== $this->default_slug()) {
                self::$user_lang = Language::where('slug', $session_lang)->first();
            } else {
                self::$user_lang = $this->default();
            }
        }

        return self::$user_lang;
    }

    /**
     * Get the default language.
     *
     * @return Language|null
     */
    public function default(): ?Language
    {
        if (self::$default === null) {
            self::$default = Cache::remember('language_default', self::CACHE_TTL, function () {
                return Language::where('default', true)->first()
                    ?? Language::first();
            });
        }

        return self::$default;
    }

    /**
     * Get the default language slug.
     *
     * @return string
     */
    public function default_slug(): string
    {
        if (self::$default_slug === null) {
            $default = $this->default();
            self::$default_slug = $default?->slug ?? config('app.locale', 'en');
        }

        return self::$default_slug;
    }

    /**
     * Get the default language direction.
     *
     * @return string 'ltr' or 'rtl'
     */
    public function default_dir(): string
    {
        $default = $this->default();
        return ($default?->direction ?? 0) === 0 ? 'ltr' : 'rtl';
    }

    /**
     * Get the user's current language slug.
     *
     * @return string
     */
    public function user_lang_slug(): string
    {
        if (self::$user_lang_slug === null) {
            $session_lang = session()->get('lang');
            self::$user_lang_slug = $session_lang ?? $this->default_slug();
        }

        return self::$user_lang_slug;
    }

    /**
     * Get the user's current language direction.
     *
     * @return string 'ltr' or 'rtl'
     */
    public function user_lang_dir(): string
    {
        $userLang = $this->user_lang();
        return ($userLang?->direction ?? 0) === 0 ? 'ltr' : 'rtl';
    }

    /**
     * Get all languages.
     *
     * @param int|null $type 1 for active only, null for all
     * @return Collection<int, Language>
     */
    public function all_languages(?int $type = 1): Collection
    {
        $cacheKey = 'all_languages_' . ($type ?? 'all');

        if (self::$all_language === null || self::$all_language->isEmpty()) {
            self::$all_language = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($type) {
                if ($type !== null) {
                    return Language::where('status', $type)->get();
                }
                return Language::all();
            });
        }

        return self::$all_language;
    }

    /**
     * Clear all cached language data.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$language = null;
        self::$default = null;
        self::$user_lang_slug = null;
        self::$default_slug = null;
        self::$user_lang = null;
        self::$all_language = null;

        Cache::forget('language_default');
        Cache::forget('all_languages_1');
        Cache::forget('all_languages_all');
    }

    /**
     * Set the user's language in session.
     *
     * @param string $slug Language slug
     * @return void
     */
    public function setUserLanguage(string $slug): void
    {
        session()->put('lang', $slug);
        self::$user_lang = null;
        self::$user_lang_slug = null;
    }
}
