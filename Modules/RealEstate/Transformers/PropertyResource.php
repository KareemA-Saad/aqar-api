<?php

declare(strict_types=1);

namespace Modules\RealEstate\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'PropertyResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'purpose', type: 'string', enum: ['sale', 'rent']),
        new OA\Property(property: 'price', type: 'number'),
        new OA\Property(property: 'price_formatted', type: 'string'),
        new OA\Property(property: 'area', type: 'number'),
        new OA\Property(property: 'area_formatted', type: 'string'),
        new OA\Property(property: 'bedrooms', type: 'integer'),
        new OA\Property(property: 'bathrooms', type: 'integer'),
        new OA\Property(property: 'finishing', type: 'string'),
        new OA\Property(property: 'delivery_year', type: 'integer'),
        new OA\Property(property: 'is_featured', type: 'boolean'),
        new OA\Property(property: 'views_count', type: 'integer'),
        new OA\Property(property: 'url', type: 'string'),
        new OA\Property(property: 'area_location', ref: '#/components/schemas/AreaResource'),
        new OA\Property(property: 'compound', ref: '#/components/schemas/CompoundResource'),
        new OA\Property(property: 'property_type', ref: '#/components/schemas/PropertyTypeResource'),
        new OA\Property(property: 'developer', ref: '#/components/schemas/DeveloperResource'),
        new OA\Property(property: 'images', type: 'array', items: new OA\Items(ref: '#/components/schemas/PropertyImageResource')),
        new OA\Property(property: 'amenities', type: 'array', items: new OA\Items(ref: '#/components/schemas/AmenityResource')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
class PropertyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'reference_no' => $this->reference_no,
            'purpose' => $this->purpose,
            
            // Pricing
            'price' => $this->price,
            'price_formatted' => $this->price_formatted,
            'currency' => $this->currency,
            'price_per_meter' => $this->price_per_meter,
            
            // Specifications
            'area' => $this->area,
            'area_formatted' => $this->area_formatted,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'floor' => $this->floor,
            'finishing' => $this->finishing,
            'furnishing' => $this->furnishing,
            'delivery_year' => $this->delivery_year,
            'delivery_date' => $this->delivery_date,
            
            // Payment
            'down_payment' => $this->down_payment,
            'monthly_installment' => $this->monthly_installment,
            'installment_years' => $this->installment_years,
            
            // Location
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            
            // Status
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            'views_count' => $this->views_count,
            
            // SEO
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            
            // URLs
            'url' => $this->url,
            
            // Relations
            'area' => new AreaResource($this->whenLoaded('area')),
            'compound' => new CompoundResource($this->whenLoaded('compound')),
            'property_type' => new PropertyTypeResource($this->whenLoaded('propertyType')),
            'developer' => new DeveloperResource($this->whenLoaded('developer')),
            'images' => PropertyImageResource::collection($this->whenLoaded('images')),
            'primary_image' => new PropertyImageResource($this->whenLoaded('primaryImage')),
            'amenities' => AmenityResource::collection($this->whenLoaded('amenities')),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
