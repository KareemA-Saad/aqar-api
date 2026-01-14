<?php

declare(strict_types=1);

namespace Modules\Portfolio\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Portfolio\Http\Resources\PortfolioResource;
use Modules\Portfolio\Http\Resources\PortfolioCategoryResource;
use Modules\Portfolio\Services\PortfolioService;
use Modules\Portfolio\Services\PortfolioCategoryService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Frontend - Portfolios', description: 'Public portfolio browsing endpoints')]
class PortfolioController extends Controller
{
    public function __construct(
        private readonly PortfolioService $portfolioService,
        private readonly PortfolioCategoryService $categoryService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/frontend/portfolios',
        summary: 'Get published portfolios',
        tags: ['Frontend - Portfolios'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tag', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['title', 'client', 'created_at'])),
            new OA\Parameter(name: 'sort_order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Portfolios retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PortfolioResource')),
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(property: 'links', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'category_id', 'tag', 'sort_by', 'sort_order']);
        $perPage = (int) $request->get('per_page', 15);
        
        $portfolios = $this->portfolioService->getPublishedPortfolios($filters, $perPage);
        
        return response()->json(PortfolioResource::collection($portfolios));
    }

    #[OA\Get(
        path: '/api/v1/frontend/portfolios/{slug}',
        summary: 'Get a specific portfolio by slug',
        tags: ['Frontend - Portfolios'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Portfolio retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/PortfolioResource'),
                        new OA\Property(
                            property: 'related',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/PortfolioResource')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Portfolio not found'),
        ]
    )]
    public function show(string $slug): JsonResponse
    {
        $portfolio = $this->portfolioService->getPortfolioBySlug($slug);
        
        if (!$portfolio) {
            return response()->json([
                'message' => 'Portfolio not found',
            ], 404);
        }
        
        $related = $this->portfolioService->getRelatedPortfolios($portfolio, 5);
        
        return response()->json([
            'data' => PortfolioResource::make($portfolio),
            'related' => PortfolioResource::collection($related),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/frontend/portfolios/category/{categoryId}',
        summary: 'Get portfolios by category',
        tags: ['Frontend - Portfolios'],
        parameters: [
            new OA\Parameter(name: 'categoryId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Portfolios retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PortfolioResource')),
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
        $portfolios = $this->portfolioService->getPortfoliosByCategory($categoryId, $perPage);
        
        return response()->json(PortfolioResource::collection($portfolios));
    }

    #[OA\Get(
        path: '/api/v1/frontend/portfolios/tag/{tag}',
        summary: 'Get portfolios by tag',
        tags: ['Frontend - Portfolios'],
        parameters: [
            new OA\Parameter(name: 'tag', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Portfolios retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PortfolioResource')),
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(property: 'links', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function byTag(string $tag, Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $portfolios = $this->portfolioService->getPortfoliosByTag($tag, $perPage);
        
        return response()->json(PortfolioResource::collection($portfolios));
    }

    #[OA\Get(
        path: '/api/v1/frontend/portfolios/search/{query}',
        summary: 'Search portfolios',
        tags: ['Frontend - Portfolios'],
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
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PortfolioResource')),
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
        $portfolios = $this->portfolioService->searchPortfolios($query, $perPage);
        
        return response()->json(PortfolioResource::collection($portfolios));
    }

    #[OA\Get(
        path: '/api/v1/frontend/portfolio-categories',
        summary: 'Get all active portfolio categories',
        tags: ['Frontend - Portfolios'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categories retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/PortfolioCategoryResource')
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
            'data' => PortfolioCategoryResource::collection($categories),
        ]);
    }

    #[OA\Get(
        path: '/api/v1/frontend/portfolio-tags',
        summary: 'Get all unique portfolio tags',
        tags: ['Frontend - Portfolios'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tags retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(type: 'string')
                        ),
                    ]
                )
            ),
        ]
    )]
    public function tags(): JsonResponse
    {
        $tags = $this->portfolioService->getAllTags();
        
        return response()->json([
            'data' => $tags,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/frontend/portfolios/{slug}/download',
        summary: 'Increment download counter for portfolio file',
        tags: ['Frontend - Portfolios'],
        parameters: [
            new OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Download counter incremented',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'download_count', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Portfolio not found or no file attached'),
        ]
    )]
    public function download(string $slug): JsonResponse
    {
        $portfolio = $this->portfolioService->getPortfolioBySlug($slug);
        
        if (!$portfolio) {
            return response()->json([
                'message' => 'Portfolio not found',
            ], 404);
        }

        if (empty($portfolio->file)) {
            return response()->json([
                'message' => 'No file attached to this portfolio',
            ], 404);
        }

        $this->portfolioService->incrementDownload($portfolio);
        $portfolio->refresh();
        
        return response()->json([
            'message' => 'Download counter incremented',
            'download_count' => $portfolio->download ?? 0,
        ]);
    }
}
