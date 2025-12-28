<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CartResource',
    title: 'Cart Resource',
    description: 'Shopping cart resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'is_guest', type: 'boolean', example: false),
        new OA\Property(property: 'items_count', type: 'integer', example: 3),
        new OA\Property(property: 'subtotal', type: 'number', format: 'float', example: 299.97),
        new OA\Property(property: 'discount', type: 'object', properties: [
            new OA\Property(property: 'coupon_code', type: 'string', nullable: true),
            new OA\Property(property: 'discount_type', type: 'string', nullable: true),
            new OA\Property(property: 'discount_amount', type: 'number'),
        ]),
        new OA\Property(property: 'shipping', type: 'object', properties: [
            new OA\Property(property: 'method_id', type: 'integer', nullable: true),
            new OA\Property(property: 'cost', type: 'number'),
        ]),
        new OA\Property(property: 'tax', type: 'number', format: 'float', example: 24.00),
        new OA\Property(property: 'total', type: 'number', format: 'float', example: 323.97),
        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: '#/components/schemas/CartItemResource')),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'is_guest' => $this->isGuest(),
            'items_count' => $this->items_count,
            'subtotal' => round($this->subtotal, 2),
            'discount' => [
                'coupon_code' => $this->coupon_code,
                'discount_type' => $this->discount_type,
                'discount_amount' => round($this->discount_amount, 2),
            ],
            'shipping' => [
                'method_id' => $this->shipping_method_id,
                'cost' => round($this->shipping_cost, 2),
                'address' => $this->shipping_address,
            ],
            'billing_address' => $this->billing_address,
            'total' => round($this->total, 2),
            'notes' => $this->notes,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
