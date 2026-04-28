<?php

declare(strict_types=1);

namespace Modules\RealEstate\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RE_CompoundResource',
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
        new OA\Property(property: 'area', ref: '#/components/schemas/RE_AreaResource'),
        new OA\Property(property: 'developer', ref: '#/components/schemas/RE_DeveloperResource'),
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
            'name' => $this->title,  // Maps to title column (translatable)
            'title' => $this->title, // Also include title for clarity
            'slug' => $this->slug,
            'description' => $this->description,
            
            // Pricing
            'min_price' => $this->min_price,
            'max_price' => $this->max_price,
            'price_range' => $this->price_range_formatted,
            'currency' => $this->price_currency,
            
            // Details
            'total_units' => $this->units_count,
            'units_count' => $this->units_count,
            'delivery_year' => $this->delivery_year,
            'launch_year' => $this->launch_year,
            'total_area' => $this->total_area,
            'construction_status' => $this->construction_status,
            
            // Location
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            
            // Media
            'thumbnail' => $this->thumbnail_url,
            'video_url' => $this->video_url,
            'virtual_tour_url' => $this->virtual_tour_url,
            
            // Status
            'is_featured' => $this->is_featured,
            'is_published' => $this->is_published,
            
            // SEO
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            
            // Counts
            'properties_count' => $this->when(isset($this->properties_count), $this->properties_count),
            'available_properties_count' => $this->when(isset($this->available_properties_count), $this->available_properties_count),
            'views_count' => $this->when(isset($this->views_count), $this->views_count),
            
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
