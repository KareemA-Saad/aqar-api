<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProductChildCategoryResource',
    title: 'Product Child Category Resource',
    description: 'Product child category resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'sub_category_id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Android Phones'),
        new OA\Property(property: 'slug', type: 'string', example: 'android-phones'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'products_count', type: 'integer', example: 10),
    ]
)]
class ProductChildCategoryResource extends JsonResource
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
            'sub_category_id' => $this->sub_category_id,
            'name' => $this->name,
            'slug' => $this->slug ?? null,
            'description' => $this->description ?? null,
            'image' => $this->when($this->image_id, function () {
                return asset('storage/media/' . $this->image_id);
            }),
            'status' => $this->status ?? 1,
            'products_count' => $this->when(isset($this->products_count), $this->products_count),
            'sub_category' => $this->when($this->relationLoaded('subCategory'), function () {
                return [
                    'id' => $this->subCategory->id,
                    'name' => $this->subCategory->name,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
