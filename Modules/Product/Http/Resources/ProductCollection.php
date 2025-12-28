<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductCollection',
    title: 'Product Collection',
    description: 'Paginated collection of products',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductResource')),
        new OA\Property(property: 'meta', type: 'object', properties: [
            new OA\Property(property: 'current_page', type: 'integer', example: 1),
            new OA\Property(property: 'from', type: 'integer', example: 1),
            new OA\Property(property: 'last_page', type: 'integer', example: 10),
            new OA\Property(property: 'per_page', type: 'integer', example: 15),
            new OA\Property(property: 'to', type: 'integer', example: 15),
            new OA\Property(property: 'total', type: 'integer', example: 150),
        ]),
        new OA\Property(property: 'links', type: 'object', properties: [
            new OA\Property(property: 'first', type: 'string'),
            new OA\Property(property: 'last', type: 'string'),
            new OA\Property(property: 'prev', type: 'string', nullable: true),
            new OA\Property(property: 'next', type: 'string', nullable: true),
        ]),
    ]
)]
class ProductCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = ProductResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'current_page' => $this->currentPage(),
                'from' => $this->firstItem(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'to' => $this->lastItem(),
                'total' => $this->total(),
            ],
            'links' => [
                'first' => $this->url(1),
                'last' => $this->url($this->lastPage()),
                'prev' => $this->previousPageUrl(),
                'next' => $this->nextPageUrl(),
            ],
        ];
    }
}
