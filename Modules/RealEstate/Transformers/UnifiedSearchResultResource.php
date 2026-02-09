<?php

declare(strict_types=1);

namespace Modules\RealEstate\Transformers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Unified Search Result Resource
 * 
 * Transforms different entity types (Properties, Compounds, Areas, Developers)
 * into a unified search result format with common fields and type-specific data.
 */
#[OA\Schema(
    schema: 'UnifiedSearchResult',
    properties: [
        new OA\Property(property: 'type', type: 'string', enum: ['property', 'compound', 'area', 'developer'], description: 'Entity type'),
        new OA\Property(property: 'id', type: 'integer', description: 'Entity ID'),
        new OA\Property(property: 'title', type: 'string', description: 'Entity title/name'),
        new OA\Property(property: 'description', type: 'string', nullable: true, description: 'Entity description'),
        new OA\Property(property: 'url', type: 'string', description: 'SEO-friendly URL'),
        new OA\Property(property: 'image', type: 'string', nullable: true, description: 'Primary image URL'),
        new OA\Property(property: 'relevance_score', type: 'number', format: 'float', description: 'Search relevance score (0-100)'),
        new OA\Property(
            property: 'data',
            description: 'Entity-specific data',
            oneOf: [
                new OA\Schema(ref: '#/components/schemas/UnifiedSearchResult_Property'),
                new OA\Schema(ref: '#/components/schemas/UnifiedSearchResult_Compound'),
                new OA\Schema(ref: '#/components/schemas/UnifiedSearchResult_Area'),
                new OA\Schema(ref: '#/components/schemas/UnifiedSearchResult_Developer'),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'UnifiedSearchResult_Property',
    properties: [
        new OA\Property(property: 'price', type: 'number'),
        new OA\Property(property: 'price_formatted', type: 'string'),
        new OA\Property(property: 'currency', type: 'string'),
        new OA\Property(property: 'purpose', type: 'string', enum: ['sale', 'rent']),
        new OA\Property(property: 'bedrooms', type: 'integer', nullable: true),
        new OA\Property(property: 'bathrooms', type: 'integer', nullable: true),
        new OA\Property(property: 'area', type: 'number', nullable: true, description: 'Property size in sqm'),
        new OA\Property(property: 'area_formatted', type: 'string'),
        new OA\Property(property: 'location', type: 'object', properties: [
            new OA\Property(property: 'area', type: 'string'),
            new OA\Property(property: 'compound', type: 'string'),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'UnifiedSearchResult_Compound',
    properties: [
        new OA\Property(property: 'developer', type: 'string', nullable: true),
        new OA\Property(property: 'min_price', type: 'number', nullable: true),
        new OA\Property(property: 'max_price', type: 'number', nullable: true),
        new OA\Property(property: 'price_range', type: 'string', nullable: true),
        new OA\Property(property: 'properties_count', type: 'integer'),
        new OA\Property(property: 'location', type: 'object', properties: [
            new OA\Property(property: 'area', type: 'string'),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'UnifiedSearchResult_Area',
    properties: [
        new OA\Property(property: 'type', type: 'string', description: 'Area type (super_area/area/sub_area)'),
        new OA\Property(property: 'properties_count', type: 'integer'),
        new OA\Property(property: 'compounds_count', type: 'integer'),
    ]
)]
#[OA\Schema(
    schema: 'UnifiedSearchResult_Developer',
    properties: [
        new OA\Property(property: 'logo', type: 'string', nullable: true),
        new OA\Property(property: 'compounds_count', type: 'integer'),
        new OA\Property(property: 'properties_count', type: 'integer'),
        new OA\Property(property: 'website', type: 'string', nullable: true),
    ]
)]
class UnifiedSearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * 
     * Handles different entity types based on the entity_type attribute.
     */
    public function toArray(Request $request): array
    {
        // Get entity type from the model
        $entityType = $this->entity_type ?? 'unknown';

        // Common fields for all entity types
        $result = [
            'type' => $entityType,
            'id' => $this->id,
            'relevance_score' => $this->relevance_score ?? 0,
        ];

        // Add type-specific transformations
        switch ($entityType) {
            case 'property':
                return array_merge($result, $this->transformProperty());
            
            case 'compound':
                return array_merge($result, $this->transformCompound());
            
            case 'area':
                return array_merge($result, $this->transformArea());
            
            case 'developer':
                return array_merge($result, $this->transformDeveloper());
            
            default:
                return $result;
        }
    }

    /**
     * Transform Property entity.
     */
    protected function transformProperty(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'url' => "/properties/{$this->id}-{$this->slug}",
            'image' => $this->primaryImage?->url ?? $this->thumbnail ?? null,
            'data' => [
                'price' => $this->price,
                'price_formatted' => number_format($this->price, 0) . ' ' . $this->currency,
                'currency' => $this->currency,
                'purpose' => $this->listing_type, // Maps to 'purpose' in API
                'bedrooms' => $this->bedrooms,
                'bathrooms' => $this->bathrooms,
                'area' => $this->area,
                'area_formatted' => $this->area ? number_format($this->area, 0) . ' sqm' : null,
                'finishing' => $this->finishing,
                'is_featured' => $this->is_featured,
                'location' => [
                    'area' => $this->compound?->area?->name ?? null,
                    'compound' => $this->compound?->title ?? null,
                ],
            ],
        ];
    }

    /**
     * Transform Compound entity.
     */
    protected function transformCompound(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'url' => "/compounds/{$this->id}-{$this->slug}",
            'image' => $this->primaryImage?->url ?? $this->thumbnail ?? null,
            'data' => [
                'developer' => $this->developer?->name ?? null,
                'min_price' => $this->min_price,
                'max_price' => $this->max_price,
                'price_range' => $this->formatPriceRange(),
                'currency' => $this->price_currency ?? 'USD',
                'properties_count' => $this->properties_count ?? 0,
                'is_featured' => $this->is_featured,
                'location' => [
                    'area' => $this->area?->name ?? null,
                ],
            ],
        ];
    }

    /**
     * Transform Area entity.
     */
    protected function transformArea(): array
    {
        return [
            'title' => $this->name,
            'description' => $this->description,
            'url' => "/areas/{$this->id}-{$this->slug}",
            'image' => $this->image ?? null,
            'data' => [
                'type' => $this->type,
                'properties_count' => $this->properties_count ?? 0,
                'compounds_count' => $this->compounds_count ?? 0,
            ],
        ];
    }

    /**
     * Transform Developer entity.
     */
    protected function transformDeveloper(): array
    {
        return [
            'title' => $this->name,
            'description' => $this->description,
            'url' => "/developers/{$this->id}-{$this->slug}",
            'image' => $this->logo ?? null,
            'data' => [
                'logo' => $this->logo,
                'website' => $this->website,
                'compounds_count' => $this->compounds_count ?? 0,
                'properties_count' => $this->properties_count ?? 0,
                'is_featured' => $this->is_featured,
            ],
        ];
    }

    /**
     * Format price range for compounds.
     */
    protected function formatPriceRange(): ?string
    {
        if (!$this->min_price && !$this->max_price) {
            return null;
        }

        $currency = $this->price_currency ?? 'USD';
        
        if ($this->min_price && $this->max_price) {
            return number_format($this->min_price, 0) . ' - ' . number_format($this->max_price, 0) . ' ' . $currency;
        }

        if ($this->min_price) {
            return 'From ' . number_format($this->min_price, 0) . ' ' . $currency;
        }

        return 'Up to ' . number_format($this->max_price, 0) . ' ' . $currency;
    }
}
