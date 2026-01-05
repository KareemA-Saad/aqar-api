<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Hotel Resource for API responses.
 *
 * @mixin \Modules\HotelBooking\Entities\Hotel
 */
#[OA\Schema(
    schema: 'HotelResource',
    title: 'Hotel Resource',
    description: 'Hotel property representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Grand Hotel'),
        new OA\Property(property: 'slug', type: 'string', example: 'grand-hotel'),
        new OA\Property(property: 'location', type: 'string', example: '123 Main Street, Downtown'),
        new OA\Property(property: 'about', type: 'string', example: 'Luxury hotel with modern amenities'),
        new OA\Property(property: 'distance', type: 'string', example: '5 km from city center', nullable: true),
        new OA\Property(property: 'restaurant_inside', type: 'boolean', example: true),
        new OA\Property(property: 'status', type: 'boolean', example: true),
        new OA\Property(property: 'country', properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'United States'),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'state', properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'California'),
        ], type: 'object', nullable: true),
        new OA\Property(property: 'images', type: 'array', items: new OA\Items(ref: '#/components/schemas/HotelImageResource')),
        new OA\Property(property: 'amenities', type: 'array', items: new OA\Items(ref: '#/components/schemas/AmenityResource')),
        new OA\Property(property: 'room_types_count', type: 'integer', example: 5),
        new OA\Property(property: 'reviews_count', type: 'integer', example: 120),
        new OA\Property(property: 'average_rating', type: 'number', format: 'float', example: 4.5),
        new OA\Property(property: 'min_price', type: 'number', format: 'float', example: 99.99, nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class HotelResource extends JsonResource
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
            'slug' => $this->slug,
            'location' => $this->location,
            'about' => $this->about,
            'distance' => $this->distance,
            'restaurant_inside' => (bool) $this->restaurant_inside,
            'status' => (bool) ($this->status ?? true),
            'country' => $this->whenLoaded('country', fn() => [
                'id' => $this->country->id,
                'name' => $this->country->name,
            ]),
            'state' => $this->whenLoaded('state', fn() => [
                'id' => $this->state->id,
                'name' => $this->state->name,
            ]),
            'images' => HotelImageResource::collection($this->whenLoaded('hotel_images')),
            'amenities' => AmenityResource::collection($this->whenLoaded('hotel_amenities')),
            'room_types_count' => $this->when(
                isset($this->room_type_count),
                fn() => $this->room_type_count
            ),
            'reviews_count' => $this->when(
                isset($this->review_count),
                fn() => $this->review_count
            ),
            'average_rating' => $this->when(
                isset($this->review_avg_ratting),
                fn() => round((float) $this->review_avg_ratting, 1)
            ),
            'min_price' => $this->when(
                isset($this->min_price),
                fn() => (float) $this->min_price
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
