<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Translation Resource
 *
 * Transforms translation data for API responses.
 */
#[OA\Schema(
    schema: 'TranslationResource',
    title: 'Translation Resource',
    description: 'Translation data for a language',
    properties: [
        new OA\Property(
            property: 'language_code',
            type: 'string',
            description: 'Language code',
            example: 'ar'
        ),
        new OA\Property(
            property: 'group',
            type: 'string',
            description: 'Translation group/namespace',
            nullable: true,
            example: 'admin'
        ),
        new OA\Property(
            property: 'total_keys',
            type: 'integer',
            description: 'Total number of translation keys',
            example: 150
        ),
        new OA\Property(
            property: 'translated_keys',
            type: 'integer',
            description: 'Number of translated keys',
            example: 145
        ),
        new OA\Property(
            property: 'missing_keys',
            type: 'integer',
            description: 'Number of missing translations',
            example: 5
        ),
        new OA\Property(
            property: 'completion_percentage',
            type: 'number',
            format: 'float',
            description: 'Translation completion percentage',
            example: 96.67
        ),
        new OA\Property(
            property: 'translations',
            type: 'object',
            description: 'Key-value pairs of translations',
            additionalProperties: new OA\AdditionalProperties(type: 'string'),
            example: ['Hello' => 'مرحبا', 'Welcome' => 'أهلاً وسهلاً']
        ),
    ]
)]
class TranslationResource extends JsonResource
{
    /**
     * Create a new resource instance.
     *
     * @param array<string, mixed> $resource
     */
    public function __construct(array $resource)
    {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $translations = $this->resource['translations'] ?? [];
        $defaultTranslations = $this->resource['default_translations'] ?? [];
        
        $totalKeys = count($defaultTranslations);
        $translatedKeys = 0;
        
        foreach ($defaultTranslations as $key => $value) {
            if (isset($translations[$key]) && $translations[$key] !== $value && $translations[$key] !== '') {
                $translatedKeys++;
            }
        }
        
        $missingKeys = $totalKeys - $translatedKeys;
        $completionPercentage = $totalKeys > 0 ? round(($translatedKeys / $totalKeys) * 100, 2) : 0;

        return [
            'language_code' => $this->resource['language_code'],
            'group' => $this->resource['group'] ?? null,
            'total_keys' => $totalKeys,
            'translated_keys' => $translatedKeys,
            'missing_keys' => $missingKeys,
            'completion_percentage' => $completionPercentage,
            'translations' => $this->when(
                $this->resource['include_translations'] ?? false,
                $translations
            ),
        ];
    }
}
