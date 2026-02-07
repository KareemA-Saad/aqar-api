<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use Modules\RealEstate\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Services\CompoundService;
use Modules\RealEstate\Services\PropertyService;
use Modules\RealEstate\Transformers\CompoundCollection;
use Modules\RealEstate\Transformers\CompoundResource;
use Modules\RealEstate\Transformers\PropertyResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Compounds', description: 'Public compound/project listing endpoints')]
class CompoundController extends BaseController
{
    public function __construct(
        protected CompoundService $compoundService,
        protected PropertyService $propertyService
    ) {}

    /**
     * List active compounds with filters.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/compounds',
        summary: 'List compounds',
        tags: ['Compounds'],
        parameters: [
            new OA\Parameter(name: 'filter[area_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[developer_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of compounds'),
        ]
    )]
    public function index(Request $request): CompoundCollection
    {
        $compounds = $this->compoundService->getPaginatedCompounds($request->all());
        
        return new CompoundCollection($compounds);
    }

    /**
     * Show a single compound by ID-slug.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/compounds/{compound}',
        summary: 'Get compound details',
        description: 'Get detailed information about a specific compound using ID-slug format',
        tags: ['Compounds'],
        parameters: [
            new OA\Parameter(name: 'compound', in: 'path', required: true, description: 'Compound in ID-slug format (e.g., 123-compound-name)', schema: new OA\Schema(type: 'string', pattern: '[0-9]+-.*')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Compound details'),
            new OA\Response(response: 404, description: 'Compound not found'),
        ]
    )]
    public function show(string $compound): JsonResponse
    {
        // Parse ID from Nawy-style route: {id}-{slug}
        $id = (int) explode('-', $compound)[0];
        
        $compound = $this->compoundService->getCompound($id);
        
        if (!$compound) {
            return response()->json(['message' => 'Compound not found.'], 404);
        }
        
        return response()->json([
            'data' => new CompoundResource($compound),
        ]);
    }

    /**
     * Get featured compounds.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/compounds/featured',
        summary: 'Get featured compounds',
        tags: ['Compounds'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Featured compounds'),
        ]
    )]
    public function featured(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $compounds = $this->compoundService->getFeaturedCompounds($limit);
        
        return response()->json([
            'data' => CompoundResource::collection($compounds),
        ]);
    }

    /**
     * Get properties in a compound.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/compounds/{compound}/properties',
        summary: 'Get properties in compound',
        description: 'Get all properties within a specific compound',
        tags: ['Compounds'],
        parameters: [
            new OA\Parameter(name: 'compound', in: 'path', required: true, description: 'Compound in ID-slug format', schema: new OA\Schema(type: 'string', pattern: '[0-9]+-.*')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Compound properties'),
        ]
    )]
    public function properties(string $compound, Request $request): JsonResponse
    {
        // Parse ID from Nawy-style route: {id}-{slug}
        $id = (int) explode('-', $compound)[0];
        
        $compound = $this->compoundService->getCompound($id);
        
        if (!$compound) {
            return response()->json(['message' => 'Compound not found.'], 404);
        }
        
        $request->merge(['filter' => ['compound_id' => $id]]);
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
