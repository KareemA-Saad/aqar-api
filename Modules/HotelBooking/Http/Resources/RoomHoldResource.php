<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Room Hold Resource for API responses.
 *
 * @mixin \Modules\HotelBooking\Entities\RoomHold
 */
#[OA\Schema(
    schema: 'RoomHoldResource',
    title: 'Room Hold Resource',
    description: 'Temporary room hold during checkout',
    properties: [
        new OA\Property(property: 'hold_token', type: 'string', example: 'abc123xyz789...'),
        new OA\Property(property: 'room_type', ref: '#/components/schemas/RoomTypeResource'),
        new OA\Property(property: 'check_in_date', type: 'string', format: 'date', example: '2024-03-15'),
        new OA\Property(property: 'check_out_date', type: 'string', format: 'date', example: '2024-03-18'),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'nights', type: 'integer', example: 3),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'expires_in_seconds', type: 'integer', example: 900),
    ]
)]
class RoomHoldResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'hold_token' => $this->hold_token,
            'room_type' => $this->whenLoaded('roomType', fn() => new RoomTypeResource($this->roomType)),
            'check_in_date' => $this->check_in_date->format('Y-m-d'),
            'check_out_date' => $this->check_out_date->format('Y-m-d'),
            'quantity' => (int) $this->quantity,
            'nights' => $this->nights,
            'expires_at' => $this->expires_at->toISOString(),
            'expires_in_seconds' => max(0, (int) now()->diffInSeconds($this->expires_at, false)),
        ];
    }
}
