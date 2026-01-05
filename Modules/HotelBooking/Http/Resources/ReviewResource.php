<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Review Resource for API responses.
 *
 * @mixin \Modules\HotelBooking\Entities\HotelReview
 */
#[OA\Schema(
    schema: 'ReviewResource',
    title: 'Review Resource',
    description: 'Hotel/Room review representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'hotel_id', type: 'integer', example: 1),
        new OA\Property(property: 'room_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'user', properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
            new OA\Property(property: 'avatar', type: 'string', example: 'https://example.com/avatar.jpg', nullable: true),
        ], type: 'object'),
        new OA\Property(property: 'rating', type: 'number', format: 'float', example: 4.5),
        new OA\Property(property: 'ratings_breakdown', properties: [
            new OA\Property(property: 'cleanliness', type: 'integer', example: 5),
            new OA\Property(property: 'comfort', type: 'integer', example: 4),
            new OA\Property(property: 'staff', type: 'integer', example: 5),
            new OA\Property(property: 'facilities', type: 'integer', example: 4),
        ], type: 'object'),
        new OA\Property(property: 'description', type: 'string', example: 'Excellent stay! The staff was very helpful.'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class ReviewResource extends JsonResource
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
            'hotel_id' => $this->hotel_id,
            'room_id' => $this->room_id,
            'user' => $this->whenLoaded('user', fn() => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar ?? null,
            ]),
            'rating' => round((float) $this->ratting, 1),
            'ratings_breakdown' => [
                'cleanliness' => (int) ($this->cleanliness ?? 0),
                'comfort' => (int) ($this->comfort ?? 0),
                'staff' => (int) ($this->staff ?? 0),
                'facilities' => (int) ($this->facilities ?? 0),
            ],
            'description' => $this->description,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
