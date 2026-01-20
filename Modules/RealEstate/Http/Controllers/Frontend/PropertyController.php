<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Services\PropertyService;
use Modules\RealEstate\Transformers\PropertyCollection;
use Modules\RealEstate\Transformers\PropertyResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Properties', description: 'Public property listing and detail endpoints')]
class PropertyController extends Controller
{
    public function __construct(
        protected PropertyService $propertyService
    ) {}

    /**
     * List active properties with filters.
     */
    #[OA\Get(
        path: '/api/realestate/properties',
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
        path: '/api/realestate/properties/{property}',
        summary: 'Get property details',
        description: 'Get detailed property information by ID-slug pattern (e.g., 123-modern-villa-in-new-cairo)',
        tags: ['Properties'],
        parameters: [
            new OA\Parameter(
                name: 'property',
                in: 'path',
                required: true,
                description: 'Property ID-slug (format: {id}-{slug})',
                schema: new OA\Schema(type: 'string', example: '123-modern-villa-in-new-cairo')
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
    public function show(int $id, string $slug): JsonResponse
    {
        $property = $this->propertyService->getPropertyByIdAndSlug($id, $slug);
        
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
        path: '/api/realestate/properties/featured',
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
        path: '/api/realestate/properties/{id}/similar',
        summary: 'Get similar properties',
        description: 'Get properties similar to the specified property based on location, type, and price range',
        tags: ['Properties'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, description: 'Property ID', schema: new OA\Schema(type: 'integer')),
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
    public function similar(int $id, Request $request): JsonResponse
    {
        $property = $this->propertyService->getProperty($id);
        
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
