<?php

declare(strict_types=1);

namespace App\Http\Resources\Tenant;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Wishlist Resource for API responses.
 */
#[OA\Schema(
    schema: 'WishlistResource',
    title: 'Wishlist Resource',
    description: 'Wishlist item resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'product_id', type: 'integer', example: 10),
        new OA\Property(
            property: 'product',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 10),
                new OA\Property(property: 'name', type: 'string', example: 'Premium Widget'),
                new OA\Property(property: 'slug', type: 'string', example: 'premium-widget'),
                new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
                new OA\Property(property: 'image', type: 'string', example: 'https://example.com/product.jpg'),
                new OA\Property(property: 'in_stock', type: 'boolean', example: true),
            ],
            type: 'object',
            nullable: true
        ),
        new OA\Property(property: 'added_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
    ]
)]
class WishlistResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle both Eloquent model and stdClass from raw DB queries
        $data = is_object($this->resource) ? (array) $this->resource : $this->resource;

        $productData = null;

        // If product relation is loaded or product data exists
        if (isset($data['product']) && is_object($data['product'])) {
            $product = (array) $data['product'];
            $productData = [
                'id' => $product['id'] ?? null,
                'name' => $product['name'] ?? null,
                'slug' => $product['slug'] ?? null,
                'price' => $product['price'] ?? null,
                'image' => $product['image'] ?? null,
                'in_stock' => (bool) ($product['stock'] ?? $product['in_stock'] ?? false),
            ];
        }

        return [
            'id' => $data['id'] ?? $this->id,
            'product_id' => $data['product_id'] ?? $this->product_id,
            'product' => $productData,
            'added_at' => isset($data['created_at'])
                ? (is_string($data['created_at']) ? $data['created_at'] : $data['created_at']->toISOString())
                : ($this->created_at?->toISOString() ?? null),
        ];
    }
}
