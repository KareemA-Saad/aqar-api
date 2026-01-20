<?php

declare(strict_types=1);

namespace Modules\RealEstate\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CompoundResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'min_price', type: 'number'),
        new OA\Property(property: 'max_price', type: 'number'),
        new OA\Property(property: 'price_range', type: 'string'),
        new OA\Property(property: 'total_units', type: 'integer'),
        new OA\Property(property: 'is_featured', type: 'boolean'),
        new OA\Property(property: 'url', type: 'string'),
        new OA\Property(property: 'area', ref: '#/components/schemas/AreaResource'),
        new OA\Property(property: 'developer', ref: '#/components/schemas/DeveloperResource'),
    ]
)]
class CompoundResource extends JsonResource
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
            
            // Pricing
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'price_range' => $this->price_range,
            'currency' => $this->currency,
            
            // Details
            'total_units' => $this->total_units,
            'delivery_date' => $this->delivery_date,
            
            // Location
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            
            // Media
            'logo' => $this->logo,
            'video_url' => $this->video_url,
            'brochure_url' => $this->brochure_url,
            
            // Status
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            
            // SEO
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            
            // URLs
            'url' => $this->url,
            
            // Counts
            'properties_count' => $this->when(isset($this->properties_count), $this->properties_count),
            
            // Relations
            'area' => new AreaResource($this->whenLoaded('area')),
            'developer' => new DeveloperResource($this->whenLoaded('developer')),
            'images' => CompoundImageResource::collection($this->whenLoaded('images')),
            'primary_image' => new CompoundImageResource($this->whenLoaded('primaryImage')),
            'amenities' => AmenityResource::collection($this->whenLoaded('amenities')),
            'properties' => PropertyResource::collection($this->whenLoaded('properties')),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
