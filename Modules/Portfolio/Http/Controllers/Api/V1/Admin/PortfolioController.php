<?php

declare(strict_types=1);

namespace Modules\Portfolio\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Portfolio\Http\Requests\BulkPortfolioRequest;
use Modules\Portfolio\Http\Requests\StorePortfolioRequest;
use Modules\Portfolio\Http\Requests\UpdatePortfolioRequest;
use Modules\Portfolio\Http\Resources\PortfolioResource;
use Modules\Portfolio\Services\PortfolioService;
use Modules\Portfolio\Entities\Portfolio;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Portfolios', description: 'Portfolio management endpoints')]
class PortfolioController extends Controller
{
    public function __construct(
        private readonly PortfolioService $portfolioService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/portfolios',
        summary: 'Get paginated list of portfolios',
        tags: ['Admin - Portfolios'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'tag', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['title', 'client', 'created_at', 'updated_at'])),
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
        $filters = $request->only(['search', 'category_id', 'status', 'tag', 'sort_by', 'sort_order']);
        $perPage = (int) $request->get('per_page', 15);
        
        $portfolios = $this->portfolioService->getPortfolios($filters, $perPage);
        
        return response()->json(PortfolioResource::collection($portfolios));
    }

    #[OA\Post(
        path: '/api/v1/admin/portfolios',
        summary: 'Create a new portfolio',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StorePortfolioRequest')
        ),
        tags: ['Admin - Portfolios'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Portfolio created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/PortfolioResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StorePortfolioRequest $request): JsonResponse
    {
        $portfolio = $this->portfolioService->createPortfolio($request->validated());
        
        return response()->json([
            'message' => 'Portfolio created successfully',
            'data' => PortfolioResource::make($portfolio),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/portfolios/{id}',
        summary: 'Get a specific portfolio',
        tags: ['Admin - Portfolios'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Portfolio retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/PortfolioResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Portfolio not found'),
        ]
    )]
    public function show(Portfolio $portfolio): JsonResponse
    {
        $portfolio->load(['category', 'metainfo']);
        
        return response()->json([
            'data' => PortfolioResource::make($portfolio),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/portfolios/{id}',
        summary: 'Update a portfolio',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdatePortfolioRequest')
        ),
        tags: ['Admin - Portfolios'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Portfolio updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/PortfolioResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Portfolio not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdatePortfolioRequest $request, Portfolio $portfolio): JsonResponse
    {
        $portfolio = $this->portfolioService->updatePortfolio($portfolio, $request->validated());
        
        return response()->json([
            'message' => 'Portfolio updated successfully',
            'data' => PortfolioResource::make($portfolio),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/portfolios/{id}',
        summary: 'Delete a portfolio',
        tags: ['Admin - Portfolios'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Portfolio deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Portfolio not found'),
        ]
    )]
    public function destroy(Portfolio $portfolio): JsonResponse
    {
        $this->portfolioService->deletePortfolio($portfolio);
        
        return response()->json([
            'message' => 'Portfolio deleted successfully',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/portfolios/{id}/clone',
        summary: 'Clone a portfolio',
        tags: ['Admin - Portfolios'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Portfolio cloned successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/PortfolioResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Portfolio not found'),
        ]
    )]
    public function clone(Portfolio $portfolio): JsonResponse
    {
        $clonedPortfolio = $this->portfolioService->clonePortfolio($portfolio);
        
        return response()->json([
            'message' => 'Portfolio cloned successfully',
            'data' => PortfolioResource::make($clonedPortfolio),
        ], 201);
    }

    #[OA\Post(
        path: '/api/v1/admin/portfolios/bulk',
        summary: 'Perform bulk actions on portfolios',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BulkPortfolioRequest')
        ),
        tags: ['Admin - Portfolios'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Bulk action completed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'processed', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function bulkAction(BulkPortfolioRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $portfolios = Portfolio::whereIn('id', $validated['ids'])->get();
        
        $processed = 0;
        
        foreach ($portfolios as $portfolio) {
            match ($validated['action']) {
                'delete' => $this->portfolioService->deletePortfolio($portfolio),
                'activate' => $portfolio->update(['status' => true]),
                'deactivate' => $portfolio->update(['status' => false]),
            };
            $processed++;
        }
        
        return response()->json([
            'message' => "Bulk action '{$validated['action']}' completed successfully",
            'processed' => $processed,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/portfolios/tags/all',
        summary: 'Get all unique tags',
        tags: ['Admin - Portfolios'],
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
    public function allTags(): JsonResponse
    {
        $tags = $this->portfolioService->getAllTags();
        
        return response()->json([
            'data' => $tags,
        ]);
    }
}
