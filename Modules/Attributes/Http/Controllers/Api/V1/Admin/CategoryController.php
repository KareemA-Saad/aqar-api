<?php

declare(strict_types=1);

namespace Modules\Attributes\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Attributes\Entities\Category;
use Modules\Attributes\Http\Resources\CategoryResource;
use Modules\Attributes\Http\Requests\StoreCategoryRequest;
use Modules\Attributes\Http\Requests\UpdateCategoryRequest;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Admin Category Controller
 *
 * Handles CRUD operations for product categories.
 *
 * @package Modules\Attributes\Http\Controllers\Api\V1\Admin
 */
#[OA\Tag(name: 'Admin - Categories', description: 'Category management endpoints')]
class CategoryController extends Controller
{
    /**
     * List all categories
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/categories',
        summary: 'List all categories',
        description: 'Returns paginated list of categories',
        tags: ['Admin - Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive']))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Categories retrieved successfully'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Category::with(['image', 'status']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $query->whereHas('status', fn($q) => $q->where('name', $request->status));
        }

        $perPage = $request->input('per_page', 15);
        $categories = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => __('Categories retrieved successfully'),
            'data' => CategoryResource::collection($categories),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    /**
     * Create a new category
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/categories',
        summary: 'Create a new category',
        tags: ['Admin - Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Electronics'),
                    new OA\Property(property: 'slug', type: 'string', example: 'electronics'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'image_id', type: 'integer'),
                    new OA\Property(property: 'status_id', type: 'integer', example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Category created successfully'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        $category = Category::create($data);
        $category->load(['image', 'status']);

        return response()->json([
            'success' => true,
            'message' => __('Category created successfully'),
            'data' => new CategoryResource($category),
        ], 201);
    }

    /**
     * Get a specific category
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/categories/{id}',
        summary: 'Get a specific category',
        tags: ['Admin - Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category retrieved successfully'),
            new OA\Response(response: 404, description: 'Category not found')
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $category = Category::with(['image', 'status'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => __('Category retrieved successfully'),
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Update a category
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/categories/{id}',
        summary: 'Update a category',
        tags: ['Admin - Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'slug', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'image_id', type: 'integer'),
                    new OA\Property(property: 'status_id', type: 'integer')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Category updated successfully'),
            new OA\Response(response: 404, description: 'Category not found')
        ]
    )]
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $data = $request->validated();

        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);
        $category->load(['image', 'status']);

        return response()->json([
            'success' => true,
            'message' => __('Category updated successfully'),
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Delete a category
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/categories/{id}',
        summary: 'Delete a category',
        tags: ['Admin - Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Category deleted successfully'),
            new OA\Response(response: 404, description: 'Category not found')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => __('Category deleted successfully'),
        ]);
    }

    /**
     * Bulk delete categories
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/categories/bulk-delete',
        summary: 'Bulk delete categories',
        tags: ['Admin - Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids'],
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'))
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Categories deleted successfully')
        ]
    )]
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        $deleted = Category::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => __(':count categories deleted successfully', ['count' => $deleted]),
        ]);
    }
}
