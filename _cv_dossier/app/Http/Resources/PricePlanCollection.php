<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PricePlanCollection',
    title: 'Price Plan Collection',
    description: 'Collection of price plans with summary',
    properties: [
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/PricePlanResource')
        ),
        new OA\Property(
            property: 'summary',
            properties: [
                new OA\Property(property: 'total_plans', type: 'integer', example: 5),
                new OA\Property(property: 'active_plans', type: 'integer', example: 4),
                new OA\Property(property: 'has_trial_plans', type: 'integer', example: 2),
            ],
            type: 'object'
        ),
    ]
)]
class PricePlanCollection extends ResourceCollection
{
    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects = PricePlanResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'summary' => [
                'total_plans' => $this->collection->count(),
                'active_plans' => $this->collection->where('status', true)->count(),
                'has_trial_plans' => $this->collection->filter(
                    fn ($plan) => $plan->has_trial ?? ($plan->free_trial > 0)
                )->count(),
            ],
        ];
    }
}
