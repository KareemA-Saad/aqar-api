<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Support Ticket Collection Resource
 */
#[OA\Schema(
    schema: 'SupportTicketCollection',
    title: 'Support Ticket Collection',
    description: 'Paginated collection of support tickets',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SupportTicketResource')
        ),
        new OA\Property(
            property: 'links',
            properties: [
                new OA\Property(property: 'first', type: 'string', example: 'http://example.com/api/v1/tickets?page=1'),
                new OA\Property(property: 'last', type: 'string', example: 'http://example.com/api/v1/tickets?page=10'),
                new OA\Property(property: 'prev', type: 'string', nullable: true),
                new OA\Property(property: 'next', type: 'string', example: 'http://example.com/api/v1/tickets?page=2'),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                new OA\Property(property: 'from', type: 'integer', example: 1),
                new OA\Property(property: 'last_page', type: 'integer', example: 10),
                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                new OA\Property(property: 'to', type: 'integer', example: 15),
                new OA\Property(property: 'total', type: 'integer', example: 150),
            ],
            type: 'object'
        ),
    ]
)]
class SupportTicketCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = SupportTicketResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
