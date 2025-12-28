<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductSubCategoryResource',
    title: 'Product Sub Category Resource',
    description: 'Product subcategory resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'category_id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Smartphones'),
        new OA\Property(property: 'slug', type: 'string', example: 'smartphones'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'products_count', type: 'integer', example: 15),
        new OA\Property(property: 'child_categories', type: 'array', items: new OA\Items(ref: '#/components/schemas/ProductChildCategoryResource')),
    ]
)]
class ProductSubCategoryResource extends JsonResource
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
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug ?? null,
            'description' => $this->description ?? null,
            'image' => $this->when($this->image_id, function () {
                return asset('storage/media/' . $this->image_id);
            }),
            'status' => $this->status ?? 1,
            'products_count' => $this->when(isset($this->products_count), $this->products_count),
            'category' => $this->when($this->relationLoaded('category'), function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'child_categories' => $this->when($this->relationLoaded('childCategories'), function () {
                return ProductChildCategoryResource::collection($this->childCategories);
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
