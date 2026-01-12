<?php

declare(strict_types=1);

namespace Modules\Event\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Event Resource for API responses.
 *
 * @mixin \Modules\Event\Entities\Event
 */
#[OA\Schema(
    schema: 'EventResource',
    title: 'Event Resource',
    description: 'Event resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Tech Conference 2026'),
        new OA\Property(property: 'slug', type: 'string', example: 'tech-conference-2026'),
        new OA\Property(property: 'content', type: 'string', example: 'Join us for an amazing tech conference...'),
        new OA\Property(property: 'organizer', type: 'string', example: 'Tech Events Inc'),
        new OA\Property(property: 'organizer_email', type: 'string', format: 'email', example: 'info@techevents.com'),
        new OA\Property(property: 'organizer_phone', type: 'string', example: '+1234567890'),
        new OA\Property(property: 'venue_location', type: 'string', example: 'Convention Center, New York'),
        new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-06-15', nullable: true),
        new OA\Property(property: 'time', type: 'string', format: 'time', example: '10:00:00', nullable: true),
        new OA\Property(property: 'cost', type: 'number', format: 'double', example: 99.99),
        new OA\Property(property: 'total_ticket', type: 'integer', example: 500),
        new OA\Property(property: 'available_ticket', type: 'integer', example: 320),
        new OA\Property(property: 'image', type: 'string', example: 'https://example.com/event.jpg', nullable: true),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'category_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'category', ref: '#/components/schemas/EventCategoryResource', nullable: true),
        new OA\Property(property: 'comments_count', type: 'integer', example: 12),
        new OA\Property(property: 'meta', properties: [
            new OA\Property(property: 'title', type: 'string', nullable: true),
            new OA\Property(property: 'description', type: 'string', nullable: true),
            new OA\Property(property: 'tags', type: 'string', nullable: true),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
    ]
)]
class EventResource extends JsonResource
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
            'content' => $this->content,
            'organizer' => $this->organizer,
            'organizer_email' => $this->organizer_email,
            'organizer_phone' => $this->organizer_phone,
            'venue_location' => $this->venue_location,
            'date' => $this->date,
            'time' => $this->time,
            'cost' => (float) ($this->cost ?? 0),
            'total_ticket' => (int) ($this->total_ticket ?? 0),
            'available_ticket' => (int) ($this->available_ticket ?? 0),
            'tickets_sold' => (int) ($this->total_ticket - $this->available_ticket),
            'is_sold_out' => $this->available_ticket <= 0,
            'image' => $this->image,
            'status' => (bool) $this->status,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', function () {
                return new EventCategoryResource($this->category);
            }),
            'comments_count' => $this->whenCounted('comments', $this->comments_count ?? 0),
            'meta' => $this->when($this->relationLoaded('metainfo') && $this->metainfo, function () {
                return [
                    'title' => $this->metainfo->meta_title ?? null,
                    'description' => $this->metainfo->meta_description ?? null,
                    'tags' => $this->metainfo->meta_tags ?? null,
                ];
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
