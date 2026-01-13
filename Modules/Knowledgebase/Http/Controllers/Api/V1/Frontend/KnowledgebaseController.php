<?php

declare(strict_types=1);

namespace Modules\Knowledgebase\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Knowledgebase\Http\Resources\KnowledgebaseResource;
use Modules\Knowledgebase\Http\Resources\KnowledgebaseCategoryResource;
use Modules\Knowledgebase\Services\KnowledgebaseService;
use Modules\Knowledgebase\Services\KnowledgebaseCategoryService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Frontend - Knowledgebase', description: 'Public knowledgebase browsing endpoints')]
class KnowledgebaseController extends Controller
{
    public function __construct(
        private readonly KnowledgebaseService $knowledgebaseService,
        private readonly KnowledgebaseCategoryService $categoryService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/frontend/knowledgebases',
        summary: 'Get published knowledgebase articles',
        tags: ['Frontend - Knowledgebase'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['title', 'views', 'created_at'])),
            new OA\Parameter(name: 'sort_order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Knowledgebase articles retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/KnowledgebaseResource')),
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(property: 'links', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'category_id', 'sort_by', 'sort_order']);
        $perPage = (int) $request->get('per_page', 15);
        
        $knowledgebases = $this->knowledgebaseService->getPublishedKnowledgebases($filters, $perPage);
        
        return response()->json(KnowledgebaseResource::collection($knowledgebases));
    }

    #[OA\Get(
        path: '/api/v1/frontend/knowledgebases/{slug}',
        summary: 'Get a specific knowledgebase article by slug',
        tags: ['Frontend - Knowledgebase'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Knowledgebase article retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/KnowledgebaseResource'),
                        new OA\Property(
                            property: 'related',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/KnowledgebaseResource')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Knowledgebase article not found'),
        ]
    )]
    public function show(string $slug): JsonResponse
    {
        $knowledgebase = $this->knowledgebaseService->getKnowledgebaseBySlug($slug);
        
        if (!$knowledgebase) {
            return response()->json([
                'message' => 'Knowledgebase article not found',
            ], 404);
        }
        
        // Increment view count
        $this->knowledgebaseService->incrementViews($knowledgebase);
        
        $related = $this->knowledgebaseService->getRelatedKnowledgebases($knowledgebase, 4);
        
        return response()->json([
            'data' => KnowledgebaseResource::make($knowledgebase),
            'related' => KnowledgebaseResource::collection($related),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/frontend/knowledgebases/category/{categoryId}',
        summary: 'Get knowledgebase articles by category',
        tags: ['Frontend - Knowledgebase'],
        parameters: [
            new OA\Parameter(name: 'categoryId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Knowledgebase articles retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/KnowledgebaseResource')),
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(property: 'links', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function byCategory(int $categoryId, Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $knowledgebases = $this->knowledgebaseService->getKnowledgebasesByCategory($categoryId, $perPage);
        
        return response()->json(KnowledgebaseResource::collection($knowledgebases));
    }

    #[OA\Get(
        path: '/api/v1/frontend/knowledgebases/search/{query}',
        summary: 'Search knowledgebase articles',
        tags: ['Frontend - Knowledgebase'],
        parameters: [
            new OA\Parameter(name: 'query', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/KnowledgebaseResource')),
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(property: 'links', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function search(string $query, Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $knowledgebases = $this->knowledgebaseService->searchKnowledgebases($query, $perPage);
        
        return response()->json(KnowledgebaseResource::collection($knowledgebases));
    }

    #[OA\Get(
        path: '/api/v1/frontend/knowledgebase-categories',
        summary: 'Get all active knowledgebase categories',
        tags: ['Frontend - Knowledgebase'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categories retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/KnowledgebaseCategoryResource')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function categories(): JsonResponse
    {
        $categories = $this->categoryService->getActiveCategories();
        
        return response()->json([
            'data' => KnowledgebaseCategoryResource::collection($categories),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/frontend/knowledgebases/popular/list',
        summary: 'Get popular knowledgebase articles',
        tags: ['Frontend - Knowledgebase'],
        parameters: [
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 10)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Popular articles retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/KnowledgebaseResource')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function popular(Request $request): JsonResponse
    {
        $limit = (int) $request->get('limit', 10);
        $popular = $this->knowledgebaseService->getPopularKnowledgebases($limit);
        
        return response()->json([
            'data' => KnowledgebaseResource::collection($popular),
        ]);
    }
}
