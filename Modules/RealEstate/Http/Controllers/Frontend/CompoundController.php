<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Services\CompoundService;
use Modules\RealEstate\Services\PropertyService;
use Modules\RealEstate\Transformers\CompoundCollection;
use Modules\RealEstate\Transformers\CompoundResource;
use Modules\RealEstate\Transformers\PropertyResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Compounds', description: 'Public compound/project listing endpoints')]
class CompoundController extends Controller
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
        tags: ['Compounds'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Compound details'),
            new OA\Response(response: 404, description: 'Compound not found'),
        ]
    )]
    public function show(int|string $id, string $slug): JsonResponse
    {
        $compound = $this->compoundService->getCompoundByIdAndSlug($id, $slug);
        
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
        path: '/api/v1/tenant/{tenant}/realestate/compounds/{id}/properties',
        summary: 'Get properties in compound',
        tags: ['Compounds'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Compound properties'),
        ]
    )]
    public function properties(int $id, Request $request): JsonResponse
    {
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
