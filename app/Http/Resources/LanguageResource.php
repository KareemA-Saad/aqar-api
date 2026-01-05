<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Language Resource
 *
 * Transforms a language model for API responses.
 *
 * @mixin \App\Models\Language
 */
#[OA\Schema(
    schema: 'LanguageResource',
    title: 'Language Resource',
    description: 'Language data for API responses',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'English'),
        new OA\Property(property: 'slug', type: 'string', example: 'en'),
        new OA\Property(property: 'direction', type: 'integer', description: '0 = LTR, 1 = RTL', example: 0),
        new OA\Property(property: 'direction_label', type: 'string', enum: ['ltr', 'rtl'], example: 'ltr'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'default', type: 'boolean', example: true),
        new OA\Property(
            property: 'translation_stats',
            description: 'Translation statistics (only included when requested)',
            properties: [
                new OA\Property(property: 'total_keys', type: 'integer', example: 150),
                new OA\Property(property: 'translated_keys', type: 'integer', example: 145),
                new OA\Property(property: 'completion_percentage', type: 'number', format: 'float', example: 96.67),
            ],
            type: 'object',
            nullable: true
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00.000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-15T10:30:00.000Z'),
    ]
)]
class LanguageResource extends JsonResource
{
    /**
     * Additional translation stats.
     *
     * @var array<string, mixed>|null
     */
    protected ?array $translationStats = null;

    /**
     * Set translation stats for the resource.
     *
     * @param array<string, mixed> $stats
     * @return static
     */
    public function withTranslationStats(array $stats): static
    {
        $this->translationStats = $stats;
        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'direction' => $this->direction,
            'direction_label' => $this->isRtl() ? 'rtl' : 'ltr',
            'status' => $this->status,
            'default' => $this->default,
            'translation_stats' => $this->when($this->translationStats !== null, $this->translationStats),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

