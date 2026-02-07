<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Language;
use App\Services\LanguageService;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set API Language Middleware
 *
 * Reads language preference from request headers and sets the application locale.
 * Supports:
 * - X-Language header (custom header for explicit language setting)
 * - Accept-Language header (standard HTTP header)
 * - Falls back to default language if none specified or invalid
 *
 * @package App\Http\Middleware
 */
class SetApiLanguage
{
    /**
     * Create a new middleware instance.
     *
     * @param LanguageService $languageService The language service
     */
    public function __construct(
        private readonly LanguageService $languageService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);
        
        // Set application locale
        app()->setLocale($locale);
        Carbon::setLocale($locale);

        // Process the request
        $response = $next($request);

        // Add language info to response headers
        $response->headers->set('Content-Language', $locale);
        $response->headers->set('X-Language', $locale);

        return $response;
    }

    /**
     * Determine the locale from request headers.
     *
     * Priority:
     * 1. X-Language header (custom)
     * 2. Accept-Language header (standard)
     * 3. Session language (if available)
     * 4. Default language from database
     * 5. Fallback to 'en'
     *
     * @param Request $request
     * @return string
     */
    private function determineLocale(Request $request): string
    {
        // Priority 1: X-Language header
        $locale = $request->header('X-Language');
        if ($locale && $this->isValidLanguage($locale)) {
            return $locale;
        }

        // Priority 2: Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $locale = $this->parseAcceptLanguageHeader($acceptLanguage);
            if ($locale && $this->isValidLanguage($locale)) {
                return $locale;
            }
        }

        // Priority 3: Session language
        if (session()->has('lang')) {
            $sessionLang = session()->get('lang');
            if ($this->isValidLanguage($sessionLang)) {
                return $sessionLang;
            }
        }

        // Priority 4: Default language from database
        $defaultLanguage = $this->languageService->getDefaultLanguage();
        if ($defaultLanguage) {
            return $defaultLanguage->slug;
        }

        // Priority 5: Fallback
        return config('app.locale', 'en');
    }

    /**
     * Parse Accept-Language header to extract the preferred language.
     *
     * Handles formats like:
     * - "en-US,en;q=0.9,ar;q=0.8"
     * - "ar"
     * - "en-GB"
     *
     * @param string $header
     * @return string|null
     */
    private function parseAcceptLanguageHeader(string $header): ?string
    {
        $languages = [];

        // Parse the header into language => quality pairs
        $parts = explode(',', $header);
        foreach ($parts as $part) {
            $part = trim($part);
            
            if (str_contains($part, ';')) {
                [$lang, $quality] = explode(';', $part, 2);
                $lang = trim($lang);
                $quality = trim($quality);
                
                // Extract quality value (q=0.9)
                if (preg_match('/q=([0-9.]+)/', $quality, $matches)) {
                    $languages[$lang] = (float) $matches[1];
                } else {
                    $languages[$lang] = 1.0;
                }
            } else {
                $languages[$part] = 1.0;
            }
        }

        // Sort by quality (highest first)
        arsort($languages);

        // Return the first valid language.
        // Base language (e.g. "en") is checked BEFORE regional variants (e.g. "en_GB")
        // so that translatable JSON keys stay consistent across seeded and user-created data.
        foreach (array_keys($languages) as $lang) {
            // Try the base language first (e.g., en from en-GB)
            $baseLang = explode('-', $lang)[0];
            $baseLang = explode('_', $baseLang)[0];

            if ($this->isValidLanguage($baseLang)) {
                return $baseLang;
            }

            // Fall back to the full regional code (e.g., en_GB)
            $normalized = $this->normalizeLanguageCode($lang);

            if ($this->isValidLanguage($normalized)) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * Normalize a language code to our format.
     *
     * Converts "en-US" to "en_US" format.
     *
     * @param string $code
     * @return string
     */
    private function normalizeLanguageCode(string $code): string
    {
        // Replace hyphens with underscores
        return str_replace('-', '_', $code);
    }

    /**
     * Check if a language code is valid and active.
     *
     * @param string $code
     * @return bool
     */
    private function isValidLanguage(string $code): bool
    {
        $language = $this->languageService->getLanguageByCode($code);
        
        return $language !== null && $language->status;
    }
}
