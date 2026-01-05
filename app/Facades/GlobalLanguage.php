<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use App\Helpers\LanguageHelper;

/**
 * GlobalLanguage Facade
 *
 * Provides a fluent interface to the LanguageHelper class.
 *
 * @method static \App\Models\Language|null user_lang() Get the user's current language
 * @method static \App\Models\Language|null default() Get the default language
 * @method static string default_slug() Get the default language slug
 * @method static string default_dir() Get the default language direction ('ltr' or 'rtl')
 * @method static string user_lang_slug() Get the user's current language slug
 * @method static string user_lang_dir() Get the user's current language direction
 * @method static \Illuminate\Database\Eloquent\Collection all_languages(?int $type = 1) Get all languages
 * @method static void clearCache() Clear all cached language data
 * @method static void setUserLanguage(string $slug) Set the user's language in session
 *
 * @see \App\Helpers\LanguageHelper
 */
class GlobalLanguage extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'GlobalLanguage';
    }
}
