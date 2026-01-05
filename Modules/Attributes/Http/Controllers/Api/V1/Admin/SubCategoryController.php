<?php

declare(strict_types=1);

namespace Modules\Attributes\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Attributes\Entities\SubCategory;
use Modules\Attributes\Http\Resources\SubCategoryResource;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Admin Sub-Category Controller
 */
#[OA\Tag(name: 'Admin - Sub-Categories', description: 'Sub-category management endpoints')]
class SubCategoryController extends Controller
{
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/sub-categories',
        summary: 'List all sub-categories',
        tags: ['Admin - Sub-Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [new OA\Response(response: 200, description: 'Sub-categories retrieved')]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = SubCategory::with(['category', 'image', 'status']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('search')) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        $subCategories = $query->orderBy('created_at', 'desc')->paginate($request->input('per_page', 15));

        return response()->json([
            'success' => true,
            'message' => __('Sub-categories retrieved successfully'),
            'data' => SubCategoryResource::collection($subCategories),
            'meta' => [
                'current_page' => $subCategories->currentPage(),
                'last_page' => $subCategories->lastPage(),
                'per_page' => $subCategories->perPage(),
                'total' => $subCategories->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/sub-categories',
        summary: 'Create a new sub-category',
        tags: ['Admin - Sub-Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'category_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'category_id', type: 'integer'),
                    new OA\Property(property: 'slug', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'image_id', type: 'integer'),
                    new OA\Property(property: 'status_id', type: 'integer')
                ]
            )
        ),
        responses: [new OA\Response(response: 201, description: 'Sub-category created')]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image_id' => 'nullable|exists:media_uploaders,id',
            'status_id' => 'nullable|exists:statuses,id',
        ]);

        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $subCategory = SubCategory::create($data);
        $subCategory->load(['category', 'image', 'status']);

        return response()->json([
            'success' => true,
            'message' => __('Sub-category created successfully'),
            'data' => new SubCategoryResource($subCategory),
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/sub-categories/{id}',
        summary: 'Get a specific sub-category',
        tags: ['Admin - Sub-Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Sub-category retrieved')]
    )]
    public function show(int $id): JsonResponse
    {
        $subCategory = SubCategory::with(['category', 'image', 'status'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => __('Sub-category retrieved successfully'),
            'data' => new SubCategoryResource($subCategory),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/sub-categories/{id}',
        summary: 'Update a sub-category',
        tags: ['Admin - Sub-Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(content: new OA\JsonContent()),
        responses: [new OA\Response(response: 200, description: 'Sub-category updated')]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $subCategory = SubCategory::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image_id' => 'nullable|exists:media_uploaders,id',
            'status_id' => 'nullable|exists:statuses,id',
        ]);

        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $subCategory->update($data);
        $subCategory->load(['category', 'image', 'status']);

        return response()->json([
            'success' => true,
            'message' => __('Sub-category updated successfully'),
            'data' => new SubCategoryResource($subCategory),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/sub-categories/{id}',
        summary: 'Delete a sub-category',
        tags: ['Admin - Sub-Categories'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Tenant-Token', in: 'header', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [new OA\Response(response: 200, description: 'Sub-category deleted')]
    )]
    public function destroy(int $id): JsonResponse
    {
        SubCategory::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => __('Sub-category deleted successfully'),
        ]);
    }
}
