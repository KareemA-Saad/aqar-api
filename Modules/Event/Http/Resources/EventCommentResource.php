<?php

declare(strict_types=1);

namespace Modules\Event\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Event Comment Resource for API responses.
 *
 * @mixin \Modules\Event\Entities\EventComment
 */
#[OA\Schema(
    schema: 'EventCommentResource',
    title: 'Event Comment Resource',
    description: 'Event comment resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'event_id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 5, nullable: true),
        new OA\Property(property: 'commented_by', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'comment_content', type: 'string', example: 'Great event! Looking forward to it.'),
        new OA\Property(property: 'user', properties: [
            new OA\Property(property: 'id', type: 'integer', example: 5),
            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
    ]
)]
class EventCommentResource extends JsonResource
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
            'event_id' => $this->event_id,
            'user_id' => $this->user_id,
            'commented_by' => $this->commented_by,
            'comment_content' => $this->comment_content,
            'user' => $this->whenLoaded('user', function () {
                return $this->user ? [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                ] : null;
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
