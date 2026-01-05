<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Room Type Resource for API responses.
 *
 * @mixin \Modules\HotelBooking\Entities\RoomType
 */
#[OA\Schema(
    schema: 'RoomTypeResource',
    title: 'Room Type Resource',
    description: 'Room type/category representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Deluxe Suite'),
        new OA\Property(property: 'description', type: 'string', example: 'Spacious suite with city view', nullable: true),
        new OA\Property(property: 'max_guest', type: 'integer', example: 4),
        new OA\Property(property: 'max_adult', type: 'integer', example: 2),
        new OA\Property(property: 'max_child', type: 'integer', example: 2),
        new OA\Property(property: 'no_bedroom', type: 'integer', example: 1),
        new OA\Property(property: 'no_living_room', type: 'integer', example: 1),
        new OA\Property(property: 'no_bathrooms', type: 'integer', example: 1),
        new OA\Property(property: 'base_charge', type: 'number', format: 'float', example: 199.99),
        new OA\Property(property: 'extra_adult', type: 'number', format: 'float', example: 50.00),
        new OA\Property(property: 'extra_child', type: 'number', format: 'float', example: 25.00),
        new OA\Property(property: 'breakfast_price', type: 'number', format: 'float', example: 15.00, nullable: true),
        new OA\Property(property: 'lunch_price', type: 'number', format: 'float', example: 20.00, nullable: true),
        new OA\Property(property: 'dinner_price', type: 'number', format: 'float', example: 25.00, nullable: true),
        new OA\Property(property: 'bed_type', ref: '#/components/schemas/BedTypeResource', nullable: true),
        new OA\Property(property: 'hotel', ref: '#/components/schemas/HotelResource', nullable: true),
        new OA\Property(property: 'amenities', type: 'array', items: new OA\Items(ref: '#/components/schemas/AmenityResource')),
        new OA\Property(property: 'images', type: 'array', items: new OA\Items(ref: '#/components/schemas/RoomImageResource')),
        new OA\Property(property: 'available_rooms', type: 'integer', example: 5, nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class RoomTypeResource extends JsonResource
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
            'max_guest' => (int) $this->max_guest,
            'max_adult' => (int) $this->max_adult,
            'max_child' => (int) $this->max_child,
            'no_bedroom' => (int) $this->no_bedroom,
            'no_living_room' => (int) $this->no_living_room,
            'no_bathrooms' => (int) $this->no_bathrooms,
            'base_charge' => (float) $this->base_charge,
            'extra_adult' => (float) ($this->extra_adult ?? 0),
            'extra_child' => (float) ($this->extra_child ?? 0),
            'breakfast_price' => $this->breakfast_price ? (float) $this->breakfast_price : null,
            'lunch_price' => $this->lunch_price ? (float) $this->lunch_price : null,
            'dinner_price' => $this->dinner_price ? (float) $this->dinner_price : null,
            'bed_type' => $this->whenLoaded('bed_type', fn() => new BedTypeResource($this->bed_type)),
            'hotel' => $this->whenLoaded('hotel', fn() => new HotelResource($this->hotel)),
            'amenities' => AmenityResource::collection($this->whenLoaded('room_type_amenities')),
            'images' => RoomImageResource::collection($this->whenLoaded('images')),
            'available_rooms' => $this->when(
                isset($this->available_rooms),
                fn() => (int) $this->available_rooms
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
