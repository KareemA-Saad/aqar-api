<?php

declare(strict_types=1);

namespace Modules\Attributes\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BrandResource',
    title: 'Brand Resource',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'url', type: 'string'),
        new OA\Property(property: 'logo', type: 'object'),
        new OA\Property(property: 'banner', type: 'object'),
    ]
)]
class BrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'url' => $this->url,
            'logo' => $this->whenLoaded('logo', fn() => [
                'id' => $this->logo->id,
                'url' => $this->logo->path ?? null,
            ]),
            'banner' => $this->whenLoaded('banner', fn() => [
                'id' => $this->banner->id,
                'url' => $this->banner->path ?? null,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
