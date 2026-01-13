<?php

declare(strict_types=1);

namespace Modules\Portfolio\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PortfolioResource',
    title: 'Portfolio Resource',
    description: 'Portfolio data representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Modern Website Design'),
        new OA\Property(property: 'slug', type: 'string', example: 'modern-website-design'),
        new OA\Property(property: 'url', type: 'string', nullable: true, example: 'https://example.com'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'image', type: 'string', nullable: true, example: 'portfolio/featured.jpg'),
        new OA\Property(property: 'image_gallery', type: 'string', nullable: true, example: 'img1.jpg,img2.jpg'),
        new OA\Property(property: 'client', type: 'string', nullable: true, example: 'Acme Corp'),
        new OA\Property(property: 'design', type: 'string', nullable: true, example: 'Minimalist'),
        new OA\Property(property: 'typography', type: 'string', nullable: true, example: 'Roboto'),
        new OA\Property(property: 'tags', type: 'string', nullable: true, example: 'web,design,modern'),
        new OA\Property(property: 'file', type: 'string', nullable: true),
        new OA\Property(property: 'download', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(
            property: 'category',
            ref: '#/components/schemas/PortfolioCategoryResource',
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
        new OA\Property(
            property: 'gallery',
            type: 'array',
            items: new OA\Items(type: 'string'),
            nullable: true
        ),
        new OA\Property(
            property: 'tag_array',
            type: 'array',
            items: new OA\Items(type: 'string'),
            nullable: true
        ),
    ]
)]
class PortfolioResource extends JsonResource
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
            'url' => $this->url,
            'description' => $this->description,
            'image' => $this->image,
            'image_gallery' => $this->image_gallery,
            'gallery' => $this->when(!empty($this->image_gallery), function () {
                return array_filter(array_map('trim', explode(',', $this->image_gallery ?? '')));
            }),
            'client' => $this->client,
            'design' => $this->design,
            'typography' => $this->typography,
            'tags' => $this->tags,
            'tag_array' => $this->when(!empty($this->tags), function () {
                return array_filter(array_map('trim', explode(',', $this->tags ?? '')));
            }),
            'file' => $this->file,
            'download' => $this->download,
            'status' => (bool) $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'category' => PortfolioCategoryResource::make($this->whenLoaded('category')),
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
