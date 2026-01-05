<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Booking Room Type Resource for multi-room bookings.
 *
 * @mixin \Modules\HotelBooking\Entities\BookingRoomType
 */
#[OA\Schema(
    schema: 'BookingRoomTypeResource',
    title: 'Booking Room Type Resource',
    description: 'Room type details within a booking',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'room_type', ref: '#/components/schemas/RoomTypeResource'),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'unit_price', type: 'number', format: 'float', example: 199.99),
        new OA\Property(property: 'subtotal', type: 'number', format: 'float', example: 399.98),
        new OA\Property(property: 'adults', type: 'integer', example: 2),
        new OA\Property(property: 'children', type: 'integer', example: 1),
        new OA\Property(property: 'meal_options', properties: [
            new OA\Property(property: 'breakfast', type: 'boolean', example: true),
            new OA\Property(property: 'lunch', type: 'boolean', example: false),
            new OA\Property(property: 'dinner', type: 'boolean', example: true),
        ], type: 'object', nullable: true),
    ]
)]
class BookingRoomTypeResource extends JsonResource
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
            'room_type' => $this->whenLoaded('roomType', fn() => new RoomTypeResource($this->roomType)),
            'quantity' => (int) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'subtotal' => (float) $this->subtotal,
            'adults' => (int) $this->adults,
            'children' => (int) $this->children,
            'meal_options' => $this->meal_options,
        ];
    }
}
