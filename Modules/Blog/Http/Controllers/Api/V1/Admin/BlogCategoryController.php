<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Blog\Entities\Blog;
use Modules\Blog\Entities\BlogCategory;
use Modules\Blog\Http\Requests\StoreCategoryRequest;
use Modules\Blog\Http\Resources\BlogCategoryResource;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin Blog Category Controller
 *
 * Manages blog categories within a tenant context.
 * Requires tenant admin authentication and tenant context.
 *
 * @package Modules\Blog\Http\Controllers\Api\V1\Admin
 */
#[OA\Tag(
    name: 'Tenant Admin - Blog Categories',
    description: 'Manage blog categories within a tenant'
)]
final class BlogCategoryController extends BaseApiController
{
    /**
     * List all blog categories with post counts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/blog/categories',
        summary: 'List blog categories',
        description: 'Get list of all blog categories with post counts',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Blog Categories']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        description: 'Filter by status',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Response(
        response: 200,
        description: 'Blog categories retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Blog categories retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/BlogCategoryResource')
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(Request $request): JsonResponse
    {
        $query = BlogCategory::query()->withCount('blogs');

        if ($request->has('status')) {
            $query->where('status', $request->boolean('status') ? '1' : '0');
        }

        $categories = $query->orderBy('id', 'desc')->get();

        return $this->success(
            BlogCategoryResource::collection($categories),
            'Blog categories retrieved successfully'
        );
    }

    /**
     * Create a new blog category.
     *
     * @param StoreCategoryRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/blog/categories',
        summary: 'Create blog category',
        description: 'Create a new blog category',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Blog Categories']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StoreCategoryRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Blog category created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Blog category created successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BlogCategoryResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        $category = BlogCategory::create([
            'title' => $data['title'],
            'status' => isset($data['status']) && $data['status'] ? '1' : '0',
        ]);

        return $this->success(
            new BlogCategoryResource($category),
            'Blog category created successfully',
            201
        );
    }

    /**
     * Get a specific blog category.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/blog/categories/{id}',
        summary: 'Get blog category',
        description: 'Get a specific blog category by ID',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Blog Categories']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Category ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Blog category retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Blog category retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BlogCategoryResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Category not found')]
    public function show(int $id): JsonResponse
    {
        $category = BlogCategory::withCount('blogs')->find($id);

        if (!$category) {
            return $this->error('Blog category not found', 404);
        }

        return $this->success(
            new BlogCategoryResource($category),
            'Blog category retrieved successfully'
        );
    }

    /**
     * Update a blog category.
     *
     * @param StoreCategoryRequest $request
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/blog/categories/{id}',
        summary: 'Update blog category',
        description: 'Update an existing blog category',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Blog Categories']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Category ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StoreCategoryRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Blog category updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Blog category updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BlogCategoryResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Category not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function update(StoreCategoryRequest $request, int $id): JsonResponse
    {
        $category = BlogCategory::find($id);

        if (!$category) {
            return $this->error('Blog category not found', 404);
        }

        $data = $request->validated();

        $category->update([
            'title' => $data['title'],
            'status' => isset($data['status']) && $data['status'] ? '1' : '0',
        ]);

        return $this->success(
            new BlogCategoryResource($category->fresh()),
            'Blog category updated successfully'
        );
    }

    /**
     * Delete a blog category.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/blog/categories/{id}',
        summary: 'Delete blog category',
        description: 'Delete a blog category. Cannot delete if category has associated posts.',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Blog Categories']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Category ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Blog category deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Blog category deleted successfully'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Category not found')]
    #[OA\Response(response: 409, description: 'Cannot delete category with associated posts')]
    public function destroy(int $id): JsonResponse
    {
        $category = BlogCategory::find($id);

        if (!$category) {
            return $this->error('Blog category not found', 404);
        }

        // Check if category has associated posts
        if (Blog::where('category_id', $id)->exists()) {
            return $this->error(
                'Cannot delete category. It has associated blog posts. Please reassign or delete the posts first.',
                409
            );
        }

        $category->delete();

        return $this->success(null, 'Blog category deleted successfully');
    }

    /**
     * Toggle category status.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Patch(
        path: '/api/v1/tenant/{tenant}/admin/blog/categories/{id}/toggle-status',
        summary: 'Toggle category status',
        description: 'Toggle the active status of a blog category',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Blog Categories']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Category ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Category status toggled successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Category status updated'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BlogCategoryResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Category not found')]
    public function toggleStatus(int $id): JsonResponse
    {
        $category = BlogCategory::find($id);

        if (!$category) {
            return $this->error('Blog category not found', 404);
        }

        $newStatus = $category->status === '1' ? '0' : '1';
        $category->update(['status' => $newStatus]);

        $statusText = $newStatus === '1' ? 'activated' : 'deactivated';

        return $this->success(
            new BlogCategoryResource($category->fresh()),
            "Category {$statusText} successfully"
        );
    }
}
