<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

/**
 * Language Collection Resource
 *
 * Transforms a collection of languages for API responses.
 */
#[OA\Schema(
    schema: 'LanguageCollection',
    title: 'Language Collection',
    description: 'Paginated collection of languages',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/LanguageResource')
        ),
        new OA\Property(
            property: 'meta',
            properties: [
                new OA\Property(property: 'current_page', type: 'integer', example: 1),
                new OA\Property(property: 'from', type: 'integer', example: 1),
                new OA\Property(property: 'last_page', type: 'integer', example: 1),
                new OA\Property(property: 'per_page', type: 'integer', example: 15),
                new OA\Property(property: 'to', type: 'integer', example: 5),
                new OA\Property(property: 'total', type: 'integer', example: 5),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'links',
            properties: [
                new OA\Property(property: 'first', type: 'string', example: '/api/v1/admin/languages?page=1'),
                new OA\Property(property: 'last', type: 'string', example: '/api/v1/admin/languages?page=1'),
                new OA\Property(property: 'prev', type: 'string', nullable: true, example: null),
                new OA\Property(property: 'next', type: 'string', nullable: true, example: null),
            ],
            type: 'object'
        ),
    ]
)]
class LanguageCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = LanguageResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'default_language' => app()->getLocale(),
                'available_directions' => [
                    ['value' => 0, 'label' => 'LTR (Left to Right)'],
                    ['value' => 1, 'label' => 'RTL (Right to Left)'],
                ],
            ],
        ];
    }
}
