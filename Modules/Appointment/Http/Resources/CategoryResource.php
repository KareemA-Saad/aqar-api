<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Category Resource
 */
#[OA\Schema(
    schema: 'CategoryResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Medical'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
        new OA\Property(property: 'appointments_count', type: 'integer', nullable: true),
        new OA\Property(property: 'subcategories', type: 'array', items: new OA\Items(ref: '#/components/schemas/SubcategoryResource')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
final class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getTranslation('title', app()->getLocale()),
            'title_translations' => $this->getTranslations('title'),
            'description' => $this->getTranslation('description', app()->getLocale()),
            'description_translations' => $this->getTranslations('description'),
            'image' => $this->image,
            'status' => $this->status,
            'appointments_count' => $this->when(isset($this->appointments_count), $this->appointments_count),
            'subcategories' => SubcategoryResource::collection($this->whenLoaded('subcategories')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
