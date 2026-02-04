<?php

declare(strict_types=1);

namespace Modules\RealEstate\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RE_PropertyResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'reference_no', type: 'string', description: 'Property reference number'),
        new OA\Property(property: 'purpose', type: 'string', enum: ['sale', 'rent'], description: 'Listing type (alias for listing_type)'),
        new OA\Property(property: 'price', type: 'number'),
        new OA\Property(property: 'price_formatted', type: 'string'),
        new OA\Property(property: 'price_per_meter', type: 'number', nullable: true, description: 'Calculated price per square meter'),
        new OA\Property(property: 'currency', type: 'string'),
        new OA\Property(property: 'area', type: 'number', description: 'Property size in square meters'),
        new OA\Property(property: 'area_formatted', type: 'string'),
        new OA\Property(property: 'bedrooms', type: 'integer'),
        new OA\Property(property: 'bathrooms', type: 'integer'),
        new OA\Property(property: 'floor', type: 'integer', nullable: true, description: 'Floor number'),
        new OA\Property(property: 'finishing', type: 'string', nullable: true),
        new OA\Property(property: 'view', type: 'string', nullable: true),
        new OA\Property(property: 'delivery_date', type: 'string', format: 'date', nullable: true),
        new OA\Property(property: 'delivery_year', type: 'string', nullable: true, description: 'Extracted from delivery_date'),
        new OA\Property(property: 'installment_details', type: 'object', nullable: true, description: 'JSON payment plan details'),
        new OA\Property(property: 'down_payment', type: 'number', nullable: true),
        new OA\Property(property: 'monthly_installment', type: 'number', nullable: true),
        new OA\Property(property: 'installment_years', type: 'integer', nullable: true),
        new OA\Property(property: 'compound_address', type: 'string', nullable: true, description: 'Address from compound relation'),
        new OA\Property(property: 'latitude', type: 'number', nullable: true, description: 'Coordinates from compound relation'),
        new OA\Property(property: 'longitude', type: 'number', nullable: true, description: 'Coordinates from compound relation'),
        new OA\Property(property: 'is_featured', type: 'boolean'),
        new OA\Property(property: 'is_published', type: 'boolean'),
        new OA\Property(property: 'is_available', type: 'boolean'),
        new OA\Property(property: 'views_count', type: 'integer'),
        new OA\Property(property: 'url', type: 'string'),
        new OA\Property(property: 'id_slug', type: 'string', description: 'URL-friendly ID-slug format'),
        new OA\Property(property: 'area_location', ref: '#/components/schemas/RE_AreaResource', description: 'Location/Area relation'),
        new OA\Property(property: 'compound', ref: '#/components/schemas/RE_CompoundResource'),
        new OA\Property(property: 'property_type', ref: '#/components/schemas/RE_PropertyTypeResource'),
        new OA\Property(property: 'developer', ref: '#/components/schemas/RE_DeveloperResource'),
        new OA\Property(property: 'images', type: 'array', items: new OA\Items(ref: '#/components/schemas/RE_PropertyImageResource')),
        new OA\Property(property: 'primary_image', ref: '#/components/schemas/RE_PropertyImageResource', nullable: true),
        new OA\Property(property: 'amenities', type: 'array', items: new OA\Items(ref: '#/components/schemas/RE_AmenityResource')),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
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
            'reference_no' => $this->reference_number,  // DB column is reference_number
            'purpose' => $this->purpose,  // Accessor maps to listing_type
            
            // Pricing
            'price' => $this->price,
            'price_formatted' => $this->price_formatted,
            'currency' => $this->currency,
            'price_per_meter' => $this->price_per_meter,  // Calculated accessor
            
            // Specifications
            'area' => $this->area,
            'area_formatted' => $this->area_formatted,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'floor' => $this->floor_number,  // DB column is floor_number
            'finishing' => $this->finishing,
            'view' => $this->view,
            'delivery_date' => $this->delivery_date?->format('Y-m-d'),
            'delivery_year' => $this->delivery_date?->format('Y'),  // Extract year from delivery_date
            
            // Payment - installment_details is JSON column
            'installment_details' => $this->installment_details,
            'down_payment' => $this->installment_details['down_payment'] ?? null,
            'monthly_installment' => $this->installment_details['monthly_installment'] ?? null,
            'installment_years' => $this->installment_details['years'] ?? null,
            
            // Location - Properties don't have address/coordinates, those are on Compound
            'compound_address' => $this->compound?->address ?? null,
            'latitude' => $this->compound?->latitude ?? null,
            'longitude' => $this->compound?->longitude ?? null,
            
            // Status
            'is_featured' => $this->is_featured,
            'is_published' => $this->is_published,
            'is_available' => $this->is_available,
            'views_count' => $this->views_count,
            
            // SEO
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            
            // URLs
            'url' => $this->url,
            'id_slug' => $this->id_slug,
            
            // Relations
            'area' => new AreaResource($this->when(
                $this->relationLoaded('compound') && $this->compound && $this->compound->relationLoaded('area'),
                fn() => $this->compound->area
            )),
            'compound' => new CompoundResource($this->whenLoaded('compound')),
            'property_type' => new PropertyTypeResource($this->whenLoaded('propertyType')),
            'developer' => new DeveloperResource($this->when(
                $this->relationLoaded('compound') && $this->compound && $this->compound->relationLoaded('developer'),
                fn() => $this->compound->developer
            )),
            'images' => PropertyImageResource::collection($this->whenLoaded('images')),
            'primary_image' => new PropertyImageResource($this->whenLoaded('primaryImage')),
            'amenities' => AmenityResource::collection($this->whenLoaded('amenities')),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
