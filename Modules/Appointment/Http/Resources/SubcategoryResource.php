<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Subcategory Resource
 */
#[OA\Schema(
    schema: 'SubcategoryResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Dental Care'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'category_id', type: 'integer'),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
final class SubcategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getTranslation('title', app()->getLocale()),
            'title_translations' => $this->getTranslations('title'),
            'description' => $this->getTranslation('description', app()->getLocale()),
            'description_translations' => $this->getTranslations('description'),
            'category_id' => $this->category_id,
            'image' => $this->image,
            'status' => $this->status,
            'category' => $this->whenLoaded('category', fn () => new CategoryResource($this->category)),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
