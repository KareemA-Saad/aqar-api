<?php

declare(strict_types=1);

namespace Modules\ShippingModule\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ShippingMethodResource',
    title: 'Shipping Method Resource',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'zone_id', type: 'integer'),
        new OA\Property(property: 'is_default', type: 'boolean'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'cost', type: 'number'),
        new OA\Property(property: 'status', type: 'integer'),
        new OA\Property(property: 'minimum_order_amount', type: 'number'),
    ]
)]
class ShippingMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'zone_id' => $this->zone_id,
            'is_default' => (bool) $this->is_default,
            'zone' => $this->whenLoaded('zone', fn() => [
                'id' => $this->zone->id,
                'name' => $this->zone->name,
            ]),
            'options' => $this->whenLoaded('options', fn() => [
                'title' => $this->options->title,
                'cost' => (float) $this->options->cost,
                'status' => $this->options->status,
                'tax_status' => $this->options->tax_status,
                'minimum_order_amount' => (float) ($this->options->minimum_order_amount ?? 0),
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
