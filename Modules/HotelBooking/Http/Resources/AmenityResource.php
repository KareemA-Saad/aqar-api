<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Amenity Resource for API responses.
 *
 * @mixin \Modules\HotelBooking\Entities\Amenity
 */
#[OA\Schema(
    schema: 'AmenityResource',
    title: 'Amenity Resource',
    description: 'Hotel/Room amenity representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Free WiFi'),
        new OA\Property(property: 'icon', type: 'string', example: 'wifi', nullable: true),
        new OA\Property(property: 'status', type: 'boolean', example: true),
    ]
)]
class AmenityResource extends JsonResource
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
            'icon' => $this->icon,
            'status' => (bool) ($this->status ?? true),
        ];
    }
}
