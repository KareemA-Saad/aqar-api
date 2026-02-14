<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use Modules\RealEstate\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\RealEstate\Entities\Compound;
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
        description: 'Get detailed information about a specific compound using ID-slug (e.g., "123-compound-name") or slug only (e.g., "compound-name")',
        tags: ['Compounds'],
        parameters: [
            new OA\Parameter(name: 'compound', in: 'path', required: true, description: 'Compound identifier: ID-slug ("123-slug") or slug only ("slug")', schema: new OA\Schema(type: 'string', example: 'mivida-compound')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Compound details'),
            new OA\Response(response: 404, description: 'Compound not found'),
        ]
    )]
    public function show(string $compound): JsonResponse
    {
        // Decode URL-encoded characters (spaces, special chars)
        $compound = urldecode($compound);
        
        // Normalize slug: convert to lowercase and replace spaces with hyphens
        $normalizedSlug = Str::slug($compound);
        
        // Support both formats: "123-compound-slug" or "compound-slug"
        if (preg_match('/^(\d+)-(.+)$/', $compound, $matches)) {
            // ID-slug format: validate both ID and slug
            $id = (int) $matches[1];
            $slug = $matches[2];
            // Try original slug first, then normalized
            $compound = $this->compoundService->getCompoundByIdAndSlug($id, $slug)
                ?? $this->compoundService->getCompoundByIdAndSlug($id, Str::slug($slug));
        } else {
            // Slug-only format: try original, then normalized, then fuzzy match
            $compound = $this->compoundService->getCompoundBySlug($compound)
                ?? $this->compoundService->getCompoundBySlug($normalizedSlug)
                ?? Compound::where('slug', 'LIKE', '%' . $compound . '%')->active()->first();
        }
        
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
            new OA\Parameter(name: 'compound', in: 'path', required: true, description: 'Compound identifier: ID-slug ("123-slug") or slug only ("slug")', schema: new OA\Schema(type: 'string', example: 'mivida-compound')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Compound properties'),
        ]
    )]
    public function properties(string $compound, Request $request): JsonResponse
    {
        // Decode URL-encoded characters (spaces, special chars)
        $compound = urldecode($compound);
        
        // Normalize slug: convert to lowercase and replace spaces with hyphens
        $normalizedSlug = Str::slug($compound);
        
        // Support both formats: "123-compound-slug" or "compound-slug"
        if (preg_match('/^(\d+)-(.+)$/', $compound, $matches)) {
            // ID-slug format: validate both ID and slug
            $id = (int) $matches[1];
            $slug = $matches[2];
            // Try original slug first, then normalized
            $compound = $this->compoundService->getCompoundByIdAndSlug($id, $slug)
                ?? $this->compoundService->getCompoundByIdAndSlug($id, Str::slug($slug));
        } else {
            // Slug-only format: try original, then normalized, then fuzzy match
            $compound = $this->compoundService->getCompoundBySlug($compound)
                ?? $this->compoundService->getCompoundBySlug($normalizedSlug)
                ?? Compound::where('slug', 'LIKE', '%' . $compound . '%')->active()->first();
        }
        
        if (!$compound) {
            return response()->json(['message' => 'Compound not found.'], 404);
        }
        
        $id = $compound->id;
        
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
