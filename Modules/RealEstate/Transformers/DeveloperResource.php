<?php

declare(strict_types=1);

namespace Modules\RealEstate\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RE_DeveloperResource',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'logo', type: 'string'),
        new OA\Property(property: 'is_featured', type: 'boolean'),
        new OA\Property(property: 'compounds_count', type: 'integer'),
        new OA\Property(property: 'properties_count', type: 'integer'),
    ]
)]
class DeveloperResource extends JsonResource
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
            
            // Contact
            'logo' => $this->logo,
            'website' => $this->website,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            
            // Status
            'is_featured' => $this->is_featured,
            'status' => $this->status,
            
            // SEO
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            
            // URLs
            'url' => "/developers/{$this->id}-{$this->slug}",
            
            // Counts
            'compounds_count' => $this->when(isset($this->compounds_count), $this->compounds_count),
            'properties_count' => $this->when(isset($this->properties_count), $this->properties_count),
            
            // Relations
            'compounds' => CompoundResource::collection($this->whenLoaded('compounds')),
            
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
