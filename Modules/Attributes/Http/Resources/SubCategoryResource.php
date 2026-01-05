<?php

declare(strict_types=1);

namespace Modules\Attributes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SubCategoryResource',
    title: 'Sub-Category Resource',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'category_id', type: 'integer'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'image', type: 'object'),
        new OA\Property(property: 'child_categories', type: 'array', items: new OA\Items(ref: '#/components/schemas/ChildCategoryResource')),
    ]
)]
class SubCategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'category_id' => $this->category_id,
            'description' => $this->description,
            'image' => $this->whenLoaded('image', fn() => [
                'id' => $this->image->id,
                'url' => $this->image->path ?? null,
            ]),
            'status' => $this->whenLoaded('status', fn() => [
                'id' => $this->status->id,
                'name' => $this->status->name,
            ]),
            'category' => $this->whenLoaded('category', fn() => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ]),
            'child_categories' => $this->when(
                isset($this->child_categories),
                fn() => ChildCategoryResource::collection($this->child_categories)
            ),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
