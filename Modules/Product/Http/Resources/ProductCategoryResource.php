<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductCategoryResource',
    title: 'Product Category Resource',
    description: 'Product category resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Electronics'),
        new OA\Property(property: 'slug', type: 'string', example: 'electronics'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'products_count', type: 'integer', example: 25),
        new OA\Property(property: 'subcategories', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductSubCategoryResource')),
    ]
)]
class ProductCategoryResource extends JsonResource
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
            'slug' => $this->slug ?? null,
            'description' => $this->description ?? null,
            'image' => $this->when($this->image_id, function () {
                return asset('storage/media/' . $this->image_id);
            }),
            'status' => $this->status ?? 1,
            'products_count' => $this->when(isset($this->products_count), $this->products_count),
            'subcategories' => $this->when($this->relationLoaded('subcategories'), function () {
                return ProductSubCategoryResource::collection($this->subcategories);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
