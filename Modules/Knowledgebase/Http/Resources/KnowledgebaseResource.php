<?php

declare(strict_types=1);

namespace Modules\Knowledgebase\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'KnowledgebaseResource',
    title: 'Knowledgebase Resource',
    description: 'Knowledgebase article data representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'How to use the platform'),
        new OA\Property(property: 'slug', type: 'string', example: 'how-to-use-the-platform'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'image', type: 'string', nullable: true, example: 'knowledgebase/article.jpg'),
        new OA\Property(property: 'files', type: 'string', nullable: true, example: 'file1.pdf,file2.pdf'),
        new OA\Property(property: 'views', type: 'integer', example: 150),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(
            property: 'category',
            ref: '#/components/schemas/KnowledgebaseCategoryResource',
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
            property: 'files_array',
            type: 'array',
            items: new OA\Items(type: 'string'),
            nullable: true
        ),
    ]
)]
class KnowledgebaseResource extends JsonResource
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
            'image' => $this->image,
            'files' => $this->files,
            'files_array' => $this->when(!empty($this->files), function () {
                return array_filter(array_map('trim', explode(',', $this->files ?? '')));
            }),
            'views' => $this->views,
            'status' => (bool) $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships
            'category' => KnowledgebaseCategoryResource::make($this->whenLoaded('category')),
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
