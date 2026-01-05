<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Hotel Image Resource for API responses.
 *
 * @mixin \Modules\HotelBooking\Entities\HotelImage
 */
#[OA\Schema(
    schema: 'HotelImageResource',
    title: 'Hotel Image Resource',
    description: 'Hotel image representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'image', type: 'string', example: 'https://example.com/hotels/image1.jpg'),
        new OA\Property(property: 'alt', type: 'string', example: 'Hotel lobby', nullable: true),
        new OA\Property(property: 'is_primary', type: 'boolean', example: true),
    ]
)]
class HotelImageResource extends JsonResource
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
            'is_primary' => (bool) ($this->is_primary ?? false),
        ];
    }
}
