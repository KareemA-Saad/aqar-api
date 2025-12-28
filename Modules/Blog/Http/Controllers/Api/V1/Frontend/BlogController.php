<?php

declare(strict_types=1);

namespace Modules\Blog\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Blog\Entities\Blog;
use Modules\Blog\Entities\BlogCategory;
use Modules\Blog\Http\Requests\StoreCommentRequest;
use Modules\Blog\Http\Resources\BlogCollection;
use Modules\Blog\Http\Resources\BlogResource;
use Modules\Blog\Http\Resources\BlogCategoryResource;
use Modules\Blog\Http\Resources\CommentResource;
use Modules\Blog\Services\BlogService;
use OpenApi\Attributes as OA;

/**
 * Frontend Blog Controller
 *
 * Public blog endpoints within a tenant context.
 * Most endpoints do not require authentication.
 *
 * @package Modules\Blog\Http\Controllers\Api\V1\Frontend
 */
#[OA\Tag(
    name: 'Tenant Frontend - Blog',
    description: 'Public blog endpoints for tenant frontend'
)]
final class BlogController extends BaseApiController
{
    public function __construct(
        private readonly BlogService $blogService,
    ) {}

    /**
     * List published blog posts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/blog',
        summary: 'List published blog posts',
        description: 'Get paginated list of published blog posts',
        tags: ['Tenant Frontend - Blog']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'category_id',
        in: 'query',
        description: 'Filter by category ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        description: 'Search query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15)
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
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category_id', 'search', 'sort_by', 'sort_order']);
        $perPage = min((int) $request->input('per_page', 15), 100);

        $posts = $this->blogService->getPublishedPosts($filters, $perPage);

        return $this->success(
            new BlogCollection($posts),
            'Blog posts retrieved successfully'
        );
    }

    /**
     * Get a single blog post by slug.
     *
     * @param string $slug
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/blog/{slug}',
        summary: 'Get blog post by slug',
        description: 'Get a single published blog post by slug with related posts',
        tags: ['Tenant Frontend - Blog']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'slug',
        in: 'path',
        required: true,
        description: 'Blog post slug',
        schema: new OA\Schema(type: 'string', example: 'getting-started-with-laravel')
    )]
    #[OA\Response(
        response: 200,
        description: 'Blog post retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Blog post retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'post', ref: '#/components/schemas/BlogResource'),
                        new OA\Property(
                            property: 'related_posts',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/BlogResource')
                        ),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Blog post not found')]
    public function show(string $slug): JsonResponse
    {
        $blog = $this->blogService->getPostBySlug($slug);

        if (!$blog) {
            return $this->error('Blog post not found', 404);
        }

        // Increment view count
        $this->blogService->incrementViews($blog);

        // Get related posts
        $relatedPosts = $this->blogService->getRelatedPosts($blog, 3);

        return $this->success([
            'post' => new BlogResource($blog),
            'related_posts' => BlogResource::collection($relatedPosts),
        ], 'Blog post retrieved successfully');
    }

    /**
     * Get blog posts by category.
     *
     * @param Request $request
     * @param string $slug
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/blog/category/{slug}',
        summary: 'Get blog posts by category',
        description: 'Get paginated list of blog posts by category slug',
        tags: ['Tenant Frontend - Blog']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'slug',
        in: 'path',
        required: true,
        description: 'Category ID',
        schema: new OA\Schema(type: 'string', example: '1')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15)
    )]
    #[OA\Response(
        response: 200,
        description: 'Blog posts retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Blog posts retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'category', ref: '#/components/schemas/BlogCategoryResource'),
                        new OA\Property(property: 'posts', ref: '#/components/schemas/BlogCollection'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Category not found')]
    public function byCategory(Request $request, string $slug): JsonResponse
    {
        // slug is actually the category ID
        $categoryId = (int) $slug;
        $category = BlogCategory::where('id', $categoryId)->where('status', '1')->first();

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $posts = $this->blogService->getPostsByCategory($categoryId, $perPage);

        return $this->success([
            'category' => new BlogCategoryResource($category),
            'posts' => new BlogCollection($posts),
        ], 'Blog posts retrieved successfully');
    }

    /**
     * Get blog posts by tag.
     *
     * @param Request $request
     * @param string $tag
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/blog/tag/{tag}',
        summary: 'Get blog posts by tag',
        description: 'Get paginated list of blog posts by tag',
        tags: ['Tenant Frontend - Blog']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'tag',
        in: 'path',
        required: true,
        description: 'Tag name',
        schema: new OA\Schema(type: 'string', example: 'laravel')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15)
    )]
    #[OA\Response(
        response: 200,
        description: 'Blog posts retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Blog posts retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'tag', type: 'string', example: 'laravel'),
                        new OA\Property(property: 'posts', ref: '#/components/schemas/BlogCollection'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    public function byTag(Request $request, string $tag): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $posts = $this->blogService->getPostsByTag($tag, $perPage);

        return $this->success([
            'tag' => $tag,
            'posts' => new BlogCollection($posts),
        ], 'Blog posts retrieved successfully');
    }

    /**
     * Search blog posts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/blog/search',
        summary: 'Search blog posts',
        description: 'Search published blog posts by title, content, or tags',
        tags: ['Tenant Frontend - Blog']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'q',
        in: 'query',
        required: true,
        description: 'Search query',
        schema: new OA\Schema(type: 'string', example: 'laravel')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15)
    )]
    #[OA\Response(
        response: 200,
        description: 'Search results retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Search results retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'query', type: 'string', example: 'laravel'),
                        new OA\Property(property: 'posts', ref: '#/components/schemas/BlogCollection'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        $query = $request->input('q');
        $perPage = min((int) $request->input('per_page', 15), 100);

        $posts = $this->blogService->searchPosts($query, $perPage);

        return $this->success([
            'query' => $query,
            'posts' => new BlogCollection($posts),
        ], 'Search results retrieved successfully');
    }

    /**
     * Get recent blog posts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/blog/recent',
        summary: 'Get recent blog posts',
        description: 'Get list of most recent published blog posts',
        tags: ['Tenant Frontend - Blog']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'Number of posts to return',
        schema: new OA\Schema(type: 'integer', default: 5, maximum: 20)
    )]
    #[OA\Response(
        response: 200,
        description: 'Recent posts retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Recent posts retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/BlogResource')
                ),
            ]
        )
    )]
    public function recent(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 5), 20);
        $posts = $this->blogService->getRecentPosts($limit);

        return $this->success(
            BlogResource::collection($posts),
            'Recent posts retrieved successfully'
        );
    }

    /**
     * Get popular blog posts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/blog/popular',
        summary: 'Get popular blog posts',
        description: 'Get list of most viewed published blog posts',
        tags: ['Tenant Frontend - Blog']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'Number of posts to return',
        schema: new OA\Schema(type: 'integer', default: 5, maximum: 20)
    )]
    #[OA\Response(
        response: 200,
        description: 'Popular posts retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Popular posts retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/BlogResource')
                ),
            ]
        )
    )]
    public function popular(Request $request): JsonResponse
    {
        $limit = min((int) $request->input('limit', 5), 20);
        $posts = $this->blogService->getPopularPosts($limit);

        return $this->success(
            BlogResource::collection($posts),
            'Popular posts retrieved successfully'
        );
    }

    /**
     * Get comments for a blog post.
     *
     * @param Request $request
     * @param int $postId
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/blog/{postId}/comments',
        summary: 'Get blog post comments',
        description: 'Get paginated comments for a blog post',
        tags: ['Tenant Frontend - Blog']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'postId',
        in: 'path',
        required: true,
        description: 'Blog post ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 10)
    )]
    #[OA\Response(
        response: 200,
        description: 'Comments retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Comments retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(
                            property: 'comments',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/CommentResource')
                        ),
                        new OA\Property(property: 'pagination', ref: '#/components/schemas/PaginationMeta'),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(response: 404, description: 'Blog post not found')]
    public function comments(Request $request, int $postId): JsonResponse
    {
        $blog = Blog::where('id', $postId)->where('status', true)->first();

        if (!$blog) {
            return $this->error('Blog post not found', 404);
        }

        $perPage = min((int) $request->input('per_page', 10), 50);
        $comments = $this->blogService->getComments($postId, $perPage);

        return $this->success([
            'comments' => CommentResource::collection($comments->items()),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
                'has_more_pages' => $comments->hasMorePages(),
            ],
        ], 'Comments retrieved successfully');
    }

    /**
     * Store a comment on a blog post.
     *
     * @param StoreCommentRequest $request
     * @param int $postId
     * @return JsonResponse
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/blog/{postId}/comments',
        summary: 'Add comment to blog post',
        description: 'Add a comment to a blog post (authentication required)',
        security: [['sanctum' => []]],
        tags: ['Tenant Frontend - Blog']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'postId',
        in: 'path',
        required: true,
        description: 'Blog post ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StoreCommentRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Comment added successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Comment added successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/CommentResource'),
            ]
        )
    )]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Blog post not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function storeComment(StoreCommentRequest $request, int $postId): JsonResponse
    {
        $blog = Blog::where('id', $postId)->where('status', true)->first();

        if (!$blog) {
            return $this->error('Blog post not found', 404);
        }

        $comment = $this->blogService->createComment($postId, $request->validated());

        return $this->success(
            new CommentResource($comment->load('user')),
            'Comment added successfully',
            201
        );
    }

    /**
     * Get all blog categories.
     *
     * @return JsonResponse
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/blog/categories',
        summary: 'Get blog categories',
        description: 'Get list of active blog categories with post counts',
        tags: ['Tenant Frontend - Blog']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Response(
        response: 200,
        description: 'Categories retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Categories retrieved successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/BlogCategoryResource')
                ),
            ]
        )
    )]
    public function categories(): JsonResponse
    {
        $categories = BlogCategory::where('status', '1')
            ->withCount(['blogs' => function ($query) {
                $query->where('status', true);
            }])
            ->orderBy('title')
            ->get();

        return $this->success(
            BlogCategoryResource::collection($categories),
            'Categories retrieved successfully'
        );
    }
}
