<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Services\AreaService;
use Modules\RealEstate\Services\CompoundService;
use Modules\RealEstate\Services\PropertyService;
use Modules\RealEstate\Transformers\AreaResource;
use Modules\RealEstate\Transformers\CompoundResource;
use Modules\RealEstate\Transformers\PropertyResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Areas', description: 'Public area/location listing endpoints')]
class AreaController extends Controller
{
    public function __construct(
        protected AreaService $areaService,
        protected CompoundService $compoundService,
        protected PropertyService $propertyService
    ) {}

    /**
     * Get areas tree structure.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/areas',
        summary: 'Get areas tree',
        tags: ['Areas'],
        responses: [
            new OA\Response(response: 200, description: 'Areas tree structure'),
        ]
    )]
    public function index(): JsonResponse
    {
        $areas = $this->areaService->getAreasTree();
        
        return response()->json([
            'data' => AreaResource::collection($areas),
        ]);
    }

    /**
     * Get root areas (cities).
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/areas/cities',
        summary: 'Get root areas/cities',
        tags: ['Areas'],
        responses: [
            new OA\Response(response: 200, description: 'Root areas'),
        ]
    )]
    public function cities(): JsonResponse
    {
        $areas = $this->areaService->getRootAreas();
        
        return response()->json([
            'data' => AreaResource::collection($areas),
        ]);
    }

    /**
     * Get featured areas.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/areas/featured',
        summary: 'Get featured areas',
        tags: ['Areas'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Featured areas'),
        ]
    )]
    public function featured(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $areas = $this->areaService->getFeaturedAreas($limit);
        
        return response()->json([
            'data' => AreaResource::collection($areas),
        ]);
    }

    /**
     * Show a single area by ID-slug.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/areas/{id}-{slug}',
        summary: 'Get area details',
        tags: ['Areas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Area details'),
            new OA\Response(response: 404, description: 'Area not found'),
        ]
    )]
    public function show(int $id, string $slug): JsonResponse
    {
        $area = $this->areaService->getAreaByIdAndSlug($id, $slug);
        
        if (!$area) {
            return response()->json(['message' => 'Area not found.'], 404);
        }
        
        // Get breadcrumbs
        $breadcrumbs = $this->areaService->getBreadcrumbs($area);
        
        return response()->json([
            'data' => new AreaResource($area),
            'breadcrumbs' => $breadcrumbs,
        ]);
    }

    /**
     * Get child areas.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/areas/{id}/children',
        summary: 'Get child areas',
        tags: ['Areas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Child areas'),
        ]
    )]
    public function children(int $id): JsonResponse
    {
        $children = $this->areaService->getChildAreas($id);
        
        return response()->json([
            'data' => AreaResource::collection($children),
        ]);
    }

    /**
     * Get compounds in area.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/areas/{id}/compounds',
        summary: 'Get compounds in area',
        tags: ['Areas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Compounds in area'),
        ]
    )]
    public function compounds(int $id, Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $compounds = $this->compoundService->getCompoundsByArea($id, $limit);
        
        return response()->json([
            'data' => CompoundResource::collection($compounds),
        ]);
    }

    /**
     * Get properties in area.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/areas/{id}/properties',
        summary: 'Get properties in area',
        tags: ['Areas'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Properties in area'),
        ]
    )]
    public function properties(int $id, Request $request): JsonResponse
    {
        $request->merge(['filter' => ['area_id' => $id]]);
        $properties = $this->propertyService->getPaginatedProperties($request->all());
        
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
}
