<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Http\Requests\AddToCartRequest;
use Modules\Product\Http\Requests\UpdateCartItemRequest;
use Modules\Product\Http\Requests\ApplyCouponRequest;
use Modules\Product\Http\Requests\CartAddressRequest;
use Modules\Product\Http\Resources\CartResource;
use Modules\Product\Http\Resources\CartItemResource;
use Modules\Product\Services\CartService;
use OpenApi\Attributes as OA;

/**
 * Cart Controller
 *
 * Handles shopping cart operations for both authenticated users and guests.
 * Guest carts use X-Cart-Token header for identification.
 *
 * @package Modules\Product\Http\Controllers\Api\V1\Frontend
 */
#[OA\Tag(
    name: 'Tenant Frontend - Cart',
    description: 'Shopping cart management endpoints'
)]
final class CartController extends BaseApiController
{
    public function __construct(
        private readonly CartService $cartService,
    ) {}

    /**
     * Get current cart.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/cart',
        summary: 'Get current cart',
        description: 'Get the current shopping cart with all items. For guests, include X-Cart-Token header.',
        tags: ['Tenant Frontend - Cart']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'X-Cart-Token',
        in: 'header',
        description: 'Guest cart token (for non-authenticated users)',
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\Response(
        response: 200,
        description: 'Cart retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Cart retrieved successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/CartResource'),
            ]
        )
    )]
    public function index(): JsonResponse
    {
        $cart = $this->cartService->getCart();

        if (!$cart) {
            return $this->success([
                'id' => null,
                'items_count' => 0,
                'subtotal' => 0,
                'discount' => ['coupon_code' => null, 'discount_type' => null, 'discount_amount' => 0],
                'shipping' => ['method_id' => null, 'cost' => 0, 'address' => null],
                'billing_address' => null,
                'total' => 0,
                'items' => [],
            ], 'Cart is empty');
        }

        $cart->load(['items.product', 'items.variant.productColor', 'items.variant.productSize']);

        $response = $this->success(
            new CartResource($cart),
            'Cart retrieved successfully'
        );

        return $this->addCartTokenToResponse($response);
    }

    /**
     * Add item to cart.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/cart/items',
        summary: 'Add item to cart',
        description: 'Add a product to the shopping cart. Creates a new cart if none exists.',
        tags: ['Tenant Frontend - Cart']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'X-Cart-Token',
        in: 'header',
        description: 'Guest cart token (for non-authenticated users)',
        schema: new OA\Schema(type: 'string', format: 'uuid')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['product_id'],
            properties: [
                new OA\Property(property: 'product_id', type: 'integer', example: 1),
                new OA\Property(property: 'variant_id', type: 'integer', example: 5, nullable: true),
                new OA\Property(property: 'quantity', type: 'integer', example: 2, default: 1),
                new OA\Property(
                    property: 'options',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'color_id', type: 'integer'),
                        new OA\Property(property: 'size_id', type: 'integer'),
                    ],
                    nullable: true
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Item added to cart successfully',
        headers: [
            new OA\Header(
                header: 'X-Cart-Token',
                description: 'New cart token for guests (only on first request)',
                schema: new OA\Schema(type: 'string', format: 'uuid')
            ),
        ],
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Item added to cart'),
                new OA\Property(property: 'data', ref: '#/components/schemas/CartItemResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error or insufficient stock',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Insufficient stock. Only 5 available.'),
            ]
        )
    )]
    public function addItem(AddToCartRequest $request): JsonResponse
    {
        try {
            $item = $this->cartService->addItem(
                productId: $request->input('product_id'),
                quantity: $request->input('quantity', 1),
                variantId: $request->input('variant_id'),
                options: $request->input('options', [])
            );

            $item->load(['product', 'variant.productColor', 'variant.productSize']);

            $response = $this->success(
                new CartItemResource($item),
                'Item added to cart'
            );

            return $this->addCartTokenToResponse($response);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Update cart item quantity.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/cart/items/{itemId}',
        summary: 'Update cart item quantity',
        description: 'Update the quantity of an item in the cart. Set quantity to 0 to remove.',
        tags: ['Tenant Frontend - Cart']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'itemId',
        in: 'path',
        required: true,
        description: 'Cart item ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['quantity'],
            properties: [
                new OA\Property(property: 'quantity', type: 'integer', example: 3),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Cart item updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Cart item updated'),
                new OA\Property(property: 'data', ref: '#/components/schemas/CartItemResource'),
            ]
        )
    )]
    public function updateItem(UpdateCartItemRequest $request, int $itemId): JsonResponse
    {
        try {
            $item = $this->cartService->updateItem($itemId, $request->input('quantity'));
            $item->load(['product', 'variant.productColor', 'variant.productSize']);

            return $this->success(
                new CartItemResource($item),
                'Cart item updated'
            );
        } catch (\InvalidArgumentException $e) {
            // Item was removed (quantity 0)
            return $this->success(null, $e->getMessage());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Cart item not found', 404);
        }
    }

    /**
     * Remove item from cart.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/cart/items/{itemId}',
        summary: 'Remove item from cart',
        description: 'Remove an item from the shopping cart',
        tags: ['Tenant Frontend - Cart']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Parameter(
        name: 'itemId',
        in: 'path',
        required: true,
        description: 'Cart item ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Item removed from cart',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Item removed from cart'),
            ]
        )
    )]
    public function removeItem(int $itemId): JsonResponse
    {
        try {
            $this->cartService->removeItem($itemId);

            return $this->success(null, 'Item removed from cart');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Cart item not found', 404);
        }
    }

    /**
     * Clear cart.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/cart',
        summary: 'Clear cart',
        description: 'Remove all items from the shopping cart',
        tags: ['Tenant Frontend - Cart']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Response(
        response: 200,
        description: 'Cart cleared',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Cart cleared'),
            ]
        )
    )]
    public function clear(): JsonResponse
    {
        $this->cartService->clearCart();

        return $this->success(null, 'Cart cleared');
    }

    /**
     * Apply coupon to cart.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/cart/coupon',
        summary: 'Apply coupon',
        description: 'Apply a coupon code to the cart for a discount',
        tags: ['Tenant Frontend - Cart']
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
            required: ['coupon_code'],
            properties: [
                new OA\Property(property: 'coupon_code', type: 'string', example: 'SAVE20'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Coupon applied successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Coupon applied successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'coupon_code', type: 'string', example: 'SAVE20'),
                        new OA\Property(property: 'discount_type', type: 'string', example: 'percentage'),
                        new OA\Property(property: 'discount_amount', type: 'number', example: 15.00),
                    ]
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Invalid coupon',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Invalid or expired coupon code'),
            ]
        )
    )]
    public function applyCoupon(ApplyCouponRequest $request): JsonResponse
    {
        try {
            $discount = $this->cartService->applyCoupon($request->input('coupon_code'));

            return $this->success($discount, 'Coupon applied successfully');
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Remove coupon from cart.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/cart/coupon',
        summary: 'Remove coupon',
        description: 'Remove the applied coupon from the cart',
        tags: ['Tenant Frontend - Cart']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Response(
        response: 200,
        description: 'Coupon removed',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Coupon removed'),
            ]
        )
    )]
    public function removeCoupon(): JsonResponse
    {
        $this->cartService->removeCoupon();

        return $this->success(null, 'Coupon removed');
    }

    /**
     * Update cart addresses.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/cart/addresses',
        summary: 'Update cart addresses',
        description: 'Update shipping and billing addresses for the cart',
        tags: ['Tenant Frontend - Cart']
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
            properties: [
                new OA\Property(
                    property: 'shipping_address',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                        new OA\Property(property: 'phone', type: 'string', example: '+1234567890'),
                        new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
                        new OA\Property(property: 'address', type: 'string', example: '123 Main St'),
                        new OA\Property(property: 'city', type: 'string', example: 'New York'),
                        new OA\Property(property: 'state', type: 'string', example: 'NY'),
                        new OA\Property(property: 'country_id', type: 'integer', example: 1),
                        new OA\Property(property: 'postal_code', type: 'string', example: '10001'),
                    ]
                ),
                new OA\Property(
                    property: 'billing_address',
                    type: 'object',
                    nullable: true
                ),
                new OA\Property(property: 'same_as_shipping', type: 'boolean', example: true),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Addresses updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Addresses updated'),
                new OA\Property(property: 'data', ref: '#/components/schemas/CartResource'),
            ]
        )
    )]
    public function updateAddresses(CartAddressRequest $request): JsonResponse
    {
        $shippingAddress = $request->input('shipping_address');
        $billingAddress = $request->input('billing_address');

        if ($shippingAddress) {
            $this->cartService->setShippingAddress($shippingAddress);
        }

        if ($request->input('same_as_shipping') && $shippingAddress) {
            $this->cartService->setBillingAddress($shippingAddress);
        } elseif ($billingAddress) {
            $this->cartService->setBillingAddress($billingAddress);
        }

        $cart = $this->cartService->getCart();
        $cart->load(['items.product', 'items.variant']);

        return $this->success(
            new CartResource($cart),
            'Addresses updated'
        );
    }

    /**
     * Get cart summary.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/cart/summary',
        summary: 'Get cart summary',
        description: 'Get a summary of the cart totals',
        tags: ['Tenant Frontend - Cart']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-store')
    )]
    #[OA\Response(
        response: 200,
        description: 'Cart summary retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Cart summary retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'items_count', type: 'integer', example: 3),
                        new OA\Property(property: 'subtotal', type: 'number', example: 150.00),
                        new OA\Property(property: 'discount', type: 'number', example: 15.00),
                        new OA\Property(property: 'shipping', type: 'number', example: 10.00),
                        new OA\Property(property: 'total', type: 'number', example: 145.00),
                        new OA\Property(property: 'coupon_code', type: 'string', example: 'SAVE10', nullable: true),
                    ]
                ),
            ]
        )
    )]
    public function summary(): JsonResponse
    {
        $summary = $this->cartService->getCartSummary();

        return $this->success($summary, 'Cart summary retrieved');
    }

    /**
     * Merge guest cart on login.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/cart/merge',
        summary: 'Merge guest cart',
        description: 'Merge a guest cart into the authenticated user cart on login',
        security: [['sanctum' => []]],
        tags: ['Tenant Frontend - Cart']
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
            required: ['guest_token'],
            properties: [
                new OA\Property(property: 'guest_token', type: 'string', format: 'uuid', example: '123e4567-e89b-12d3-a456-426614174000'),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Cart merged successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Cart merged successfully'),
                new OA\Property(property: 'data', ref: '#/components/schemas/CartResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated'
    )]
    public function mergeCart(Request $request): JsonResponse
    {
        $user = auth('api_tenant_user')->user();

        if (!$user) {
            return $this->error('Authentication required', 401);
        }

        $guestToken = $request->input('guest_token');

        if (!$guestToken) {
            return $this->error('Guest token is required', 422);
        }

        $cart = $this->cartService->mergeGuestCart($guestToken, $user->id);
        $cart->load(['items.product', 'items.variant']);

        return $this->success(
            new CartResource($cart),
            'Cart merged successfully'
        );
    }

    /**
     * Add cart token to response header for new guest carts.
     */
    protected function addCartTokenToResponse(JsonResponse $response): JsonResponse
    {
        $newToken = $this->cartService->getNewCartToken();

        if ($newToken) {
            $response->header('X-Cart-Token', $newToken);
        }

        return $response;
    }
}
