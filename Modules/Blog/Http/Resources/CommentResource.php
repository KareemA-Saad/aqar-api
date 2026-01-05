<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Blog Comment Resource for API responses.
 *
 * @mixin \Modules\Blog\Entities\BlogComment
 */
#[OA\Schema(
    schema: 'CommentResource',
    title: 'Comment Resource',
    description: 'Blog comment resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'blog_id', type: 'integer', example: 1),
        new OA\Property(property: 'content', type: 'string', example: 'Great article!'),
        new OA\Property(property: 'parent_id', type: 'integer', example: null, nullable: true),
        new OA\Property(property: 'commented_by', type: 'string', example: 'user'),
        new OA\Property(property: 'user', properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
            new OA\Property(property: 'image', type: 'string', example: 'https://example.com/avatar.jpg', nullable: true),
        ], type: 'object', nullable: true),
        new OA\Property(
            property: 'reply',
            ref: '#/components/schemas/CommentResource',
            nullable: true
        ),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
    ]
)]
class CommentResource extends JsonResource
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
            'blog_id' => $this->blog_id,
            'content' => $this->comment_content,
            'parent_id' => $this->parent_id,
            'commented_by' => $this->commented_by,
            'user' => $this->when($this->relationLoaded('user') && $this->user, function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'image' => $this->user->image ?? null,
                ];
            }),
            'reply' => $this->when($this->relationLoaded('comment_replay') && $this->comment_replay, function () {
                return new CommentResource($this->comment_replay);
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
