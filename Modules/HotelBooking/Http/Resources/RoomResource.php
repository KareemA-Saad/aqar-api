<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Room Resource for API responses.
 *
 * @mixin \Modules\HotelBooking\Entities\Room
 */
#[OA\Schema(
    schema: 'RoomResource',
    title: 'Room Resource',
    description: 'Individual room representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Room 101'),
        new OA\Property(property: 'description', type: 'string', example: 'Corner room with ocean view', nullable: true),
        new OA\Property(property: 'room_type_id', type: 'integer', example: 1),
        new OA\Property(property: 'base_cost', type: 'number', format: 'float', example: 199.99),
        new OA\Property(property: 'share_value', type: 'string', example: 'private', nullable: true),
        new OA\Property(property: 'location', type: 'string', example: 'Floor 1, Wing A', nullable: true),
        new OA\Property(property: 'type', type: 'string', example: 'standard', nullable: true),
        new OA\Property(property: 'duration', type: 'string', example: 'nightly', nullable: true),
        new OA\Property(property: 'is_featured', type: 'boolean', example: false),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'room_type', ref: '#/components/schemas/RoomTypeResource', nullable: true),
        new OA\Property(property: 'images', type: 'array', items: new OA\Items(ref: '#/components/schemas/RoomImageResource')),
        new OA\Property(property: 'country', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'state', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class RoomResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'room_type_id' => $this->room_type_id,
            'base_cost' => (float) ($this->base_cost ?? 0),
            'share_value' => $this->share_value,
            'location' => $this->location,
            'type' => $this->type,
            'duration' => $this->duration,
            'is_featured' => (bool) ($this->is_featured ?? false),
            'status' => (bool) ($this->status ?? true),
            'room_type' => $this->whenLoaded('roomType', fn() => new RoomTypeResource($this->roomType)),
            'images' => RoomImageResource::collection($this->whenLoaded('room_images')),
            'country' => $this->whenLoaded('country', fn() => [
                'id' => $this->country->id,
                'name' => $this->country->name,
            ]),
            'state' => $this->whenLoaded('state', fn() => [
                'id' => $this->state->id,
                'name' => $this->state->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
