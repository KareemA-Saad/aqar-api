<?php

declare(strict_types=1);

namespace Modules\Portfolio\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Portfolio\Http\Requests\PortfolioCategoryRequest;
use Modules\Portfolio\Http\Resources\PortfolioCategoryResource;
use Modules\Portfolio\Services\PortfolioCategoryService;
use Modules\Portfolio\Entities\PortfolioCategory;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Portfolio Categories', description: 'Portfolio category management endpoints')]
class PortfolioCategoryController extends Controller
{
    public function __construct(
        private readonly PortfolioCategoryService $categoryService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/portfolio-categories',
        summary: 'Get paginated list of portfolio categories',
        tags: ['Admin - Portfolio Categories'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'title', 'created_at'])),
            new OA\Parameter(name: 'sort_order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Categories retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PortfolioCategoryResource')),
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(property: 'links', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'status', 'sort_by', 'sort_order']);
        $perPage = (int) $request->get('per_page', 15);
        
        $categories = $this->categoryService->getCategories($filters, $perPage);
        
        return response()->json(PortfolioCategoryResource::collection($categories));
    }

    #[OA\Post(
        path: '/api/v1/admin/portfolio-categories',
        summary: 'Create a new portfolio category',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PortfolioCategoryRequest')
        ),
        tags: ['Admin - Portfolio Categories'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Category created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/PortfolioCategoryResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(PortfolioCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request->validated());
        
        return response()->json([
            'message' => 'Portfolio category created successfully',
            'data' => PortfolioCategoryResource::make($category),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/portfolio-categories/{id}',
        summary: 'Get a specific portfolio category',
        tags: ['Admin - Portfolio Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/PortfolioCategoryResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Category not found'),
        ]
    )]
    public function show(PortfolioCategory $portfolioCategory): JsonResponse
    {
        return response()->json([
            'data' => PortfolioCategoryResource::make($portfolioCategory),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/portfolio-categories/{id}',
        summary: 'Update a portfolio category',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PortfolioCategoryRequest')
        ),
        tags: ['Admin - Portfolio Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/PortfolioCategoryResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Category not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(PortfolioCategoryRequest $request, PortfolioCategory $portfolioCategory): JsonResponse
    {
        $category = $this->categoryService->updateCategory($portfolioCategory, $request->validated());
        
        return response()->json([
            'message' => 'Portfolio category updated successfully',
            'data' => PortfolioCategoryResource::make($category),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/portfolio-categories/{id}',
        summary: 'Delete a portfolio category',
        tags: ['Admin - Portfolio Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Category not found'),
            new OA\Response(response: 400, description: 'Cannot delete category with associated portfolios'),
        ]
    )]
    public function destroy(PortfolioCategory $portfolioCategory): JsonResponse
    {
        try {
            $this->categoryService->deleteCategory($portfolioCategory);
            
            return response()->json([
                'message' => 'Portfolio category deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
