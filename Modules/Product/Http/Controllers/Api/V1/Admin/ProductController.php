<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Http\Requests\StoreProductRequest;
use Modules\Product\Http\Requests\UpdateProductRequest;
use Modules\Product\Http\Requests\StoreVariantRequest;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Http\Resources\ProductCollection;
use Modules\Product\Http\Resources\ProductInventoryDetailResource;
use Modules\Product\Services\ProductService;
use OpenApi\Attributes as OA;

/**
 * Admin Product Controller
 *
 * Full CRUD operations for products within a tenant context.
 *
 * @package Modules\Product\Http\Controllers\Api\V1\Admin
 */
#[OA\Tag(
    name: 'Tenant Admin - Products',
    description: 'Product management endpoints for tenant administrators'
)]
final class ProductController extends BaseApiController
{
    public function __construct(
        private readonly ProductService $productService,
    ) {}

    /**
     * List all products.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/products',
        summary: 'List all products',
        description: 'Get paginated list of all products with filters',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Products']
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
        name: 'status_id',
        in: 'query',
        description: 'Filter by status',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        description: 'Search by name or description',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'sort_by',
        in: 'query',
        description: 'Sort field',
        schema: new OA\Schema(type: 'string', enum: ['created_at', 'name', 'price', 'id'])
    )]
    #[OA\Parameter(
        name: 'sort_order',
        in: 'query',
        description: 'Sort order',
        schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])
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
            'status_id',
            'search',
            'min_price',
            'max_price',
            'in_stock',
            'on_sale',
            'sort_by',
            'sort_order',
        ]);

        $perPage = min((int) $request->input('per_page', 15), 100);

        $products = $this->productService->getAllProducts($filters, $perPage);

        return $this->success(
            new ProductCollection($products),
            'Products retrieved successfully'
        );
    }

    /**
     * Create a new product.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/products',
        summary: 'Create a new product',
        description: 'Create a new product with inventory, variants, and related data',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Products']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'price'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Premium T-Shirt'),
                new OA\Property(property: 'slug', type: 'string', example: 'premium-t-shirt'),
                new OA\Property(property: 'summary', type: 'string', example: 'High quality cotton t-shirt'),
                new OA\Property(property: 'description', type: 'string', example: 'Detailed product description...'),
                new OA\Property(property: 'price', type: 'number', example: 29.99),
                new OA\Property(property: 'sale_price', type: 'number', example: 24.99),
                new OA\Property(property: 'cost', type: 'number', example: 12.00),
                new OA\Property(property: 'category_id', type: 'integer', example: 1),
                new OA\Property(property: 'sub_category_id', type: 'integer', example: 2),
                new OA\Property(property: 'brand_id', type: 'integer', example: 1),
                new OA\Property(property: 'sku', type: 'string', example: 'TS-001'),
                new OA\Property(property: 'stock_count', type: 'integer', example: 100),
                new OA\Property(
                    property: 'variants',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'color_id', type: 'integer'),
                            new OA\Property(property: 'size_id', type: 'integer'),
                            new OA\Property(property: 'additional_price', type: 'number'),
                            new OA\Property(property: 'stock_count', type: 'integer'),
                        ],
                        type: 'object'
                    )
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Product created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Product created successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/ProductResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error'
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct($request->validated());

        return $this->success(
            new ProductResource($product),
            'Product created successfully',
            201
        );
    }

    /**
     * Get a single product.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/products/{id}',
        summary: 'Get a single product',
        description: 'Get detailed information about a product',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Products']
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
    public function show(int $id): JsonResponse
    {
        try {
            $product = $this->productService->getProductById($id);

            return $this->success(
                new ProductResource($product),
                'Product retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Product not found', 404);
        }
    }

    /**
     * Update a product.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/products/{id}',
        summary: 'Update a product',
        description: 'Update an existing product',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Products']
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
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'price', type: 'number'),
                new OA\Property(property: 'sale_price', type: 'number'),
                new OA\Property(property: 'status_id', type: 'integer'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Product updated successfully'
    )]
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $product = $this->productService->updateProduct($id, $request->validated());

            return $this->success(
                new ProductResource($product),
                'Product updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Product not found', 404);
        }
    }

    /**
     * Delete a product.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/products/{id}',
        summary: 'Delete a product',
        description: 'Soft delete a product',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Products']
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
    #[OA\Response(
        response: 200,
        description: 'Product deleted successfully'
    )]
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->productService->deleteProduct($id);

            return $this->success(null, 'Product deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Product not found', 404);
        }
    }

    /**
     * Bulk delete products.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/products/bulk-delete',
        summary: 'Bulk delete products',
        description: 'Delete multiple products at once',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Products']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['ids'],
            properties: [
                new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer'), example: [1, 2, 3]),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Products deleted successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: '3 products deleted successfully'),
            ]
        )
    )]
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $count = $this->productService->bulkDeleteProducts($request->input('ids'));

        return $this->success(
            ['deleted_count' => $count],
            "{$count} products deleted successfully"
        );
    }

    /**
     * Toggle product status.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/products/{id}/toggle-status',
        summary: 'Toggle product status',
        description: 'Toggle product between active and inactive status',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Products']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Product ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Status toggled successfully'
    )]
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $product = $this->productService->toggleStatus($id);

            return $this->success(
                new ProductResource($product),
                'Product status updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Product not found', 404);
        }
    }

    /**
     * Add variant to product.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/products/{id}/variants',
        summary: 'Add product variant',
        description: 'Add a new color/size variant to a product',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Products']
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
            properties: [
                new OA\Property(property: 'color_id', type: 'integer', example: 1),
                new OA\Property(property: 'size_id', type: 'integer', example: 2),
                new OA\Property(property: 'additional_price', type: 'number', example: 5.00),
                new OA\Property(property: 'stock_count', type: 'integer', example: 50),
                new OA\Property(property: 'image_id', type: 'integer', example: 10),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Variant added successfully'
    )]
    public function addVariant(StoreVariantRequest $request, int $id): JsonResponse
    {
        try {
            $product = $this->productService->getProductById($id);
            $this->productService->createVariants($product, [$request->validated()]);

            $product->refresh()->load('inventoryDetail');

            return $this->success(
                ProductInventoryDetailResource::collection($product->inventoryDetail),
                'Variant added successfully',
                201
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Product not found', 404);
        }
    }

    /**
     * Update product variant.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/products/{id}/variants/{variantId}',
        summary: 'Update product variant',
        description: 'Update an existing product variant',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Products']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Product ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'variantId',
        in: 'path',
        required: true,
        description: 'Variant ID',
        schema: new OA\Schema(type: 'integer', example: 5)
    )]
    #[OA\Response(
        response: 200,
        description: 'Variant updated successfully'
    )]
    public function updateVariant(StoreVariantRequest $request, int $id, int $variantId): JsonResponse
    {
        try {
            $variant = $this->productService->updateVariant($variantId, $request->validated());

            return $this->success(
                new ProductInventoryDetailResource($variant),
                'Variant updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Variant not found', 404);
        }
    }

    /**
     * Delete product variant.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/products/{id}/variants/{variantId}',
        summary: 'Delete product variant',
        description: 'Delete a product variant',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Products']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Product ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'variantId',
        in: 'path',
        required: true,
        description: 'Variant ID',
        schema: new OA\Schema(type: 'integer', example: 5)
    )]
    #[OA\Response(
        response: 200,
        description: 'Variant deleted successfully'
    )]
    public function deleteVariant(int $id, int $variantId): JsonResponse
    {
        try {
            $this->productService->deleteVariant($variantId);

            return $this->success(null, 'Variant deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Variant not found', 404);
        }
    }

    /**
     * Update product stock.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/products/{id}/stock',
        summary: 'Update product stock',
        description: 'Update stock count for a product or variant',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Products']
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
            required: ['quantity'],
            properties: [
                new OA\Property(property: 'quantity', type: 'integer', example: 50),
                new OA\Property(property: 'operation', type: 'string', enum: ['set', 'add', 'subtract'], example: 'add'),
                new OA\Property(property: 'variant_id', type: 'integer', example: 5),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Stock updated successfully'
    )]
    public function updateStock(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
            'operation' => ['nullable', 'string', 'in:set,add,subtract'],
            'variant_id' => ['nullable', 'integer'],
        ]);

        try {
            $this->productService->updateStock(
                $id,
                $request->input('quantity'),
                $request->input('operation', 'set'),
                $request->input('variant_id')
            );

            $product = $this->productService->getProductById($id);

            return $this->success(
                new ProductResource($product),
                'Stock updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Product or variant not found', 404);
        }
    }
}
