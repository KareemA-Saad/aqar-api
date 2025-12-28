<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Blog Post Resource for API responses.
 *
 * @mixin \Modules\Blog\Entities\Blog
 */
#[OA\Schema(
    schema: 'BlogResource',
    title: 'Blog Resource',
    description: 'Blog post resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Getting Started with Laravel'),
        new OA\Property(property: 'slug', type: 'string', example: 'getting-started-with-laravel'),
        new OA\Property(property: 'excerpt', type: 'string', example: 'A quick introduction to Laravel framework', nullable: true),
        new OA\Property(property: 'content', type: 'string', example: 'Full blog content here...'),
        new OA\Property(property: 'image', type: 'string', example: 'https://example.com/blog/image.jpg', nullable: true),
        new OA\Property(property: 'image_gallery', type: 'string', example: '1,2,3', nullable: true),
        new OA\Property(property: 'video_url', type: 'string', example: 'https://youtube.com/watch?v=xxx', nullable: true),
        new OA\Property(property: 'tags', type: 'string', example: 'laravel,php,web', nullable: true),
        new OA\Property(property: 'views', type: 'integer', example: 150),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'featured', type: 'boolean', example: false),
        new OA\Property(property: 'visibility', type: 'string', example: 'public', nullable: true),
        new OA\Property(property: 'category_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'category', ref: '#/components/schemas/BlogCategoryResource', nullable: true),
        new OA\Property(property: 'author', properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
            new OA\Property(property: 'type', type: 'string', example: 'admin'),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'comments_count', type: 'integer', example: 5),
        new OA\Property(property: 'meta', properties: [
            new OA\Property(property: 'title', type: 'string', nullable: true),
            new OA\Property(property: 'description', type: 'string', nullable: true),
            new OA\Property(property: 'keywords', type: 'string', nullable: true),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
    ]
)]
class BlogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $authorData = $this->author_data();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->blog_content,
            'image' => $this->image,
            'image_gallery' => $this->image_gallery,
            'video_url' => $this->video_url,
            'tags' => $this->tags,
            'views' => (int) ($this->views ?? 0),
            'status' => (bool) $this->status,
            'featured' => (bool) $this->featured,
            'visibility' => $this->visibility,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', function () {
                return new BlogCategoryResource($this->category);
            }),
            'author' => $authorData ? [
                'id' => $authorData->id,
                'name' => $authorData->name ?? 'Anonymous',
                'type' => $this->created_by ?? 'admin',
            ] : null,
            'comments_count' => $this->whenCounted('comments', $this->comments_count ?? 0),
            'meta' => $this->when($this->relationLoaded('metainfo') && $this->metainfo, function () {
                return [
                    'title' => $this->metainfo->title ?? null,
                    'description' => $this->metainfo->description ?? null,
                    'keywords' => $this->metainfo->keywords ?? null,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
