<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CartItemResource',
    title: 'Cart Item Resource',
    description: 'Shopping cart item resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', example: 10),
        new OA\Property(property: 'variant_id', type: 'integer', nullable: true, example: 5),
        new OA\Property(property: 'quantity', type: 'integer', example: 2),
        new OA\Property(property: 'unit_price', type: 'number', format: 'float', example: 49.99),
        new OA\Property(property: 'total_price', type: 'number', format: 'float', example: 99.98),
        new OA\Property(property: 'options', type: 'object', nullable: true),
        new OA\Property(property: 'variant_name', type: 'string', nullable: true, example: 'Red - Large'),
        new OA\Property(property: 'product', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'slug', type: 'string'),
            new OA\Property(property: 'image', type: 'string'),
            new OA\Property(property: 'in_stock', type: 'boolean'),
        ]),
    ]
)]
class CartItemResource extends JsonResource
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
            'product_id' => $this->product_id,
            'variant_id' => $this->variant_id,
            'quantity' => $this->quantity,
            'unit_price' => round($this->unit_price, 2),
            'total_price' => round($this->total_price, 2),
            'options' => $this->options,
            'variant_name' => $this->variant_name,
            'product' => $this->when($this->relationLoaded('product'), function () {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->name,
                    'slug' => $this->product->slug,
                    'image' => $this->product->image_id ? asset('storage/media/' . $this->product->image_id) : null,
                    'price' => round($this->product->price, 2),
                    'sale_price' => $this->product->sale_price ? round($this->product->sale_price, 2) : null,
                ];
            }),
            'variant' => $this->when($this->relationLoaded('variant') && $this->variant, function () {
                return [
                    'id' => $this->variant->id,
                    'color' => $this->variant->productColor ? [
                        'id' => $this->variant->productColor->id,
                        'name' => $this->variant->productColor->name,
                        'color_code' => $this->variant->productColor->color_code,
                    ] : null,
                    'size' => $this->variant->productSize ? [
                        'id' => $this->variant->productSize->id,
                        'name' => $this->variant->productSize->name,
                    ] : null,
                    'additional_price' => round($this->variant->additional_price ?? 0, 2),
                    'stock_count' => $this->variant->stock_count,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
