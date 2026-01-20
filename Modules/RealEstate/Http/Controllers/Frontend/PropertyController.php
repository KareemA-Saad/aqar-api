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

#[OA\Tag(name: 'Properties', description: 'Public property listing endpoints')]
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
        tags: ['Properties'],
        parameters: [
            new OA\Parameter(name: 'filter[area_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[compound_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[property_type_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[purpose]', in: 'query', schema: new OA\Schema(type: 'string', enum: ['sale', 'rent'])),
            new OA\Parameter(name: 'filter[price_range]', in: 'query', description: 'min,max', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'filter[bedrooms]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of properties'),
        ]
    )]
    public function index(Request $request): PropertyCollection
    {
        $properties = $this->propertyService->getPaginatedProperties($request->all());
        
        return new PropertyCollection($properties);
    }

    /**
     * Show a single property by ID-slug.
     */
    #[OA\Get(
        path: '/api/realestate/properties/{id}-{slug}',
        summary: 'Get property details',
        tags: ['Properties'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Property details'),
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
        tags: ['Properties'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Featured properties'),
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
        tags: ['Properties'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 6)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Similar properties'),
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
