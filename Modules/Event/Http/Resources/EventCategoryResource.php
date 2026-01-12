<?php

declare(strict_types=1);

namespace Modules\Event\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Event Category Resource for API responses.
 *
 * @mixin \Modules\Event\Entities\EventCategory
 */
#[OA\Schema(
    schema: 'EventCategoryResource',
    title: 'Event Category Resource',
    description: 'Event category resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Technology'),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'events_count', type: 'integer', example: 15),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
    ]
)]
class EventCategoryResource extends JsonResource
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
            'status' => (bool) $this->status,
            'events_count' => $this->whenCounted('events', $this->events_count ?? 0),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
