<?php

declare(strict_types=1);

namespace Modules\Knowledgebase\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'KnowledgebaseCategoryResource',
    title: 'Knowledgebase Category Resource',
    description: 'Knowledgebase category data representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Getting Started'),
        new OA\Property(property: 'image', type: 'string', nullable: true, example: 'categories/getting-started.jpg'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'knowledgebase_count', type: 'integer', example: 12),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class KnowledgebaseCategoryResource extends JsonResource
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
            'image' => $this->image,
            'status' => (bool) $this->status,
            'knowledgebase_count' => $this->whenCounted('knowledgebase'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
