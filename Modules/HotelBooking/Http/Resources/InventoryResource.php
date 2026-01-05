<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Inventory Resource for API responses.
 *
 * @mixin \Modules\HotelBooking\Entities\Inventory
 */
#[OA\Schema(
    schema: 'InventoryResource',
    title: 'Inventory Resource',
    description: 'Room type inventory/availability for a specific date',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'room_type_id', type: 'integer', example: 1),
        new OA\Property(property: 'date', type: 'string', format: 'date', example: '2024-03-15'),
        new OA\Property(property: 'total_room', type: 'integer', example: 10),
        new OA\Property(property: 'available_room', type: 'integer', example: 7),
        new OA\Property(property: 'booked_room', type: 'integer', example: 3),
        new OA\Property(property: 'extra_base_charge', type: 'number', format: 'float', example: 249.99, nullable: true),
        new OA\Property(property: 'extra_adult', type: 'number', format: 'float', example: 50.00, nullable: true),
        new OA\Property(property: 'extra_child', type: 'number', format: 'float', example: 25.00, nullable: true),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'room_type', ref: '#/components/schemas/RoomTypeResource', nullable: true),
    ]
)]
class InventoryResource extends JsonResource
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
            'room_type_id' => $this->room_type_id,
            'date' => $this->date,
            'total_room' => (int) $this->total_room,
            'available_room' => (int) $this->available_room,
            'booked_room' => (int) $this->booked_room,
            'extra_base_charge' => $this->extra_base_charge ? (float) $this->extra_base_charge : null,
            'extra_adult' => $this->extra_adult ? (float) $this->extra_adult : null,
            'extra_child' => $this->extra_child ? (float) $this->extra_child : null,
            'status' => (bool) $this->status,
            'room_type' => $this->whenLoaded('room_type', fn() => new RoomTypeResource($this->room_type)),
        ];
    }
}
