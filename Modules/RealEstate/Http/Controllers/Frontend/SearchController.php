<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Services\SearchService;
use Modules\RealEstate\Transformers\PropertyResource;
use Modules\RealEstate\Transformers\CompoundResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Search', description: 'Advanced search endpoints')]
class SearchController extends Controller
{
    public function __construct(
        protected SearchService $searchService
    ) {}

    /**
     * Search properties with advanced filters.
     */
    #[OA\Get(
        path: '/api/realestate/search/properties',
        summary: 'Search properties',
        tags: ['Search'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Search query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'area_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'compound_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'property_type_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'developer_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'purpose', in: 'query', schema: new OA\Schema(type: 'string', enum: ['sale', 'rent'])),
            new OA\Parameter(name: 'min_price', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'max_price', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'min_area', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'max_area', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'bedrooms', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'bathrooms', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'finishing', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'delivery_year', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'amenities', in: 'query', description: 'Comma-separated amenity IDs', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'featured', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: ['created_at', 'price', 'area', 'bedrooms'])),
            new OA\Parameter(name: 'direction', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Search results'),
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
        path: '/api/realestate/search/compounds',
        summary: 'Search compounds',
        tags: ['Search'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', description: 'Search query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'area_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'developer_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'min_price', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'max_price', in: 'query', schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'featured', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Search results'),
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
        path: '/api/realestate/search/autocomplete',
        summary: 'Get autocomplete suggestions',
        tags: ['Search'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, description: 'Search query', schema: new OA\Schema(type: 'string', minLength: 2)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Autocomplete suggestions'),
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
        path: '/api/realestate/search/facets',
        summary: 'Get search facets',
        tags: ['Search'],
        parameters: [
            new OA\Parameter(name: 'area_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Search facets'),
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
        path: '/api/realestate/search/popular',
        summary: 'Get popular searches',
        tags: ['Search'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Popular searches'),
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
        path: '/api/realestate/search/nearby',
        summary: 'Get nearby properties',
        tags: ['Search'],
        parameters: [
            new OA\Parameter(name: 'latitude', in: 'query', required: true, schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'longitude', in: 'query', required: true, schema: new OA\Schema(type: 'number')),
            new OA\Parameter(name: 'radius', in: 'query', description: 'Radius in km', schema: new OA\Schema(type: 'integer', default: 5)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Nearby properties'),
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
}
