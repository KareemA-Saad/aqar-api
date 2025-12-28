<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Blog\Entities\Blog;
use Modules\Blog\Http\Requests\BulkBlogRequest;
use Modules\Blog\Http\Requests\StoreBlogRequest;
use Modules\Blog\Http\Requests\UpdateBlogRequest;
use Modules\Blog\Http\Resources\BlogCollection;
use Modules\Blog\Http\Resources\BlogResource;
use Modules\Blog\Services\BlogService;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin Blog Controller
 *
 * Manages blog posts within a tenant context.
 * Requires tenant admin authentication and tenant context.
 *
 * @package Modules\Blog\Http\Controllers\Api\V1\Admin
 */
#[OA\Tag(
    name: 'Tenant Admin - Blog',
    description: 'Manage blog posts within a tenant'
)]
final class BlogController extends BaseApiController
{
    public function __construct(
        private readonly BlogService $blogService,
    ) {}

    /**
     * List all blog posts with pagination and filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/blog/posts',
        summary: 'List blog posts',
        description: 'Get paginated list of blog posts with optional filters',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Blog']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        description: 'Search by title, content, or tags',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'category_id',
        in: 'query',
        description: 'Filter by category ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        description: 'Filter by status (1=published, 0=draft)',
        schema: new OA\Schema(type: 'integer', enum: [0, 1])
    )]
    #[OA\Parameter(
        name: 'featured',
        in: 'query',
        description: 'Filter by featured status',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Parameter(
        name: 'author_id',
        in: 'query',
        description: 'Filter by author ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'date_from',
        in: 'query',
        description: 'Filter by creation date from (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'date_to',
        in: 'query',
        description: 'Filter by creation date to (Y-m-d)',
        schema: new OA\Schema(type: 'string', format: 'date')
    )]
    #[OA\Parameter(
        name: 'sort_by',
        in: 'query',
        description: 'Sort field',
        schema: new OA\Schema(type: 'string', enum: ['title', 'views', 'created_at', 'updated_at', 'status'])
    )]
    #[OA\Parameter(
        name: 'sort_order',
        in: 'query',
        description: 'Sort direction',
        schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15, minimum: 1, maximum: 100)
    )]
    #[OA\Response(
        response: 200,
        description: 'Blog posts retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Blog posts retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BlogCollection'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'category_id',
            'status',
            'featured',
            'author_id',
            'date_from',
            'date_to',
            'sort_by',
            'sort_order',
        ]);

        $perPage = min((int) $request->input('per_page', 15), 100);

        $posts = $this->blogService->getPosts($filters, $perPage);

        return $this->success(
            new BlogCollection($posts),
            'Blog posts retrieved successfully'
        );
    }

    /**
     * Create a new blog post.
     *
     * @param StoreBlogRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/blog/posts',
        summary: 'Create blog post',
        description: 'Create a new blog post',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Blog']
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
        content: new OA\JsonContent(ref: '#/components/schemas/StoreBlogRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Blog post created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Blog post created successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BlogResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function store(StoreBlogRequest $request): JsonResponse
    {
        $blog = $this->blogService->createPost($request->validated());

        return $this->success(
            new BlogResource($blog),
            'Blog post created successfully',
            201
        );
    }

    /**
     * Get a specific blog post.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/blog/posts/{id}',
        summary: 'Get blog post',
        description: 'Get a specific blog post by ID',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Blog']
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
        description: 'Blog post ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Blog post retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Blog post retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BlogResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Blog post not found')]
    public function show(int $id): JsonResponse
    {
        $blog = Blog::with(['category', 'metainfo'])->withCount('comments')->find($id);

        if (!$blog) {
            return $this->error('Blog post not found', 404);
        }

        return $this->success(
            new BlogResource($blog),
            'Blog post retrieved successfully'
        );
    }

    /**
     * Update a blog post.
     *
     * @param UpdateBlogRequest $request
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/blog/posts/{id}',
        summary: 'Update blog post',
        description: 'Update an existing blog post',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Blog']
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
        description: 'Blog post ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UpdateBlogRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Blog post updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Blog post updated successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BlogResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Blog post not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function update(UpdateBlogRequest $request, int $id): JsonResponse
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return $this->error('Blog post not found', 404);
        }

        $blog = $this->blogService->updatePost($blog, $request->validated());

        return $this->success(
            new BlogResource($blog),
            'Blog post updated successfully'
        );
    }

    /**
     * Delete a blog post.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/blog/posts/{id}',
        summary: 'Delete blog post',
        description: 'Delete a blog post',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Blog']
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
        description: 'Blog post ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Blog post deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Blog post deleted successfully'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Blog post not found')]
    public function destroy(int $id): JsonResponse
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return $this->error('Blog post not found', 404);
        }

        $this->blogService->deletePost($blog);

        return $this->success(null, 'Blog post deleted successfully');
    }

    /**
     * Toggle blog post status.
     *
     * @param int $id
     * @return JsonResponse
     */
    #[OA\Patch(
        path: '/api/v1/tenant/{tenant}/admin/blog/posts/{id}/toggle-status',
        summary: 'Toggle blog post status',
        description: 'Toggle the publish status of a blog post',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Blog']
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
        description: 'Blog post ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Blog post status toggled successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Blog post status updated'),
                new OA\Property(property: 'data', ref: '#/components/schemas/BlogResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Blog post not found')]
    public function toggleStatus(int $id): JsonResponse
    {
        $blog = Blog::find($id);

        if (!$blog) {
            return $this->error('Blog post not found', 404);
        }

        $blog = $this->blogService->toggleStatus($blog);

        $status = $blog->status ? 'published' : 'unpublished';

        return $this->success(
            new BlogResource($blog),
            "Blog post {$status} successfully"
        );
    }

    /**
     * Bulk action on blog posts.
     *
     * @param BulkBlogRequest $request
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/blog/posts/bulk-action',
        summary: 'Bulk action on blog posts',
        description: 'Perform bulk action (delete, publish, unpublish) on multiple blog posts',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Blog']
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
        content: new OA\JsonContent(ref: '#/components/schemas/BulkBlogRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Bulk action completed successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Bulk action completed. 3 posts affected.'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'affected_count', type: 'integer', example: 3),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function bulkAction(BulkBlogRequest $request): JsonResponse
    {
        $data = $request->validated();
        $affectedCount = $this->blogService->bulkAction($data['ids'], $data['action']);

        return $this->success(
            ['affected_count' => $affectedCount],
            "Bulk action completed. {$affectedCount} posts affected."
        );
    }
}
