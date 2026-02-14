<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use Modules\RealEstate\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Services\PropertyService;
use Modules\RealEstate\Transformers\PropertyCollection;
use Modules\RealEstate\Transformers\PropertyResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Properties', description: 'Public property listing and detail endpoints')]
class PropertyController extends BaseController
{
    public function __construct(
        protected PropertyService $propertyService
    ) {}

    /**
     * List active properties with filters.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/properties',
        summary: 'List properties',
        description: 'Get paginated list of active properties with advanced filtering options',
        tags: ['Properties'],
        parameters: [
            new OA\Parameter(name: 'filter[area_id]', in: 'query', description: 'Filter by area/location', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[compound_id]', in: 'query', description: 'Filter by compound/project', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[property_type_id]', in: 'query', description: 'Filter by property type', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[developer_id]', in: 'query', description: 'Filter by developer', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[purpose]', in: 'query', description: 'Filter by purpose', schema: new OA\Schema(type: 'string', enum: ['sale', 'rent'])),
            new OA\Parameter(name: 'filter[min_price]', in: 'query', description: 'Minimum price', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'filter[max_price]', in: 'query', description: 'Maximum price', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'filter[min_area]', in: 'query', description: 'Minimum area in sqm', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'filter[max_area]', in: 'query', description: 'Maximum area in sqm', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'filter[bedrooms]', in: 'query', description: 'Number of bedrooms', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[bathrooms]', in: 'query', description: 'Number of bathrooms', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[finishing]', in: 'query', description: 'Finishing level', schema: new OA\Schema(type: 'string', enum: ['unfinished', 'semi_finished', 'fully_finished', 'furnished'])),
            new OA\Parameter(name: 'filter[is_featured]', in: 'query', description: 'Featured only', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', description: 'Sort field (prefix with - for desc)', schema: new OA\Schema(type: 'string', example: '-created_at')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Items per page', schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of properties',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/RE_PropertyResource')
                        ),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/RE_PaginationMeta'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): PropertyCollection
    {
        $properties = $this->propertyService->getPaginatedProperties($request->all());
        
        return new PropertyCollection($properties);
    }

    /**
     * Show a single property by ID-slug (Nawy-style URL).
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/properties/{property}',
        summary: 'Get property details',
        description: 'Get detailed property information by ID-slug (e.g., "123-modern-villa") or slug only (e.g., "modern-villa")',
        tags: ['Properties'],
        parameters: [
            new OA\Parameter(
                name: 'property',
                in: 'path',
                required: true,
                description: 'Property identifier: ID-slug format ("123-slug") or slug only ("slug")',
                schema: new OA\Schema(type: 'string', example: 'modern-villa-in-new-cairo')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Property details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/RE_PropertyResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Property not found'),
        ]
    )]
    public function show(string $property): JsonResponse
    {
        // Decode URL-encoded characters (spaces, special chars)
        $property = urldecode($property);
        
        // Normalize slug: convert to lowercase and replace spaces with hyphens
        $normalizedSlug = Str::slug($property);
        
        // Support both formats: "123-property-slug" or "property-slug"
        if (preg_match('/^(\d+)-(.+)$/', $property, $matches)) {
            // ID-slug format: validate both ID and slug
            $id = (int) $matches[1];
            $slug = $matches[2];
            // Try original slug first, then normalized
            $property = $this->propertyService->getPropertyByIdAndSlug($id, $slug)
                ?? $this->propertyService->getPropertyByIdAndSlug($id, Str::slug($slug));
        } else {
            // Slug-only format: try original, then normalized, then as-is from DB
            $property = $this->propertyService->getPropertyBySlug($property)
                ?? $this->propertyService->getPropertyBySlug($normalizedSlug)
                ?? Property::where('slug', 'LIKE', '%' . $property . '%')->active()->first();
        }
        
        if (!$property) {
            return response()->json(['message' => 'Property not found.'], 404);
        }
        
        // Increment views
        $this->propertyService->incrementViews($property);
        
        return response()->json([
            'data' => new PropertyResource($property),
        ]);
    }

    /**
     * Get featured properties.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/properties/featured',
        summary: 'Get featured properties',
        description: 'Get list of featured/highlighted properties for homepage showcase',
        tags: ['Properties'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', description: 'Number of properties to return', schema: new OA\Schema(type: 'integer', default: 10, maximum: 50)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Featured properties list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/RE_PropertyResource')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function featured(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $properties = $this->propertyService->getFeaturedProperties($limit);
        
        return response()->json([
            'data' => PropertyResource::collection($properties),
        ]);
    }

    /**
     * Get similar properties.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/properties/{property}/similar',
        summary: 'Get similar properties',
        description: 'Get properties similar to the specified property based on location, type, and price range',
        tags: ['Properties'],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, description: 'Property identifier: ID-slug ("123-slug") or slug only ("slug")', schema: new OA\Schema(type: 'string', example: 'modern-villa-in-new-cairo')),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Number of similar properties', schema: new OA\Schema(type: 'integer', default: 6, maximum: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Similar properties list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/RE_PropertyResource')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Property not found'),
        ]
    )]
    public function similar(string $property, Request $request): JsonResponse
    {
        // Decode URL-encoded characters (spaces, special chars)
        $property = urldecode($property);
        
        // Normalize slug: convert to lowercase and replace spaces with hyphens
        $normalizedSlug = Str::slug($property);
        
        // Support both formats: "123-property-slug" or "property-slug"
        if (preg_match('/^(\d+)-(.+)$/', $property, $matches)) {
            // ID-slug format: validate both ID and slug
            $id = (int) $matches[1];
            $slug = $matches[2];
            // Try original slug first, then normalized
            $property = $this->propertyService->getPropertyByIdAndSlug($id, $slug)
                ?? $this->propertyService->getPropertyByIdAndSlug($id, Str::slug($slug));
        } else {
            // Slug-only format: try original, then normalized, then as-is from DB
            $property = $this->propertyService->getPropertyBySlug($property)
                ?? $this->propertyService->getPropertyBySlug($normalizedSlug)
                ?? Property::where('slug', 'LIKE', '%' . $property . '%')->active()->first();
        }
        
        if (!$property) {
            return response()->json(['message' => 'Property not found.'], 404);
        }
        
        $limit = $request->input('limit', 6);
        $similar = $this->propertyService->getSimilarProperties($property, $limit);
        
        return response()->json([
            'data' => PropertyResource::collection($similar),
        ]);
    }
}
