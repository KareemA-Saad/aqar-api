<?php

namespace Modules\Product\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\ProductInventory;
use Modules\Product\Entities\ProductInventoryDetail;
use Modules\Product\Entities\ProductCategory;
use Modules\Product\Entities\ProductSubCategory;
use Modules\Product\Entities\ProductChildCategory;
use Modules\Product\Entities\ProductGallery;
use Modules\Product\Entities\ProductTag;
use Modules\Product\Entities\ProductUom;
use Modules\Product\Entities\ProductDeliveryOption;
use Modules\Product\Entities\ProductShippingReturnPolicy;
use App\Models\MetaInfo;

class ProductService
{
    /**
     * Get all products with filters
     */
    public function getAllProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::with([
            'category',
            'subCategory',
            'brand',
            'status',
            'badge',
            'inventory',
            'inventoryDetail.productColor',
            'inventoryDetail.productSize',
        ])->withCount('inventoryDetail');

        // Apply filters
        $this->applyFilters($query, $filters);

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['created_at', 'name', 'price', 'sale_price', 'id'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder === 'asc' ? 'asc' : 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get published products for frontend
     */
    public function getPublishedProducts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Product::with([
            'category',
            'subCategory',
            'brand',
            'badge',
            'inventory',
            'inventoryDetail.productColor',
            'inventoryDetail.productSize',
            'gallery_images',
        ])
        ->withCount('inventoryDetail')
        ->where('status_id', 1); // Published status

        // Apply filters
        $this->applyFilters($query, $filters);

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        switch ($sortBy) {
            case 'price_low':
                $query->orderByRaw('COALESCE(sale_price, price) ASC');
                break;
            case 'price_high':
                $query->orderByRaw('COALESCE(sale_price, price) DESC');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'popular':
                $query->withCount('reviews')->orderBy('reviews_count', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Get single product by ID
     */
    public function getProductById(int $id): Product
    {
        return Product::with([
            'category',
            'subCategory',
            'childCategory',
            'brand',
            'status',
            'badge',
            'inventory',
            'inventoryDetail.productColor',
            'inventoryDetail.productSize',
            'inventoryDetail.attribute',
            'inventoryDetail.attr_image',
            'gallery_images',
            'tag',
            'uom',
            'product_delivery_option',
            'return_policy',
            'reviews.user',
            'metaData',
        ])
        ->withCount('inventoryDetail')
        ->findOrFail($id);
    }

    /**
     * Get single product by slug
     */
    public function getProductBySlug(string $slug): Product
    {
        return Product::with([
            'category',
            'subCategory',
            'childCategory',
            'brand',
            'badge',
            'inventory',
            'inventoryDetail.productColor',
            'inventoryDetail.productSize',
            'inventoryDetail.attribute',
            'inventoryDetail.attr_image',
            'gallery_images',
            'tag',
            'uom',
            'product_delivery_option',
            'return_policy',
            'reviews.user',
            'metaData',
        ])
        ->withCount('inventoryDetail')
        ->where('slug', $slug)
        ->where('status_id', 1)
        ->firstOrFail();
    }

    /**
     * Create a new product
     */
    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            // Generate slug if not provided
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

            // Create product
            $product = Product::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'summary' => $data['summary'] ?? null,
                'description' => $data['description'] ?? null,
                'brand_id' => $data['brand_id'] ?? null,
                'status_id' => $data['status_id'] ?? 1,
                'cost' => $data['cost'] ?? 0,
                'price' => $data['price'],
                'sale_price' => $data['sale_price'] ?? null,
                'image_id' => $data['image_id'] ?? null,
                'badge_id' => $data['badge_id'] ?? null,
                'min_purchase' => $data['min_purchase'] ?? 1,
                'max_purchase' => $data['max_purchase'] ?? null,
                'is_refundable' => $data['is_refundable'] ?? false,
                'is_inventory_warn_able' => $data['is_inventory_warn_able'] ?? false,
                'is_in_house' => $data['is_in_house'] ?? true,
            ]);

            // Create category relationship
            if (!empty($data['category_id'])) {
                ProductCategory::create([
                    'product_id' => $product->id,
                    'category_id' => $data['category_id'],
                ]);
            }

            // Create sub category relationship
            if (!empty($data['sub_category_id'])) {
                ProductSubCategory::create([
                    'product_id' => $product->id,
                    'sub_category_id' => $data['sub_category_id'],
                ]);
            }

            // Create child categories
            if (!empty($data['child_category_ids'])) {
                foreach ($data['child_category_ids'] as $childCategoryId) {
                    ProductChildCategory::create([
                        'product_id' => $product->id,
                        'child_category_id' => $childCategoryId,
                    ]);
                }
            }

            // Create inventory
            if (isset($data['sku']) || isset($data['stock_count'])) {
                ProductInventory::create([
                    'product_id' => $product->id,
                    'sku' => $data['sku'] ?? Str::upper(Str::random(8)),
                    'stock_count' => $data['stock_count'] ?? 0,
                ]);
            }

            // Create gallery images
            if (!empty($data['gallery_images'])) {
                foreach ($data['gallery_images'] as $imageId) {
                    ProductGallery::create([
                        'product_id' => $product->id,
                        'image_id' => $imageId,
                    ]);
                }
            }

            // Create tags
            if (!empty($data['tags'])) {
                foreach ($data['tags'] as $tagName) {
                    ProductTag::create([
                        'product_id' => $product->id,
                        'tag_name' => $tagName,
                    ]);
                }
            }

            // Create UOM
            if (!empty($data['uom_id'])) {
                ProductUom::create([
                    'product_id' => $product->id,
                    'uom_id' => $data['uom_id'],
                    'quantity' => $data['uom_quantity'] ?? 1,
                ]);
            }

            // Create delivery options
            if (!empty($data['delivery_option_ids'])) {
                foreach ($data['delivery_option_ids'] as $optionId) {
                    ProductDeliveryOption::create([
                        'product_id' => $product->id,
                        'delivery_option_id' => $optionId,
                    ]);
                }
            }

            // Create return policy
            if (!empty($data['return_policy'])) {
                ProductShippingReturnPolicy::create([
                    'product_id' => $product->id,
                    'return_day' => $data['return_policy']['return_days'] ?? 0,
                    'return_policy' => $data['return_policy']['policy'] ?? null,
                    'shipping_return_description' => $data['return_policy']['description'] ?? null,
                ]);
            }

            // Create meta info
            if (!empty($data['meta'])) {
                $product->metainfo()->create([
                    'title' => $data['meta']['title'] ?? $product->name,
                    'description' => $data['meta']['description'] ?? $product->summary,
                    'keywords' => $data['meta']['keywords'] ?? null,
                    'og_image' => $data['meta']['og_image'] ?? null,
                ]);
            }

            // Create variants
            if (!empty($data['variants'])) {
                $this->createVariants($product, $data['variants']);
            }

            return $product->fresh([
                'category',
                'subCategory',
                'inventory',
                'inventoryDetail',
            ]);
        });
    }

    /**
     * Update a product
     */
    public function updateProduct(int $id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data) {
            $product = Product::findOrFail($id);

            // Update product fields
            $product->update([
                'name' => $data['name'] ?? $product->name,
                'slug' => $data['slug'] ?? $product->slug,
                'summary' => $data['summary'] ?? $product->summary,
                'description' => $data['description'] ?? $product->description,
                'brand_id' => $data['brand_id'] ?? $product->brand_id,
                'status_id' => $data['status_id'] ?? $product->status_id,
                'cost' => $data['cost'] ?? $product->cost,
                'price' => $data['price'] ?? $product->price,
                'sale_price' => array_key_exists('sale_price', $data) ? $data['sale_price'] : $product->sale_price,
                'image_id' => $data['image_id'] ?? $product->image_id,
                'badge_id' => $data['badge_id'] ?? $product->badge_id,
                'min_purchase' => $data['min_purchase'] ?? $product->min_purchase,
                'max_purchase' => $data['max_purchase'] ?? $product->max_purchase,
                'is_refundable' => $data['is_refundable'] ?? $product->is_refundable,
                'is_inventory_warn_able' => $data['is_inventory_warn_able'] ?? $product->is_inventory_warn_able,
                'is_in_house' => $data['is_in_house'] ?? $product->is_in_house,
            ]);

            // Update category
            if (isset($data['category_id'])) {
                ProductCategory::updateOrCreate(
                    ['product_id' => $product->id],
                    ['category_id' => $data['category_id']]
                );
            }

            // Update sub category
            if (isset($data['sub_category_id'])) {
                ProductSubCategory::updateOrCreate(
                    ['product_id' => $product->id],
                    ['sub_category_id' => $data['sub_category_id']]
                );
            }

            // Update child categories
            if (isset($data['child_category_ids'])) {
                ProductChildCategory::where('product_id', $product->id)->delete();
                foreach ($data['child_category_ids'] as $childCategoryId) {
                    ProductChildCategory::create([
                        'product_id' => $product->id,
                        'child_category_id' => $childCategoryId,
                    ]);
                }
            }

            // Update inventory
            if (isset($data['sku']) || isset($data['stock_count'])) {
                ProductInventory::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'sku' => $data['sku'] ?? $product->inventory?->sku ?? Str::upper(Str::random(8)),
                        'stock_count' => $data['stock_count'] ?? $product->inventory?->stock_count ?? 0,
                    ]
                );
            }

            // Update gallery images
            if (isset($data['gallery_images'])) {
                ProductGallery::where('product_id', $product->id)->delete();
                foreach ($data['gallery_images'] as $imageId) {
                    ProductGallery::create([
                        'product_id' => $product->id,
                        'image_id' => $imageId,
                    ]);
                }
            }

            // Update tags
            if (isset($data['tags'])) {
                ProductTag::where('product_id', $product->id)->delete();
                foreach ($data['tags'] as $tagName) {
                    ProductTag::create([
                        'product_id' => $product->id,
                        'tag_name' => $tagName,
                    ]);
                }
            }

            // Update delivery options
            if (isset($data['delivery_option_ids'])) {
                ProductDeliveryOption::where('product_id', $product->id)->delete();
                foreach ($data['delivery_option_ids'] as $optionId) {
                    ProductDeliveryOption::create([
                        'product_id' => $product->id,
                        'delivery_option_id' => $optionId,
                    ]);
                }
            }

            // Update return policy
            if (isset($data['return_policy'])) {
                ProductShippingReturnPolicy::updateOrCreate(
                    ['product_id' => $product->id],
                    [
                        'return_day' => $data['return_policy']['return_days'] ?? 0,
                        'return_policy' => $data['return_policy']['policy'] ?? null,
                        'shipping_return_description' => $data['return_policy']['description'] ?? null,
                    ]
                );
            }

            // Update meta info
            if (isset($data['meta'])) {
                $product->metainfo()->updateOrCreate(
                    ['metainfoable_type' => Product::class, 'metainfoable_id' => $product->id],
                    [
                        'title' => $data['meta']['title'] ?? $product->name,
                        'description' => $data['meta']['description'] ?? $product->summary,
                        'keywords' => $data['meta']['keywords'] ?? null,
                        'og_image' => $data['meta']['og_image'] ?? null,
                    ]
                );
            }

            return $product->fresh([
                'category',
                'subCategory',
                'childCategory',
                'inventory',
                'inventoryDetail',
            ]);
        });
    }

    /**
     * Delete a product
     */
    public function deleteProduct(int $id): bool
    {
        $product = Product::findOrFail($id);
        return $product->delete();
    }

    /**
     * Bulk delete products
     */
    public function bulkDeleteProducts(array $ids): int
    {
        return Product::whereIn('id', $ids)->delete();
    }

    /**
     * Toggle product status
     */
    public function toggleStatus(int $id): Product
    {
        $product = Product::findOrFail($id);
        $product->status_id = $product->status_id === 1 ? 0 : 1;
        $product->save();

        return $product;
    }

    /**
     * Create product variants
     */
    public function createVariants(Product $product, array $variants): void
    {
        foreach ($variants as $variant) {
            ProductInventoryDetail::create([
                'product_inventory_id' => $product->inventory?->id,
                'product_id' => $product->id,
                'color' => $variant['color_id'] ?? null,
                'size' => $variant['size_id'] ?? null,
                'hash' => Str::random(10),
                'additional_price' => $variant['additional_price'] ?? 0,
                'add_cost' => $variant['add_cost'] ?? 0,
                'image' => $variant['image_id'] ?? null,
                'stock_count' => $variant['stock_count'] ?? 0,
                'sold_count' => 0,
            ]);
        }
    }

    /**
     * Update product variant
     */
    public function updateVariant(int $variantId, array $data): ProductInventoryDetail
    {
        $variant = ProductInventoryDetail::findOrFail($variantId);

        $variant->update([
            'color' => $data['color_id'] ?? $variant->color,
            'size' => $data['size_id'] ?? $variant->size,
            'additional_price' => $data['additional_price'] ?? $variant->additional_price,
            'add_cost' => $data['add_cost'] ?? $variant->add_cost,
            'image' => $data['image_id'] ?? $variant->image,
            'stock_count' => $data['stock_count'] ?? $variant->stock_count,
        ]);

        return $variant->fresh();
    }

    /**
     * Delete product variant
     */
    public function deleteVariant(int $variantId): bool
    {
        return ProductInventoryDetail::findOrFail($variantId)->delete();
    }

    /**
     * Update inventory stock
     */
    public function updateStock(int $productId, int $quantity, string $operation = 'set', ?int $variantId = null): void
    {
        if ($variantId) {
            $variant = ProductInventoryDetail::where('product_id', $productId)
                ->where('id', $variantId)
                ->firstOrFail();

            switch ($operation) {
                case 'add':
                    $variant->increment('stock_count', $quantity);
                    break;
                case 'subtract':
                    $variant->decrement('stock_count', min($quantity, $variant->stock_count));
                    break;
                default:
                    $variant->update(['stock_count' => $quantity]);
            }
        } else {
            $inventory = ProductInventory::where('product_id', $productId)->firstOrFail();

            switch ($operation) {
                case 'add':
                    $inventory->increment('stock_count', $quantity);
                    break;
                case 'subtract':
                    $inventory->decrement('stock_count', min($quantity, $inventory->stock_count));
                    break;
                default:
                    $inventory->update(['stock_count' => $quantity]);
            }
        }
    }

    /**
     * Get related products
     */
    public function getRelatedProducts(int $productId, int $limit = 4): \Illuminate\Database\Eloquent\Collection
    {
        $product = Product::with('category')->find($productId);

        if (!$product || !$product->category) {
            return collect();
        }

        return Product::with(['category', 'inventory'])
            ->where('id', '!=', $productId)
            ->where('status_id', 1)
            ->whereHas('product_category', function ($query) use ($product) {
                $query->where('category_id', $product->category->id);
            })
            ->limit($limit)
            ->get();
    }

    /**
     * Search products
     */
    public function searchProducts(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Product::with(['category', 'inventory'])
            ->where('status_id', 1)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('summary', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%")
                    ->orWhereHas('tag', function ($tagQuery) use ($query) {
                        $tagQuery->where('tag_name', 'like', "%{$query}%");
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters): void
    {
        // Category filter
        if (!empty($filters['category_id'])) {
            $query->whereHas('product_category', function ($q) use ($filters) {
                $q->where('category_id', $filters['category_id']);
            });
        }

        // Sub category filter
        if (!empty($filters['sub_category_id'])) {
            $query->whereHas('product_sub_category', function ($q) use ($filters) {
                $q->where('sub_category_id', $filters['sub_category_id']);
            });
        }

        // Child category filter
        if (!empty($filters['child_category_id'])) {
            $query->whereHas('product_child_category', function ($q) use ($filters) {
                $q->where('child_category_id', $filters['child_category_id']);
            });
        }

        // Brand filter
        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }

        // Price range filter
        if (!empty($filters['min_price'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('sale_price', '>=', $filters['min_price'])
                    ->orWhere(function ($q2) use ($filters) {
                        $q2->whereNull('sale_price')
                            ->where('price', '>=', $filters['min_price']);
                    });
            });
        }

        if (!empty($filters['max_price'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('sale_price', '<=', $filters['max_price'])
                    ->orWhere(function ($q2) use ($filters) {
                        $q2->whereNull('sale_price')
                            ->where('price', '<=', $filters['max_price']);
                    });
            });
        }

        // Color filter
        if (!empty($filters['color_id'])) {
            $query->whereHas('inventoryDetail', function ($q) use ($filters) {
                $q->where('color', $filters['color_id']);
            });
        }

        // Size filter
        if (!empty($filters['size_id'])) {
            $query->whereHas('inventoryDetail', function ($q) use ($filters) {
                $q->where('size', $filters['size_id']);
            });
        }

        // In stock filter
        if (!empty($filters['in_stock'])) {
            $query->where(function ($q) {
                $q->whereHas('inventory', function ($invQ) {
                    $invQ->where('stock_count', '>', 0);
                })->orWhereHas('inventoryDetail', function ($detQ) {
                    $detQ->where('stock_count', '>', 0);
                });
            });
        }

        // On sale filter
        if (!empty($filters['on_sale'])) {
            $query->whereNotNull('sale_price')
                ->whereColumn('sale_price', '<', 'price');
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('summary', 'like', "%{$search}%");
            });
        }

        // Badge filter
        if (!empty($filters['badge_id'])) {
            $query->where('badge_id', $filters['badge_id']);
        }

        // Status filter (admin only)
        if (isset($filters['status_id'])) {
            $query->where('status_id', $filters['status_id']);
        }
    }
}
