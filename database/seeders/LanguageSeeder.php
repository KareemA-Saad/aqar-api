<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Language Seeder
 *
 * Seeds default languages for the landlord application.
 * Includes all languages that have corresponding JSON translation files.
 */
class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            // Primary Languages
            [
                'name' => 'English (British)',
                'slug' => 'en_GB',
                'direction' => 0, // LTR
                'status' => true,
                'default' => true,
            ],
            [
                'name' => 'Arabic',
                'slug' => 'ar',
                'direction' => 1, // RTL
                'status' => true,
                'default' => false,
            ],
            // English Variants
            [
                'name' => 'English (US)',
                'slug' => 'en_US',
                'direction' => 0,
                'status' => true,
                'default' => false,
            ],
            [
                'name' => 'English (Canada)',
                'slug' => 'en_CA',
                'direction' => 0,
                'status' => false, // Disabled by default
                'default' => false,
            ],
            [
                'name' => 'English (New Zealand)',
                'slug' => 'en_NZ',
                'direction' => 0,
                'status' => false,
                'default' => false,
            ],
            [
                'name' => 'English (South Africa)',
                'slug' => 'en_ZA',
                'direction' => 0,
                'status' => false,
                'default' => false,
            ],
            // Spanish Variants
            [
                'name' => 'Spanish (Spain)',
                'slug' => 'es_ES',
                'direction' => 0,
                'status' => true,
                'default' => false,
            ],
            [
                'name' => 'Spanish (Mexico)',
                'slug' => 'es_MX',
                'direction' => 0,
                'status' => false,
                'default' => false,
            ],
            [
                'name' => 'Spanish (Argentina)',
                'slug' => 'es_AR',
                'direction' => 0,
                'status' => false,
                'default' => false,
            ],
            // French Variants
            [
                'name' => 'French (France)',
                'slug' => 'fr_FR',
                'direction' => 0,
                'status' => true,
                'default' => false,
            ],
            [
                'name' => 'French (Canada)',
                'slug' => 'fr_CA',
                'direction' => 0,
                'status' => false,
                'default' => false,
            ],
            [
                'name' => 'French (Belgium)',
                'slug' => 'fr_BE',
                'direction' => 0,
                'status' => false,
                'default' => false,
            ],
            // Portuguese Variants
            [
                'name' => 'Portuguese (Brazil)',
                'slug' => 'pt_BR',
                'direction' => 0,
                'status' => true,
                'default' => false,
            ],
            [
                'name' => 'Portuguese (Portugal)',
                'slug' => 'pt_PT',
                'direction' => 0,
                'status' => false,
                'default' => false,
            ],
            // Other Languages
            [
                'name' => 'German',
                'slug' => 'de',
                'direction' => 0,
                'status' => true,
                'default' => false,
            ],
            [
                'name' => 'Hindi (India)',
                'slug' => 'hi_IN',
                'direction' => 0,
                'status' => true,
                'default' => false,
            ],
            [
                'name' => 'Indonesian',
                'slug' => 'id_ID',
                'direction' => 0,
                'status' => false,
                'default' => false,
            ],
            [
                'name' => 'Russian',
                'slug' => 'ru_RU',
                'direction' => 0,
                'status' => false,
                'default' => false,
            ],
            [
                'name' => 'Turkish',
                'slug' => 'tr_TR',
                'direction' => 0,
                'status' => false,
                'default' => false,
            ],
            [
                'name' => 'Chinese (Simplified)',
                'slug' => 'zh_CN',
                'direction' => 0,
                'status' => false,
                'default' => false,
            ],
            [
                'name' => 'Bengali (Bangladesh)',
                'slug' => 'bn_BD',
                'direction' => 0,
                'status' => false,
                'default' => false,
            ],
        ];

        foreach ($languages as $language) {
            Language::updateOrCreate(
                ['slug' => $language['slug']],
                $language
            );

            // Create translation file if it doesn't exist
            $this->createTranslationFile($language['slug']);
        }

        $this->command->info('Languages seeded successfully! ' . count($languages) . ' languages added.');
    }

    /**
     * Create translation file from default.json.
     *
     * @param string $slug Language code
     */
    private function createTranslationFile(string $slug): void
    {
        $langPath = resource_path('lang');
        $defaultPath = $langPath . '/default.json';
        $targetPath = $langPath . '/' . $slug . '.json';

        // Ensure lang directory exists
        if (!File::isDirectory($langPath)) {
            File::makeDirectory($langPath, 0755, true);
        }

        // Create translation file from default if it doesn't exist
        if (File::exists($defaultPath) && !File::exists($targetPath)) {
            File::copy($defaultPath, $targetPath);
            $this->command->info("Created translation file: {$slug}.json");
        }
    }
}
