<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Room Image Resource for API responses.
 *
 * @mixin \Modules\HotelBooking\Entities\RoomImage
 */
#[OA\Schema(
    schema: 'RoomImageResource',
    title: 'Room Image Resource',
    description: 'Room image representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'image', type: 'string', example: 'https://example.com/rooms/image1.jpg'),
        new OA\Property(property: 'alt', type: 'string', example: 'Room interior', nullable: true),
    ]
)]
class RoomImageResource extends JsonResource
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
            'image' => $this->image,
            'alt' => $this->alt ?? null,
        ];
    }
}
