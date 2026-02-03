<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Language;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * Language Service
 *
 * Handles all language and translation-related business logic including:
 * - Language CRUD operations
 * - Translation file management
 * - User locale settings
 * - Translation synchronization
 */
final class LanguageService
{
    /**
     * Cache TTL in seconds (24 hours).
     */
    private const CACHE_TTL = 86400;

    /**
     * Cache key prefix for languages.
     */
    private const CACHE_PREFIX = 'language_';

    /**
     * Base path for language files.
     */
    private const LANG_PATH = 'lang';

    /**
     * Default language file name.
     */
    private const DEFAULT_LANG_FILE = 'default.json';

    /**
     * Get all available languages.
     *
     * @param bool $activeOnly Whether to return only active languages
     * @return Collection<int, Language>
     */
    public function getAvailableLanguages(bool $activeOnly = false): Collection
    {
        $cacheKey = self::CACHE_PREFIX . 'all_' . ($activeOnly ? 'active' : 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($activeOnly) {
            $query = Language::query()->orderBy('name');
            
            if ($activeOnly) {
                $query->active();
            }
            
            return $query->get();
        });
    }

    /**
     * Get the default language.
     *
     * @return Language|null
     */
    public function getDefaultLanguage(): ?Language
    {
        return Cache::remember(self::CACHE_PREFIX . 'default', self::CACHE_TTL, function () {
            return Language::where('default', true)->first() 
                ?? Language::first();
        });
    }

    /**
     * Get a language by code.
     *
     * @param string $code The language code (slug)
     * @return Language|null
     */
    public function getLanguageByCode(string $code): ?Language
    {
        return Cache::remember(self::CACHE_PREFIX . 'code_' . $code, self::CACHE_TTL, function () use ($code) {
            return Language::where('slug', $code)->first();
        });
    }

    /**
     * Create a new language.
     *
     * @param array<string, mixed> $data Language data
     * @return Language
     */
    public function createLanguage(array $data): Language
    {
        $language = Language::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'direction' => $data['direction'] ?? 0,
            'status' => $data['status'] ?? true,
            'default' => false,
        ]);

        // Create translation file from default
        $this->createTranslationFile($language->slug);

        // Clear cache
        $this->clearCache();

        return $language;
    }

    /**
     * Update a language.
     *
     * @param Language $language The language to update
     * @param array<string, mixed> $data Update data
     * @return Language
     */
    public function updateLanguage(Language $language, array $data): Language
    {
        $oldSlug = $language->slug;
        
        $language->update($data);

        // Rename translation file if slug changed
        if (isset($data['slug']) && $oldSlug !== $data['slug']) {
            $this->renameTranslationFile($oldSlug, $data['slug']);
        }

        // Clear cache
        $this->clearCache();

        return $language->fresh();
    }

    /**
     * Delete a language.
     *
     * @param Language $language The language to delete
     * @return bool
     * @throws \RuntimeException If trying to delete the default language
     */
    public function deleteLanguage(Language $language): bool
    {
        if ($language->default) {
            throw new \RuntimeException('Cannot delete the default language.');
        }

        // Delete translation file
        $this->deleteTranslationFile($language->slug);

        $deleted = $language->delete();

        // Clear cache
        $this->clearCache();

        return $deleted;
    }

    /**
     * Set a language as default.
     *
     * @param Language $language The language to set as default
     * @return Language
     */
    public function setDefaultLanguage(Language $language): Language
    {
        // Remove default from current default
        Language::where('default', true)->update(['default' => false]);

        // Set new default
        $language->update([
            'default' => true,
            'status' => true, // Ensure default is always active
        ]);

        // Update static option for central storage
        if (function_exists('update_static_option_central')) {
            update_static_option_central('landlord_default_language_slug', $language->slug);
        }

        // Clear cache
        $this->clearCache();

        return $language->fresh();
    }

    /**
     * Toggle language status.
     *
     * @param Language $language The language to toggle
     * @return Language
     * @throws \RuntimeException If trying to deactivate the default language
     */
    public function toggleStatus(Language $language): Language
    {
        if ($language->default && $language->status) {
            throw new \RuntimeException('Cannot deactivate the default language.');
        }

        $language->update(['status' => !$language->status]);

        // Clear cache
        $this->clearCache();

        return $language->fresh();
    }

    /**
     * Set user/session language.
     *
     * @param string $code Language code
     * @return void
     * @throws \InvalidArgumentException If language doesn't exist
     */
    public function setUserLanguage(string $code): void
    {
        $language = $this->getLanguageByCode($code);

        if (!$language) {
            throw new \InvalidArgumentException("Language '{$code}' not found.");
        }

        if (!$language->status) {
            throw new \InvalidArgumentException("Language '{$code}' is not active.");
        }

        session()->put('lang', $code);
        app()->setLocale($code);
    }

    /**
     * Get translations for a language.
     *
     * @param string $code Language code
     * @param string|null $group Optional group/namespace filter
     * @return array<string, string>
     */
    public function getTranslations(string $code, ?string $group = null): array
    {
        $translations = $this->loadTranslationFile($code);

        if ($group !== null) {
            // Filter by group if specified (assuming keys are prefixed with group)
            $prefix = $group . '.';
            $filtered = [];
            foreach ($translations as $key => $value) {
                if (str_starts_with($key, $prefix)) {
                    $filtered[substr($key, strlen($prefix))] = $value;
                }
            }
            return $filtered;
        }

        return $translations;
    }

    /**
     * Get default translations (source language).
     *
     * @return array<string, string>
     */
    public function getDefaultTranslations(): array
    {
        return $this->loadTranslationFile('default');
    }

    /**
     * Update translations for a language.
     *
     * @param string $code Language code
     * @param array<string, string> $translations Key-value pairs
     * @param bool $merge Whether to merge with existing (true) or replace (false)
     * @return bool
     */
    public function updateTranslations(string $code, array $translations, bool $merge = true): bool
    {
        try {
            if ($merge) {
                $existing = $this->getTranslations($code);
                $translations = array_merge($existing, $translations);
            }

            return $this->saveTranslationFile($code, $translations);
        } catch (\Exception $e) {
            Log::error('Failed to update translations', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Search translations by query.
     *
     * @param string $code Language code
     * @param string $query Search query
     * @return array<string, string>
     */
    public function searchTranslations(string $code, string $query): array
    {
        $translations = $this->getTranslations($code);
        $results = [];

        $query = strtolower($query);
        foreach ($translations as $key => $value) {
            if (
                str_contains(strtolower($key), $query) ||
                str_contains(strtolower($value), $query)
            ) {
                $results[$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Get missing translation keys for a language.
     *
     * @param string $code Language code
     * @return array<string, string>
     */
    public function getMissingKeys(string $code): array
    {
        $defaultTranslations = $this->getDefaultTranslations();
        $languageTranslations = $this->getTranslations($code);
        $missing = [];

        foreach ($defaultTranslations as $key => $value) {
            if (
                !isset($languageTranslations[$key]) ||
                $languageTranslations[$key] === '' ||
                $languageTranslations[$key] === $value
            ) {
                $missing[$key] = $value;
            }
        }

        return $missing;
    }

    /**
     * Get translation statistics for a language.
     *
     * @param string $code Language code
     * @return array<string, mixed>
     */
    public function getTranslationStats(string $code): array
    {
        $defaultTranslations = $this->getDefaultTranslations();
        $languageTranslations = $this->getTranslations($code);

        $totalKeys = count($defaultTranslations);
        $translatedKeys = 0;

        foreach ($defaultTranslations as $key => $value) {
            if (
                isset($languageTranslations[$key]) &&
                $languageTranslations[$key] !== '' &&
                $languageTranslations[$key] !== $value
            ) {
                $translatedKeys++;
            }
        }

        return [
            'total_keys' => $totalKeys,
            'translated_keys' => $translatedKeys,
            'missing_keys' => $totalKeys - $translatedKeys,
            'completion_percentage' => $totalKeys > 0 
                ? round(($translatedKeys / $totalKeys) * 100, 2) 
                : 0,
        ];
    }

    /**
     * Sync translation files for a language.
     *
     * Ensures the language file has all keys from the default file.
     *
     * @param string $code Language code
     * @return void
     */
    public function syncTranslationFiles(string $code): void
    {
        $defaultTranslations = $this->getDefaultTranslations();
        $languageTranslations = $this->getTranslations($code);

        // Add missing keys with default values
        foreach ($defaultTranslations as $key => $value) {
            if (!isset($languageTranslations[$key])) {
                $languageTranslations[$key] = $value;
            }
        }

        // Sort keys alphabetically
        ksort($languageTranslations);

        $this->saveTranslationFile($code, $languageTranslations);
    }

    /**
     * Export translations as JSON.
     *
     * @param string $code Language code
     * @return string JSON string
     */
    public function exportTranslations(string $code): string
    {
        $translations = $this->getTranslations($code);
        return json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Clone a language with all its translations.
     *
     * @param Language $source Source language
     * @param array<string, mixed> $data New language data
     * @return Language
     */
    public function cloneLanguage(Language $source, array $data): Language
    {
        // Create new language
        $newLanguage = Language::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'direction' => $data['direction'] ?? $source->direction,
            'status' => $data['status'] ?? true,
            'default' => false,
        ]);

        // Copy translation file
        $sourceTranslations = $this->getTranslations($source->slug);
        $this->saveTranslationFile($newLanguage->slug, $sourceTranslations);

        // Clear cache
        $this->clearCache();

        return $newLanguage;
    }

    /**
     * Get the path to the language directory.
     *
     * @return string
     */
    private function getLangPath(): string
    {
        return resource_path(self::LANG_PATH);
    }

    /**
     * Get the path to a specific translation file.
     *
     * @param string $code Language code
     * @return string
     */
    private function getTranslationFilePath(string $code): string
    {
        if ($code === 'default') {
            return $this->getLangPath() . '/' . self::DEFAULT_LANG_FILE;
        }
        return $this->getLangPath() . '/' . $code . '.json';
    }

    /**
     * Load a translation file.
     *
     * @param string $code Language code
     * @return array<string, string>
     */
    private function loadTranslationFile(string $code): array
    {
        $path = $this->getTranslationFilePath($code);

        if (!File::exists($path)) {
            // If file doesn't exist, copy from default
            if ($code !== 'default') {
                $this->createTranslationFile($code);
                return $this->loadTranslationFile($code);
            }
            return [];
        }

        $content = File::get($path);
        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to decode translation file', [
                'code' => $code,
                'path' => $path,
                'error' => json_last_error_msg(),
            ]);
            return [];
        }

        return $decoded ?? [];
    }

    /**
     * Save a translation file.
     *
     * @param string $code Language code
     * @param array<string, string> $translations
     * @return bool
     */
    private function saveTranslationFile(string $code, array $translations): bool
    {
        $path = $this->getTranslationFilePath($code);
        $content = json_encode($translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Ensure directory exists
        $directory = dirname($path);
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        return File::put($path, $content) !== false;
    }

    /**
     * Create a translation file from default.
     *
     * @param string $code Language code
     * @return void
     */
    private function createTranslationFile(string $code): void
    {
        $defaultPath = $this->getTranslationFilePath('default');
        $newPath = $this->getTranslationFilePath($code);

        if (File::exists($defaultPath) && !File::exists($newPath)) {
            File::copy($defaultPath, $newPath);
        } elseif (!File::exists($newPath)) {
            // Create empty file if no default exists
            $this->saveTranslationFile($code, []);
        }
    }

    /**
     * Rename a translation file.
     *
     * @param string $oldCode Old language code
     * @param string $newCode New language code
     * @return void
     */
    private function renameTranslationFile(string $oldCode, string $newCode): void
    {
        $oldPath = $this->getTranslationFilePath($oldCode);
        $newPath = $this->getTranslationFilePath($newCode);

        if (File::exists($oldPath)) {
            File::move($oldPath, $newPath);
        }
    }

    /**
     * Delete a translation file.
     *
     * @param string $code Language code
     * @return void
     */
    private function deleteTranslationFile(string $code): void
    {
        $path = $this->getTranslationFilePath($code);

        if (File::exists($path)) {
            File::delete($path);
        }
    }

    /**
     * Clear all language-related caches.
     *
     * @return void
     */
    public function clearCache(): void
    {
        // Clear specific cached items
        Cache::forget(self::CACHE_PREFIX . 'all_all');
        Cache::forget(self::CACHE_PREFIX . 'all_active');
        Cache::forget(self::CACHE_PREFIX . 'default');

        // Clear language-specific caches
        $languages = Language::all();
        foreach ($languages as $language) {
            Cache::forget(self::CACHE_PREFIX . 'code_' . $language->slug);
        }

        // Clear LanguageHelper singleton cache as well
        \App\Helpers\LanguageHelper::clearCache();
    }
}
