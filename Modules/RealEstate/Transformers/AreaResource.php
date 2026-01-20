<?php

declare(strict_types=1);

namespace Modules\RealEstate\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AreaResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'type', type: 'string'),
        new OA\Property(property: 'is_featured', type: 'boolean'),
        new OA\Property(property: 'url', type: 'string'),
        new OA\Property(property: 'compounds_count', type: 'integer'),
        new OA\Property(property: 'properties_count', type: 'integer'),
    ]
)]
class AreaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            
            // Location
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            
            // Media
            'image' => $this->image,
            
            // Status
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            'order' => $this->order,
            
            // SEO
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            
            // URLs
            'url' => $this->url,
            
            // Counts
            'compounds_count' => $this->when(isset($this->compounds_count), $this->compounds_count),
            'properties_count' => $this->when(isset($this->properties_count), $this->properties_count),
            'children_count' => $this->when(isset($this->children_count), $this->children_count),
            
            // Relations
            'parent' => new AreaResource($this->whenLoaded('parent')),
            'children' => AreaResource::collection($this->whenLoaded('children')),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
