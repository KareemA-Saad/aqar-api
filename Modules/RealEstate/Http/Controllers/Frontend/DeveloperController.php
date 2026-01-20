<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\Developer;
use Modules\RealEstate\Services\CompoundService;
use Modules\RealEstate\Transformers\CompoundResource;
use Modules\RealEstate\Transformers\DeveloperResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Developers', description: 'Public developer listing endpoints')]
class DeveloperController extends Controller
{
    public function __construct(
        protected CompoundService $compoundService
    ) {}

    /**
     * List active developers.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/developers',
        summary: 'List developers',
        tags: ['Developers'],
        parameters: [
            new OA\Parameter(name: 'featured', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of developers'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Developer::active()
            ->withCount(['compounds', 'properties']);
        
        if ($request->boolean('featured')) {
            $query->featured();
        }
        
        $developers = $query->orderBy('name')
            ->paginate($request->input('per_page', 15));
        
        return response()->json([
            'data' => DeveloperResource::collection($developers),
            'meta' => [
                'total' => $developers->total(),
                'per_page' => $developers->perPage(),
                'current_page' => $developers->currentPage(),
                'last_page' => $developers->lastPage(),
            ],
        ]);
    }

    /**
     * Get featured developers.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/developers/featured',
        summary: 'Get featured developers',
        tags: ['Developers'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Featured developers'),
        ]
    )]
    public function featured(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        
        $developers = Developer::active()
            ->featured()
            ->withCount(['compounds', 'properties'])
            ->limit($limit)
            ->get();
        
        return response()->json([
            'data' => DeveloperResource::collection($developers),
        ]);
    }

    /**
     * Show a single developer.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/developers/{id}-{slug}',
        summary: 'Get developer details',
        tags: ['Developers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Developer details'),
            new OA\Response(response: 404, description: 'Developer not found'),
        ]
    )]
    public function show(int $id, string $slug): JsonResponse
    {
        $developer = Developer::where('id', $id)
            ->where('slug', $slug)
            ->active()
            ->withCount(['compounds', 'properties'])
            ->first();
        
        if (!$developer) {
            return response()->json(['message' => 'Developer not found.'], 404);
        }
        
        return response()->json([
            'data' => new DeveloperResource($developer),
        ]);
    }

    /**
     * Get compounds by developer.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/developers/{id}/compounds',
        summary: 'Get developer compounds',
        tags: ['Developers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Developer compounds'),
        ]
    )]
    public function compounds(int $id, Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);
        $compounds = $this->compoundService->getCompoundsByDeveloper($id, $limit);
        
        return response()->json([
            'data' => CompoundResource::collection($compounds),
        ]);
    }
}
