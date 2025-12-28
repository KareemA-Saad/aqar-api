<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Http\Resources\ProductCollection;
use Modules\Product\Http\Resources\ProductReviewResource;
use Modules\Product\Http\Resources\ProductCategoryResource;
use Modules\Product\Services\ProductService;
use Modules\Attributes\Entities\Category;
use OpenApi\Attributes as OA;

/**
 * Frontend Product Controller
 *
 * Public product endpoints for tenant storefront.
 *
 * @package Modules\Product\Http\Controllers\Api\V1\Frontend
 */
#[OA\Tag(
    name: 'Tenant Frontend - Products',
    description: 'Public product browsing endpoints'
)]
final class ProductController extends BaseApiController
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    /**
     * List published products.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/products',
        summary: 'List published products',
        description: 'Get paginated list of published products with filtering and sorting options',
        tags: ['Tenant Frontend - Products']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'category_id',
        in: 'query',
        description: 'Filter by category',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'sub_category_id',
        in: 'query',
        description: 'Filter by sub category',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'brand_id',
        in: 'query',
        description: 'Filter by brand',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'min_price',
        in: 'query',
        description: 'Minimum price filter',
        schema: new OA\Schema(type: 'number')
    )]
    #[OA\Parameter(
        name: 'max_price',
        in: 'query',
        description: 'Maximum price filter',
        schema: new OA\Schema(type: 'number')
    )]
    #[OA\Parameter(
        name: 'color_id',
        in: 'query',
        description: 'Filter by color',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'size_id',
        in: 'query',
        description: 'Filter by size',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'in_stock',
        in: 'query',
        description: 'Filter only in-stock products',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Parameter(
        name: 'on_sale',
        in: 'query',
        description: 'Filter only products on sale',
        schema: new OA\Schema(type: 'boolean')
    )]
    #[OA\Parameter(
        name: 'sort_by',
        in: 'query',
        description: 'Sort option',
        schema: new OA\Schema(type: 'string', enum: ['newest', 'oldest', 'price_low', 'price_high', 'name_asc', 'name_desc', 'popular'])
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15)
    )]
    #[OA\Response(
        response: 200,
        description: 'Products retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Products retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/ProductCollection'),
            ]
        )
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'category_id',
            'sub_category_id',
            'child_category_id',
            'brand_id',
            'min_price',
            'max_price',
            'color_id',
            'size_id',
            'in_stock',
            'on_sale',
            'badge_id',
            'search',
            'sort_by',
        ]);

        $perPage = min((int) $request->input('per_page', 15), 100);

        $products = $this->productService->getPublishedProducts($filters, $perPage);

        return $this->success(
            new ProductCollection($products),
            'Products retrieved successfully'
        );
    }

    /**
     * Get product by slug.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/products/{slug}',
        summary: 'Get product by slug',
        description: 'Get detailed information about a product by its slug',
        tags: ['Tenant Frontend - Products']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'slug',
        in: 'path',
        required: true,
        description: 'Product slug',
        schema: new OA\Schema(type: 'string', example: 'premium-t-shirt')
    )]
    #[OA\Response(
        response: 200,
        description: 'Product retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Product retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/ProductResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Product not found'
    )]
    public function show(string $slug): JsonResponse
    {
        try {
            $product = $this->productService->getProductBySlug($slug);

            return $this->success(
                new ProductResource($product),
                'Product retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Product not found', 404);
        }
    }

    /**
     * Search products.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/products/search',
        summary: 'Search products',
        description: 'Search products by name, description, or tags',
        tags: ['Tenant Frontend - Products']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'q',
        in: 'query',
        required: true,
        description: 'Search query',
        schema: new OA\Schema(type: 'string', example: 't-shirt')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15)
    )]
    #[OA\Response(
        response: 200,
        description: 'Search results',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Search results retrieved'),
                new OA\Property(property: 'data', ref: '#/components/schemas/ProductCollection'),
            ]
        )
    )]
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return $this->error('Search query must be at least 2 characters', 422);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        $products = $this->productService->searchProducts($query, $perPage);

        return $this->success(
            new ProductCollection($products),
            'Search results retrieved'
        );
    }

    /**
     * Get related products.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/products/{id}/related',
        summary: 'Get related products',
        description: 'Get products related to the specified product (same category)',
        tags: ['Tenant Frontend - Products']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Product ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: 'Number of related products to return',
        schema: new OA\Schema(type: 'integer', default: 4)
    )]
    #[OA\Response(
        response: 200,
        description: 'Related products retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Related products retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ProductResource')
                ),
            ]
        )
    )]
    public function related(Request $request, int $id): JsonResponse
    {
        $limit = min((int) $request->input('limit', 4), 12);

        $products = $this->productService->getRelatedProducts($id, $limit);

        return $this->success(
            ProductResource::collection($products),
            'Related products retrieved'
        );
    }

    /**
     * Get product reviews.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/products/{id}/reviews',
        summary: 'Get product reviews',
        description: 'Get paginated reviews for a product',
        tags: ['Tenant Frontend - Products']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Product ID',
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
        description: 'Reviews retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Reviews retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'average_rating', type: 'number', example: 4.5),
                        new OA\Property(property: 'total_reviews', type: 'integer', example: 25),
                        new OA\Property(
                            property: 'rating_distribution',
                            type: 'object',
                            example: ['5' => 15, '4' => 7, '3' => 2, '2' => 1, '1' => 0]
                        ),
                        new OA\Property(
                            property: 'reviews',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/ProductReviewResource')
                        ),
                    ]
                ),
            ]
        )
    )]
    public function reviews(Request $request, int $id): JsonResponse
    {
        try {
            $product = $this->productService->getProductById($id);
            $perPage = min((int) $request->input('per_page', 10), 50);

            $reviews = $product->reviews()
                ->with('user')
                ->where('status', 1)
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Calculate rating distribution
            $distribution = $product->reviews()
                ->where('status', 1)
                ->selectRaw('rating, COUNT(*) as count')
                ->groupBy('rating')
                ->pluck('count', 'rating')
                ->toArray();

            $ratingDistribution = [];
            for ($i = 5; $i >= 1; $i--) {
                $ratingDistribution[$i] = $distribution[$i] ?? 0;
            }

            return $this->success([
                'average_rating' => round($product->ratings() ?? 0, 1),
                'total_reviews' => $reviews->total(),
                'rating_distribution' => $ratingDistribution,
                'reviews' => ProductReviewResource::collection($reviews),
                'meta' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'per_page' => $reviews->perPage(),
                    'total' => $reviews->total(),
                ],
            ], 'Reviews retrieved');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Product not found', 404);
        }
    }

    /**
     * Submit product review.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/products/{id}/reviews',
        summary: 'Submit product review',
        description: 'Submit a review for a product (requires authentication)',
        security: [['sanctum' => []]],
        tags: ['Tenant Frontend - Products']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Product ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['rating'],
            properties: [
                new OA\Property(property: 'rating', type: 'integer', minimum: 1, maximum: 5, example: 5),
                new OA\Property(property: 'review', type: 'string', example: 'Great product! Highly recommended.'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Review submitted successfully'
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    public function storeReview(Request $request, int $id): JsonResponse
    {
        $user = auth('api_tenant_user')->user();

        if (!$user) {
            return $this->error('Authentication required to submit a review', 401);
        }

        $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $product = $this->productService->getProductById($id);

            // Check if user already reviewed this product
            $existingReview = $product->reviews()
                ->where('user_id', $user->id)
                ->first();

            if ($existingReview) {
                return $this->error('You have already reviewed this product', 422);
            }

            $review = $product->reviews()->create([
                'user_id' => $user->id,
                'rating' => $request->input('rating'),
                'review' => $request->input('review'),
                'status' => 0, // Pending moderation
            ]);

            return $this->success(
                new ProductReviewResource($review),
                'Review submitted successfully and is pending moderation',
                201
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Product not found', 404);
        }
    }

    /**
     * Get all categories.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/categories',
        summary: 'Get all categories',
        description: 'Get list of all product categories with optional subcategories',
        tags: ['Tenant Frontend - Products']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'with_subcategories',
        in: 'query',
        description: 'Include subcategories in response',
        schema: new OA\Schema(type: 'boolean', default: false)
    )]
    #[OA\Parameter(
        name: 'with_count',
        in: 'query',
        description: 'Include product count',
        schema: new OA\Schema(type: 'boolean', default: false)
    )]
    #[OA\Response(
        response: 200,
        description: 'Categories retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Categories retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ProductCategoryResource')
                ),
            ]
        )
    )]
    public function categories(Request $request): JsonResponse
    {
        $query = Category::where('status', 1);

        if ($request->boolean('with_subcategories')) {
            $query->with(['subcategories' => function ($q) {
                $q->where('status', 1);
            }]);
        }

        if ($request->boolean('with_count')) {
            $query->withCount(['products' => function ($q) {
                $q->where('status_id', 1);
            }]);
        }

        $categories = $query->orderBy('name')->get();

        return $this->success(
            ProductCategoryResource::collection($categories),
            'Categories retrieved'
        );
    }

    /**
     * Get products by category.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/categories/{id}/products',
        summary: 'Get products by category',
        description: 'Get products in a specific category',
        tags: ['Tenant Frontend - Products']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Category ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        description: 'Items per page',
        schema: new OA\Schema(type: 'integer', default: 15)
    )]
    #[OA\Response(
        response: 200,
        description: 'Products retrieved'
    )]
    public function productsByCategory(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $perPage = min((int) $request->input('per_page', 15), 100);

        $filters = array_merge(
            $request->only(['sub_category_id', 'brand_id', 'min_price', 'max_price', 'sort_by']),
            ['category_id' => $id]
        );

        $products = $this->productService->getPublishedProducts($filters, $perPage);

        return $this->success([
            'category' => new ProductCategoryResource($category),
            'products' => new ProductCollection($products),
        ], 'Products retrieved');
    }

    /**
     * Get available filters.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/products/filters',
        summary: 'Get available filters',
        description: 'Get all available filter options (colors, sizes, brands, price range)',
        tags: ['Tenant Frontend - Products']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'category_id',
        in: 'query',
        description: 'Get filters specific to a category',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Filters retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'colors', type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'sizes', type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'brands', type: 'array', items: new OA\Items(type: 'object')),
                    new OA\Property(property: 'price_range', type: 'object', properties: [
                        new OA\Property(property: 'min', type: 'number'),
                        new OA\Property(property: 'max', type: 'number'),
                    ]),
                ]),
            ]
        )
    )]
    public function filters(Request $request): JsonResponse
    {
        $categoryId = $request->input('category_id');

        // Base product query
        $productQuery = \Modules\Product\Entities\Product::where('status_id', 1);

        if ($categoryId) {
            $productQuery->whereHas('product_category', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        $productIds = $productQuery->pluck('id');

        // Get colors
        $colors = \Modules\Attributes\Entities\Color::whereHas('productInventoryDetails', function ($q) use ($productIds) {
            $q->whereIn('product_id', $productIds);
        })->select('id', 'name', 'color_code')->get();

        // Get sizes
        $sizes = \Modules\Attributes\Entities\Size::whereHas('productInventoryDetails', function ($q) use ($productIds) {
            $q->whereIn('product_id', $productIds);
        })->select('id', 'name')->get();

        // Get brands
        $brands = \Modules\Attributes\Entities\Brand::whereHas('products', function ($q) use ($productIds) {
            $q->whereIn('id', $productIds);
        })->select('id', 'name')->get();

        // Get price range
        $priceRange = $productQuery->selectRaw('MIN(COALESCE(sale_price, price)) as min_price, MAX(COALESCE(sale_price, price)) as max_price')
            ->first();

        return $this->success([
            'colors' => $colors,
            'sizes' => $sizes,
            'brands' => $brands,
            'price_range' => [
                'min' => round($priceRange->min_price ?? 0, 2),
                'max' => round($priceRange->max_price ?? 0, 2),
            ],
        ], 'Filters retrieved');
    }
}
