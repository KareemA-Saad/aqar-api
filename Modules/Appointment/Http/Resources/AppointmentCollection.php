<?php

declare(strict_types=1);

namespace Modules\Appointment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Appointment Collection
 */
#[OA\Schema(
    schema: 'AppointmentCollection',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/AppointmentResource')
        ),
        new OA\Property(
            property: 'links',
            properties: [
                new OA\Property(property: 'first', type: 'string'),
                new OA\Property(property: 'last', type: 'string'),
                new OA\Property(property: 'prev', type: 'string', nullable: true),
                new OA\Property(property: 'next', type: 'string', nullable: true),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(property: 'current_page', type: 'integer'),
                new OA\Property(property: 'from', type: 'integer'),
                new OA\Property(property: 'last_page', type: 'integer'),
                new OA\Property(property: 'per_page', type: 'integer'),
                new OA\Property(property: 'to', type: 'integer'),
                new OA\Property(property: 'total', type: 'integer'),
            ],
            type: 'object'
        ),
    ]
)]
final class AppointmentCollection extends ResourceCollection
{
    public $collects = AppointmentResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'links' => [
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $this->resource->currentPage(),
                'from' => $this->resource->firstItem(),
                'last_page' => $this->resource->lastPage(),
                'per_page' => $this->resource->perPage(),
                'to' => $this->resource->lastItem(),
                'total' => $this->resource->total(),
            ],
        ];
    }
}
