<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Booking Collection for paginated API responses.
 */
#[OA\Schema(
    schema: 'BookingCollection',
    title: 'Booking Collection',
    description: 'Paginated collection of bookings',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/BookingResource')),
        new OA\Property(
            property: 'pagination',
            properties: [
                new OA\Property(property: 'total', type: 'integer', example: 100),
                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                new OA\Property(property: 'last_page', type: 'integer', example: 7),
                new OA\Property(property: 'from', type: 'integer', example: 1),
                new OA\Property(property: 'to', type: 'integer', example: 15),
            ],
            type: 'object'
        ),
    ]
)]
class BookingCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = BookingResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'pagination' => [
                'total' => $this->total(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ],
        ];
    }
}
