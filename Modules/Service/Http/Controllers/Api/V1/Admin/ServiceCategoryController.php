<?php

declare(strict_types=1);

namespace Modules\Service\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Service\Http\Requests\ServiceCategoryRequest;
use Modules\Service\Http\Requests\BulkServiceCategoryRequest;
use Modules\Service\Http\Resources\ServiceCategoryResource;
use Modules\Service\Services\ServiceCategoryService;
use Modules\Service\Entities\ServiceCategory;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Service Categories', description: 'Service category management endpoints')]
class ServiceCategoryController extends Controller
{
    public function __construct(
        private readonly ServiceCategoryService $categoryService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/service-categories',
        summary: 'Get paginated list of service categories',
        tags: ['Admin - Service Categories'],
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
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ServiceCategoryResource')),
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
        
        return response()->json(ServiceCategoryResource::collection($categories));
    }

    #[OA\Post(
        path: '/api/v1/admin/service-categories',
        summary: 'Create a new service category',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ServiceCategoryRequest')
        ),
        tags: ['Admin - Service Categories'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Category created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/ServiceCategoryResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(ServiceCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory($request->validated());
        
        return response()->json([
            'message' => 'Service category created successfully',
            'data' => ServiceCategoryResource::make($category),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/service-categories/{id}',
        summary: 'Get a specific service category',
        tags: ['Admin - Service Categories'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Category retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/ServiceCategoryResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Category not found'),
        ]
    )]
    public function show(ServiceCategory $serviceCategory): JsonResponse
    {
        return response()->json([
            'data' => ServiceCategoryResource::make($serviceCategory),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/service-categories/{id}',
        summary: 'Update a service category',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ServiceCategoryRequest')
        ),
        tags: ['Admin - Service Categories'],
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
                        new OA\Property(property: 'data', ref: '#/components/schemas/ServiceCategoryResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Category not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(ServiceCategoryRequest $request, ServiceCategory $serviceCategory): JsonResponse
    {
        $category = $this->categoryService->updateCategory($serviceCategory, $request->validated());
        
        return response()->json([
            'message' => 'Service category updated successfully',
            'data' => ServiceCategoryResource::make($category),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/service-categories/{id}',
        summary: 'Delete a service category',
        tags: ['Admin - Service Categories'],
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
            new OA\Response(response: 400, description: 'Cannot delete category with associated services'),
        ]
    )]
    public function destroy(ServiceCategory $serviceCategory): JsonResponse
    {
        try {
            $this->categoryService->deleteCategory($serviceCategory);
            
            return response()->json([
                'message' => 'Service category deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    #[OA\Post(
        path: '/api/v1/admin/service-categories/bulk',
        summary: 'Perform bulk actions on service categories',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BulkServiceCategoryRequest')
        ),
        tags: ['Admin - Service Categories'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Bulk action completed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'processed', type: 'integer'),
                        new OA\Property(property: 'failed', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function bulkAction(BulkServiceCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $categories = ServiceCategory::whereIn('id', $validated['ids'])->get();
        
        $processed = 0;
        $failed = 0;
        
        foreach ($categories as $category) {
            try {
                match ($validated['action']) {
                    'delete' => $this->categoryService->deleteCategory($category),
                };
                $processed++;
            } catch (\Exception $e) {
                $failed++;
                \Log::warning('Failed to delete service category in bulk action', [
                    'category_id' => $category->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return response()->json([
            'message' => "Bulk action '{$validated['action']}' completed: {$processed} processed, {$failed} failed",
            'processed' => $processed,
            'failed' => $failed,
        ]);
    }
}
