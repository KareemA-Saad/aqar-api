<?php

declare(strict_types=1);

namespace Modules\Service\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ServiceResource',
    title: 'Service Resource',
    description: 'Service data representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Web Development'),
        new OA\Property(property: 'slug', type: 'string', example: 'web-development'),
        new OA\Property(property: 'description', type: 'string', example: 'Professional web development services'),
        new OA\Property(property: 'price_plan', type: 'string', example: '$50/hour'),
        new OA\Property(property: 'image', type: 'string', nullable: true, example: 'services/web-dev.jpg'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'meta_tag', type: 'string', nullable: true),
        new OA\Property(property: 'meta_description', type: 'string', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(
            property: 'category',
            ref: '#/components/schemas/ServiceCategoryResource',
            nullable: true
        ),
        new OA\Property(
            property: 'metainfo',
            properties: [
                new OA\Property(property: 'meta_title', type: 'string', nullable: true),
                new OA\Property(property: 'meta_description', type: 'string', nullable: true),
                new OA\Property(property: 'meta_tags', type: 'string', nullable: true),
            ],
            type: 'object',
            nullable: true
        ),
    ]
)]
class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'price_plan' => $this->price_plan,
            'image' => $this->image,
            'status' => (bool) $this->status,
            'meta_tag' => $this->meta_tag,
            'meta_description' => $this->meta_description,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'category' => ServiceCategoryResource::make($this->whenLoaded('category')),
            'metainfo' => $this->when($this->relationLoaded('metainfo'), function () {
                return $this->metainfo ? [
                    'meta_title' => $this->metainfo->meta_title,
                    'meta_description' => $this->metainfo->meta_description,
                    'meta_tags' => $this->metainfo->meta_tags,
                ] : null;
            }),
        ];
    }
}
