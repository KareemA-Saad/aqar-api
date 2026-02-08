<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use Modules\RealEstate\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Services\SearchService;
use Modules\RealEstate\Transformers\PropertyResource;
use Modules\RealEstate\Transformers\CompoundResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Search', description: 'Advanced search, autocomplete, and faceted search endpoints')]
class SearchController extends BaseController
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * Search properties with advanced filters.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/search/properties',
        summary: 'Search properties',
        description: 'Advanced property search with comprehensive filtering, sorting, and pagination',
        tags: ['Search'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Full-text search query', schema: new OA\Schema(type: 'string', example: 'villa new cairo')),
            new OA\Parameter(name: 'area_id', in: 'query', description: 'Filter by area/location ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'compound_id', in: 'query', description: 'Filter by compound ID', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'property_type_id', in: 'query', description: 'Filter by property type', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'developer_id', in: 'query', description: 'Filter by developer', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'purpose', in: 'query', description: 'Sale or rent', schema: new OA\Schema(type: 'string', enum: ['sale', 'rent'])),
            new OA\Parameter(name: 'min_price', in: 'query', description: 'Minimum price', schema: new OA\Schema(type: 'number', format: 'float')),
            new OA\Parameter(name: 'max_price', in: 'query', description: 'Maximum price', schema: new OA\Schema(type: 'number', format: 'float')),
            new OA\Parameter(name: 'min_area', in: 'query', description: 'Minimum area in sqm', schema: new OA\Schema(type: 'number', format: 'float')),
            new OA\Parameter(name: 'max_area', in: 'query', description: 'Maximum area in sqm', schema: new OA\Schema(type: 'number', format: 'float')),
            new OA\Parameter(name: 'bedrooms', in: 'query', description: 'Number of bedrooms', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'bathrooms', in: 'query', description: 'Number of bathrooms', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'finishing', in: 'query', description: 'Finishing level', schema: new OA\Schema(type: 'string', enum: ['unfinished', 'semi_finished', 'fully_finished', 'furnished'])),
            new OA\Parameter(name: 'delivery_year', in: 'query', description: 'Expected delivery year', schema: new OA\Schema(type: 'integer', example: 2025)),
            new OA\Parameter(name: 'amenities', in: 'query', description: 'Comma-separated amenity IDs', schema: new OA\Schema(type: 'string', example: '1,2,5,8')),
            new OA\Parameter(name: 'featured', in: 'query', description: 'Featured properties only', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', description: 'Sort field', schema: new OA\Schema(type: 'string', enum: ['created_at', 'price', 'area', 'bedrooms'], default: 'created_at')),
            new OA\Parameter(name: 'direction', in: 'query', description: 'Sort direction', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Results per page', schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)),
            new OA\Parameter(name: 'page', in: 'query', description: 'Page number', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results with pagination',
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
    public function properties(Request $request): JsonResponse
    {
        // Parse amenities if comma-separated
        $params = $request->all();
        if (isset($params['amenities']) && is_string($params['amenities'])) {
            $params['amenities'] = array_map('intval', explode(',', $params['amenities']));
        }
        
        $properties = $this->searchService->searchProperties($params);
        
        return response()->json([
            'data' => PropertyResource::collection($properties),
            'meta' => [
                'total' => $properties->total(),
                'per_page' => $properties->perPage(),
                'current_page' => $properties->currentPage(),
                'last_page' => $properties->lastPage(),
            ],
        ]);
    }

    /**
     * Search compounds with filters.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/search/compounds',
        summary: 'Search compounds',
        description: 'Search compounds/projects with filtering options',
        tags: ['Search'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Search query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'area_id', in: 'query', description: 'Filter by area', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'developer_id', in: 'query', description: 'Filter by developer', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'min_price', in: 'query', description: 'Minimum starting price', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'max_price', in: 'query', description: 'Maximum starting price', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Construction status', schema: new OA\Schema(type: 'string', enum: ['planning', 'under_construction', 'completed'])),
            new OA\Parameter(name: 'featured', in: 'query', description: 'Featured only', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', description: 'Sort field', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Results per page', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Compound search results',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/RE_CompoundResource')),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/RE_PaginationMeta'),
                    ]
                )
            ),
        ]
    )]
    public function compounds(Request $request): JsonResponse
    {
        $compounds = $this->searchService->searchCompounds($request->all());
        
        return response()->json([
            'data' => CompoundResource::collection($compounds),
            'meta' => [
                'total' => $compounds->total(),
                'per_page' => $compounds->perPage(),
                'current_page' => $compounds->currentPage(),
                'last_page' => $compounds->lastPage(),
            ],
        ]);
    }

    /**
     * Get autocomplete suggestions.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/search/autocomplete',
        summary: 'Get autocomplete suggestions',
        description: 'Get instant search suggestions for areas, compounds, developers, and properties based on query input. Minimum 2 characters required.',
        tags: ['Search'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, description: 'Search query (min 2 chars)', schema: new OA\Schema(type: 'string', minLength: 2, example: 'new ca')),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Max suggestions to return', schema: new OA\Schema(type: 'integer', default: 10, maximum: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Autocomplete suggestions grouped by type',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/RE_AutocompleteSuggestion')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Query too short'),
        ]
    )]
    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);
        
        $suggestions = $this->searchService->getAutocompleteSuggestions(
            $request->input('q'),
            $request->input('limit', 10)
        );
        
        return response()->json([
            'data' => $suggestions,
        ]);
    }

    /**
     * Get search facets (filters with counts).
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/search/facets',
        summary: 'Get search facets',
        description: 'Get available filter options with property counts for building dynamic filter UI. Returns property types, areas, developers, price ranges, and bedroom counts.',
        tags: ['Search'],
        parameters: [
            new OA\Parameter(name: 'area_id', in: 'query', description: 'Scope facets to specific area', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'purpose', in: 'query', description: 'Filter by purpose', schema: new OA\Schema(type: 'string', enum: ['sale', 'rent'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search facets with counts',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/RE_SearchFacets'),
                    ]
                )
            ),
        ]
    )]
    public function facets(Request $request): JsonResponse
    {
        $facets = $this->searchService->getSearchFacets($request->all());
        
        return response()->json([
            'data' => $facets,
        ]);
    }

    /**
     * Get popular searches.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/search/popular',
        summary: 'Get popular searches',
        description: 'Get trending and most popular search terms/queries',
        tags: ['Search'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', description: 'Number of results', schema: new OA\Schema(type: 'integer', default: 10, maximum: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Popular search terms',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'term', type: 'string', example: 'villa new cairo'),
                                    new OA\Property(property: 'count', type: 'integer', example: 1250),
                                ]
                            )
                        ),
                    ]
                )
            ),
        ]
    )]
    public function popular(Request $request): JsonResponse
    {
        $popular = $this->searchService->getPopularSearches($request->input('limit', 10));
        
        return response()->json([
            'data' => $popular,
        ]);
    }

    /**
     * Get nearby properties.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/search/nearby',
        summary: 'Get nearby properties',
        description: 'Get properties near a geographic location using latitude/longitude coordinates',
        tags: ['Search'],
        parameters: [
            new OA\Parameter(name: 'latitude', in: 'query', required: true, description: 'Latitude coordinate', schema: new OA\Schema(type: 'number', format: 'float', example: 30.0444)),
            new OA\Parameter(name: 'longitude', in: 'query', required: true, description: 'Longitude coordinate', schema: new OA\Schema(type: 'number', format: 'float', example: 31.2357)),
            new OA\Parameter(name: 'radius', in: 'query', description: 'Search radius in kilometers', schema: new OA\Schema(type: 'integer', default: 5, minimum: 1, maximum: 100)),
            new OA\Parameter(name: 'limit', in: 'query', description: 'Maximum results', schema: new OA\Schema(type: 'integer', default: 10, maximum: 50)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Nearby properties with distance',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                allOf: [
                                    new OA\Schema(ref: '#/components/schemas/RE_PropertyResource'),
                                    new OA\Schema(properties: [
                                        new OA\Property(property: 'distance_km', type: 'number', format: 'float', example: 2.5),
                                    ]),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Invalid coordinates'),
        ]
    )]
    public function nearby(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius' => 'nullable|integer|min:1|max:100',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);
        
        $properties = $this->searchService->getNearbyProperties(
            (float) $request->input('latitude'),
            (float) $request->input('longitude'),
            $request->input('radius', 5),
            $request->input('limit', 10)
        );
        
        return response()->json([
            'data' => $properties,
        ]);
    }

    /**
     * Get properties within map bounds (viewport).
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/map/properties',
        summary: 'Get properties within map bounds',
        description: 'Get all properties visible within a map viewport defined by northeast and southwest corners. Supports filtering by property type, price range, bedrooms, and purpose.',
        tags: ['Search'],
        parameters: [
            new OA\Parameter(name: 'ne_lat', in: 'query', required: true, description: 'Northeast corner latitude', schema: new OA\Schema(type: 'number', format: 'float', example: 30.1)),
            new OA\Parameter(name: 'ne_lng', in: 'query', required: true, description: 'Northeast corner longitude', schema: new OA\Schema(type: 'number', format: 'float', example: 31.5)),
            new OA\Parameter(name: 'sw_lat', in: 'query', required: true, description: 'Southwest corner latitude', schema: new OA\Schema(type: 'number', format: 'float', example: 29.9)),
            new OA\Parameter(name: 'sw_lng', in: 'query', required: true, description: 'Southwest corner longitude', schema: new OA\Schema(type: 'number', format: 'float', example: 31.1)),
            new OA\Parameter(name: 'property_type_id', in: 'query', description: 'Filter by property type', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'min_price', in: 'query', description: 'Minimum price', schema: new OA\Schema(type: 'number', format: 'float')),
            new OA\Parameter(name: 'max_price', in: 'query', description: 'Maximum price', schema: new OA\Schema(type: 'number', format: 'float')),
            new OA\Parameter(name: 'bedrooms', in: 'query', description: 'Number of bedrooms', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'purpose', in: 'query', description: 'Sale or rent', schema: new OA\Schema(type: 'string', enum: ['sale', 'rent'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Properties within map bounds',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/RE_PropertyResource')
                        ),
                        new OA\Property(property: 'count', type: 'integer', example: 45),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Invalid bounds'),
        ]
    )]
    public function mapProperties(Request $request): JsonResponse
    {
        $request->validate([
            'ne_lat' => 'required|numeric|between:-90,90',
            'ne_lng' => 'required|numeric|between:-180,180',
            'sw_lat' => 'required|numeric|between:-90,90',
            'sw_lng' => 'required|numeric|between:-180,180',
            'property_type_id' => 'nullable|integer|exists:re_property_types,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0',
            'bedrooms' => 'nullable|integer|min:0',
            'purpose' => 'nullable|in:sale,rent',
        ]);
        
        $filters = $request->only(['property_type_id', 'min_price', 'max_price', 'bedrooms', 'purpose']);
        
        $properties = $this->searchService->searchPropertiesInBounds(
            (float) $request->input('ne_lat'),
            (float) $request->input('ne_lng'),
            (float) $request->input('sw_lat'),
            (float) $request->input('sw_lng'),
            $filters
        );
        
        return response()->json([
            'data' => PropertyResource::collection($properties),
            'count' => $properties->count(),
        ]);
    }

    /**
     * Get property clusters for map display.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/map/clusters',
        summary: 'Get property clusters',
        description: 'Get clustered property markers for map display with zoom-level precision. Returns property counts and average prices for each cluster. Zoom levels: 1-5 (country), 6-10 (city), 11-15 (neighborhood), 16+ (street).',
        tags: ['Search'],
        parameters: [
            new OA\Parameter(name: 'zoom', in: 'query', required: true, description: 'Map zoom level (1-20)', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 20, example: 12)),
            new OA\Parameter(name: 'ne_lat', in: 'query', required: true, description: 'Northeast corner latitude', schema: new OA\Schema(type: 'number', format: 'float', example: 30.1)),
            new OA\Parameter(name: 'ne_lng', in: 'query', required: true, description: 'Northeast corner longitude', schema: new OA\Schema(type: 'number', format: 'float', example: 31.5)),
            new OA\Parameter(name: 'sw_lat', in: 'query', required: true, description: 'Southwest corner latitude', schema: new OA\Schema(type: 'number', format: 'float', example: 29.9)),
            new OA\Parameter(name: 'sw_lng', in: 'query', required: true, description: 'Southwest corner longitude', schema: new OA\Schema(type: 'number', format: 'float', example: 31.1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Property clusters with counts and stats',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'latitude', type: 'number', format: 'float', example: 30.0444),
                                    new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 31.2357),
                                    new OA\Property(property: 'count', type: 'integer', example: 15, description: 'Number of properties in cluster'),
                                    new OA\Property(property: 'avg_price', type: 'number', format: 'float', example: 2500000, description: 'Average price'),
                                    new OA\Property(property: 'min_price', type: 'number', format: 'float', example: 1800000, description: 'Minimum price'),
                                    new OA\Property(property: 'max_price', type: 'number', format: 'float', example: 3500000, description: 'Maximum price'),
                                ]
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Invalid parameters'),
        ]
    )]
    public function clusters(Request $request): JsonResponse
    {
        $request->validate([
            'zoom' => 'required|integer|min:1|max:20',
            'ne_lat' => 'required|numeric|between:-90,90',
            'ne_lng' => 'required|numeric|between:-180,180',
            'sw_lat' => 'required|numeric|between:-90,90',
            'sw_lng' => 'required|numeric|between:-180,180',
        ]);
        
        $clusters = $this->searchService->getPropertyClusters(
            (int) $request->input('zoom'),
            (float) $request->input('ne_lat'),
            (float) $request->input('ne_lng'),
            (float) $request->input('sw_lat'),
            (float) $request->input('sw_lng')
        );
        
        return response()->json([
            'data' => $clusters,
        ]);
    }
}
