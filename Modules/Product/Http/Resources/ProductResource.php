<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductResource',
    title: 'Product Resource',
    description: 'Product resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Sample Product'),
        new OA\Property(property: 'slug', type: 'string', example: 'sample-product'),
        new OA\Property(property: 'summary', type: 'string', example: 'Short description'),
        new OA\Property(property: 'description', type: 'string', example: 'Full product description'),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
        new OA\Property(property: 'sale_price', type: 'number', format: 'float', nullable: true, example: 79.99),
        new OA\Property(property: 'sku', type: 'string', example: 'SKU-12345'),
        new OA\Property(property: 'stock_qty', type: 'integer', example: 100),
        new OA\Property(property: 'in_stock', type: 'boolean', example: true),
        new OA\Property(property: 'badge', type: 'string', nullable: true, example: 'New'),
        new OA\Property(property: 'status_id', type: 'integer', example: 1),
        new OA\Property(property: 'is_featured', type: 'boolean', example: false),
        new OA\Property(property: 'image', type: 'string', example: 'https://example.com/product.jpg'),
        new OA\Property(property: 'gallery', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class ProductResource extends JsonResource
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
            'name' => $this->name,
            'slug' => $this->slug,
            'summary' => $this->summary,
            'description' => $this->description,
            'price' => round($this->price, 2),
            'sale_price' => $this->sale_price ? round($this->sale_price, 2) : null,
            'cost' => $this->when(auth('api_tenant_admin')->check(), round($this->cost ?? 0, 2)),
            'discount_percentage' => $this->when($this->sale_price, function () {
                return $this->price > 0 
                    ? round((($this->price - $this->sale_price) / $this->price) * 100, 1) 
                    : 0;
            }),
            'image' => $this->when($this->image_id, function () {
                return asset('storage/media/' . $this->image_id);
            }),
            'gallery' => $this->when($this->relationLoaded('gallery_images'), function () {
                return $this->gallery_images->map(function ($image) {
                    return asset('storage/media/' . $image->path);
                });
            }),
            'status' => [
                'id' => $this->status_id,
                'name' => $this->when($this->relationLoaded('status'), fn() => $this->status?->name),
            ],
            'badge' => $this->when($this->relationLoaded('badge') && $this->badge, function () {
                return [
                    'id' => $this->badge->id,
                    'name' => $this->badge->name,
                    'image' => $this->badge->image ? asset('storage/media/' . $this->badge->image) : null,
                ];
            }),
            'brand' => $this->when($this->relationLoaded('brand') && $this->brand, function () {
                return [
                    'id' => $this->brand->id,
                    'name' => $this->brand->name,
                ];
            }),
            'category' => $this->when($this->relationLoaded('category') && $this->category, function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'sub_category' => $this->when($this->relationLoaded('subCategory') && $this->subCategory, function () {
                return [
                    'id' => $this->subCategory->id,
                    'name' => $this->subCategory->name,
                ];
            }),
            'child_categories' => $this->when($this->relationLoaded('childCategory'), function () {
                return $this->childCategory->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'name' => $cat->name,
                    ];
                });
            }),
            'inventory' => $this->when($this->relationLoaded('inventory') && $this->inventory, function () {
                return [
                    'sku' => $this->inventory->sku,
                    'stock_count' => $this->inventory->stock_count,
                    'sold_count' => $this->inventory->sold_count ?? 0,
                ];
            }),
            'variants' => $this->when($this->relationLoaded('inventoryDetail'), function () {
                return ProductInventoryDetailResource::collection($this->inventoryDetail);
            }),
            'variants_count' => $this->inventory_detail_count ?? null,
            'colors' => $this->when($this->relationLoaded('color'), function () {
                return $this->color->unique('id')->map(function ($color) {
                    return [
                        'id' => $color->id,
                        'name' => $color->name,
                        'color_code' => $color->color_code ?? null,
                    ];
                })->values();
            }),
            'sizes' => $this->when($this->relationLoaded('sizes'), function () {
                return $this->sizes->unique('id')->map(function ($size) {
                    return [
                        'id' => $size->id,
                        'name' => $size->name,
                    ];
                })->values();
            }),
            'tags' => $this->when($this->relationLoaded('tag'), function () {
                return $this->tag->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->tag_name ?? $tag->name,
                    ];
                });
            }),
            'uom' => $this->when($this->relationLoaded('uom') && $this->uom, function () {
                return [
                    'id' => $this->uom->id,
                    'uom_id' => $this->uom->uom_id,
                    'quantity' => $this->uom->quantity,
                ];
            }),
            'delivery_options' => $this->when($this->relationLoaded('product_delivery_option'), function () {
                return $this->product_delivery_option->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'name' => $option->name,
                        'sub_title' => $option->sub_title,
                        'icon' => $option->icon,
                    ];
                });
            }),
            'return_policy' => $this->when($this->relationLoaded('return_policy') && $this->return_policy, function () {
                return [
                    'return_days' => $this->return_policy->return_day ?? 0,
                    'policy' => $this->return_policy->return_policy ?? null,
                    'shipping_return_description' => $this->return_policy->shipping_return_description ?? null,
                ];
            }),
            'reviews' => [
                'average_rating' => round($this->ratings() ?? 0, 1),
                'count' => $this->when($this->relationLoaded('reviews'), fn() => $this->reviews->count(), 0),
            ],
            'settings' => [
                'min_purchase' => $this->min_purchase ?? 1,
                'max_purchase' => $this->max_purchase,
                'is_refundable' => (bool) $this->is_refundable,
                'is_inventory_warn_able' => (bool) $this->is_inventory_warn_able,
                'is_in_house' => (bool) $this->is_in_house,
            ],
            'meta' => $this->when($this->relationLoaded('metaData') && $this->metaData, function () {
                return [
                    'title' => $this->metaData->title,
                    'description' => $this->metaData->description,
                    'keywords' => $this->metaData->keywords,
                    'og_image' => $this->metaData->og_image ? asset('storage/media/' . $this->metaData->og_image) : null,
                ];
            }),
            'is_in_stock' => $this->isInStock(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Check if product is in stock
     */
    protected function isInStock(): bool
    {
        // Check if product has variants
        if ($this->relationLoaded('inventoryDetail') && $this->inventoryDetail->count() > 0) {
            return $this->inventoryDetail->sum('stock_count') > 0;
        }

        // Check main inventory
        if ($this->relationLoaded('inventory') && $this->inventory) {
            return $this->inventory->stock_count > 0;
        }

        return true; // Default to in stock if no inventory info
    }
}
