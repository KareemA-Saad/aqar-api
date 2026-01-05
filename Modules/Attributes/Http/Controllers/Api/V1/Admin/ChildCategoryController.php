<?php

declare(strict_types=1);

namespace Modules\Attributes\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Attributes\Entities\ChildCategory;
use Modules\Attributes\Http\Resources\ChildCategoryResource;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Admin Child-Category Controller
 */
#[OA\Tag(name: 'Admin - Child-Categories', description: 'Child-category management endpoints')]
class ChildCategoryController extends Controller
{
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/child-categories',
        summary: 'List all child-categories',
        tags: ['Admin - Child-Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sub_category_id', in: 'query', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [new OA\Response(response: 200, description: 'Child-categories retrieved')]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = ChildCategory::with(['category', 'sub_category', 'image', 'status']);

        if ($request->filled('sub_category_id')) {
            $query->where('sub_category_id', $request->sub_category_id);
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $childCategories = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => __('Child-categories retrieved successfully'),
            'data' => ChildCategoryResource::collection($childCategories),
            'meta' => [
                'current_page' => $childCategories->currentPage(),
                'last_page' => $childCategories->lastPage(),
                'per_page' => $childCategories->perPage(),
                'total' => $childCategories->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/child-categories',
        summary: 'Create a new child-category',
        tags: ['Admin - Child-Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'category_id', 'sub_category_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'category_id', type: 'integer'),
                    new OA\Property(property: 'sub_category_id', type: 'integer'),
                    new OA\Property(property: 'slug', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'image_id', type: 'integer'),
                    new OA\Property(property: 'status_id', type: 'integer')
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Child-category created')]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'required|exists:sub_categories,id',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image_id' => 'nullable|exists:media_uploaders,id',
            'status_id' => 'nullable|exists:statuses,id',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $childCategory = ChildCategory::create($data);
        $childCategory->load(['category', 'sub_category', 'image', 'status']);

        return response()->json([
            'success' => true,
            'message' => __('Child-category created successfully'),
            'data' => new ChildCategoryResource($childCategory),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/child-categories/{id}',
        summary: 'Get a specific child-category',
        tags: ['Admin - Child-Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Child-category retrieved')]
    )]
    public function show(int $id): JsonResponse
    {
        $childCategory = ChildCategory::with(['category', 'sub_category', 'image', 'status'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => __('Child-category retrieved successfully'),
            'data' => new ChildCategoryResource($childCategory),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/child-categories/{id}',
        summary: 'Update a child-category',
        tags: ['Admin - Child-Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(content: new OA\JsonContent()),
        responses: [new OA\Response(response: 200, description: 'Child-category updated')]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $childCategory = ChildCategory::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'sub_category_id' => 'sometimes|exists:sub_categories,id',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image_id' => 'nullable|exists:media_uploaders,id',
            'status_id' => 'nullable|exists:statuses,id',
        ]);

        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $childCategory->update($data);
        $childCategory->load(['category', 'sub_category', 'image', 'status']);

        return response()->json([
            'success' => true,
            'message' => __('Child-category updated successfully'),
            'data' => new ChildCategoryResource($childCategory),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/child-categories/{id}',
        summary: 'Delete a child-category',
        tags: ['Admin - Child-Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Child-category deleted')]
    )]
    public function destroy(int $id): JsonResponse
    {
        ChildCategory::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => __('Child-category deleted successfully'),
        ]);
    }
}
