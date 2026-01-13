<?php

declare(strict_types=1);

namespace Modules\Knowledgebase\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Knowledgebase\Http\Requests\KnowledgebaseCategoryRequest;
use Modules\Knowledgebase\Http\Resources\KnowledgebaseCategoryResource;
use Modules\Knowledgebase\Services\KnowledgebaseCategoryService;
use Modules\Knowledgebase\Entities\KnowledgebaseCategory;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Knowledgebase Categories', description: 'Knowledgebase category management endpoints')]
class KnowledgebaseCategoryController extends Controller
{
    public function __construct(
        private readonly KnowledgebaseCategoryService $categoryService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/knowledgebase-categories',
        summary: 'Get paginated list of knowledgebase categories',
        tags: ['Admin - Knowledgebase Categories'],
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
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/KnowledgebaseCategoryResource')),
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
        
        return response()->json(KnowledgebaseCategoryResource::collection($categories));
    }

    #[OA\Post(
        path: '/api/v1/admin/knowledgebase-categories',
        summary: 'Create a new knowledgebase category',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/KnowledgebaseCategoryRequest')
        ),
        tags: ['Admin - Knowledgebase Categories'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Category created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/KnowledgebaseCategoryResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(KnowledgebaseCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request->validated());
        
        return response()->json([
            'message' => 'Knowledgebase category created successfully',
            'data' => KnowledgebaseCategoryResource::make($category),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/knowledgebase-categories/{id}',
        summary: 'Get a specific knowledgebase category',
        tags: ['Admin - Knowledgebase Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/KnowledgebaseCategoryResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Category not found'),
        ]
    )]
    public function show(KnowledgebaseCategory $knowledgebaseCategory): JsonResponse
    {
        return response()->json([
            'data' => KnowledgebaseCategoryResource::make($knowledgebaseCategory),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/knowledgebase-categories/{id}',
        summary: 'Update a knowledgebase category',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/KnowledgebaseCategoryRequest')
        ),
        tags: ['Admin - Knowledgebase Categories'],
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/KnowledgebaseCategoryResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Category not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(KnowledgebaseCategoryRequest $request, KnowledgebaseCategory $knowledgebaseCategory): JsonResponse
    {
        $category = $this->categoryService->updateCategory($knowledgebaseCategory, $request->validated());
        
        return response()->json([
            'message' => 'Knowledgebase category updated successfully',
            'data' => KnowledgebaseCategoryResource::make($category),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/knowledgebase-categories/{id}',
        summary: 'Delete a knowledgebase category',
        tags: ['Admin - Knowledgebase Categories'],
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
            new OA\Response(response: 400, description: 'Cannot delete category with associated articles'),
        ]
    )]
    public function destroy(KnowledgebaseCategory $knowledgebaseCategory): JsonResponse
    {
        try {
            $this->categoryService->deleteCategory($knowledgebaseCategory);
            
            return response()->json([
                'message' => 'Knowledgebase category deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
