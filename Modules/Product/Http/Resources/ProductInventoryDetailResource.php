<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductInventoryDetailResource',
    title: 'Product Inventory Detail Resource',
    description: 'Product variant/inventory detail resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', example: 10),
        new OA\Property(property: 'color', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'code', type: 'string'),
        ]),
        new OA\Property(property: 'size', type: 'object', nullable: true, properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'code', type: 'string'),
        ]),
        new OA\Property(property: 'sku', type: 'string', example: 'SKU-RED-L'),
        new OA\Property(property: 'stock_qty', type: 'integer', example: 50),
        new OA\Property(property: 'price', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'sale_price', type: 'number', format: 'float', nullable: true),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'in_stock', type: 'boolean', example: true),
    ]
)]
class ProductInventoryDetailResource extends JsonResource
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
            'color' => $this->when($this->relationLoaded('productColor') || $this->productColor, function () {
                return $this->productColor ? [
                    'id' => $this->productColor->id,
                    'name' => $this->productColor->name,
                    'color_code' => $this->productColor->color_code ?? null,
                ] : null;
            }),
            'size' => $this->when($this->relationLoaded('productSize') || $this->productSize, function () {
                return $this->productSize ? [
                    'id' => $this->productSize->id,
                    'name' => $this->productSize->name,
                ] : null;
            }),
            'additional_price' => round($this->additional_price ?? 0, 2),
            'add_cost' => round($this->add_cost ?? 0, 2),
            'stock_count' => $this->stock_count,
            'sold_count' => $this->sold_count,
            'is_in_stock' => $this->stock_count > 0,
            'image' => $this->when($this->relationLoaded('attr_image'), function () {
                return $this->attr_image ? asset('storage/media/' . $this->attr_image->path) : null;
            }),
            'attributes' => $this->when($this->relationLoaded('attribute'), function () {
                return $this->attribute->map(function ($attr) {
                    return [
                        'id' => $attr->id,
                        'attribute_name' => $attr->attribute_name,
                        'attribute_value' => $attr->attribute_value,
                    ];
                });
            }),
        ];
    }
}
