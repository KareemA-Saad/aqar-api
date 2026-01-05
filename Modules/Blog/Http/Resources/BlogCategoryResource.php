<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Blog Category Resource for API responses.
 *
 * @mixin \Modules\Blog\Entities\BlogCategory
 */
#[OA\Schema(
    schema: 'BlogCategoryResource',
    title: 'Blog Category Resource',
    description: 'Blog category resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Technology'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'posts_count', type: 'integer', example: 15, nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
    ]
)]
class BlogCategoryResource extends JsonResource
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
            'status' => $this->status === '1' || $this->status === 1 || $this->status === true,
            'posts_count' => $this->whenCounted('blogs', $this->blogs_count ?? 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
