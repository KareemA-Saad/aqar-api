<?php

declare(strict_types=1);

namespace Modules\Product\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Product\Http\Requests\CheckoutRequest;
use Modules\Product\Http\Resources\OrderResource;
use Modules\Product\Services\CartService;
use Modules\Product\Services\CheckoutService;
use Modules\Product\Services\ShippingService;
use Modules\Product\Services\TaxService;
use OpenApi\Attributes as OA;

/**
 * Checkout Controller
 *
 * Handles the checkout process including shipping calculation,
 * tax calculation, payment processing, and order creation.
 *
 * @package Modules\Product\Http\Controllers\Api\V1\Frontend
 */
#[OA\Tag(
    name: 'Tenant Frontend - Checkout',
    description: 'Checkout process endpoints for completing purchases'
)]
final class CheckoutController extends BaseApiController
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutService $checkoutService,
        private readonly ShippingService $shippingService,
        private readonly TaxService $taxService,
    ) {}

    /**
     * Get available shipping methods for the cart.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/checkout/shipping-methods',
        summary: 'Get available shipping methods',
        description: 'Returns available shipping methods based on the delivery address',
        tags: ['Tenant Frontend - Checkout']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'X-Cart-Token',
        in: 'header',
        required: false,
        description: 'Guest cart token (for non-authenticated users)',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'country_id',
        in: 'query',
        required: true,
        description: 'Delivery country ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'state_id',
        in: 'query',
        required: false,
        description: 'Delivery state ID',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Shipping methods retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Shipping methods retrieved'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'methods', type: 'array', items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'name', type: 'string'),
                            new OA\Property(property: 'cost', type: 'number'),
                            new OA\Property(property: 'estimated_days', type: 'string'),
                            new OA\Property(property: 'is_default', type: 'boolean'),
                        ]
                    )),
                ]),
            ]
        )
    )]
    public function shippingMethods(Request $request): JsonResponse
    {
        $request->validate([
            'country_id' => 'required|integer',
            'state_id' => 'nullable|integer',
        ]);

        $cart = $this->cartService->getCart($request);

        if (!$cart || $cart->items->isEmpty()) {
            return $this->errorResponse('Cart is empty', 400);
        }

        $methods = $this->shippingService->getAvailableMethods(
            (int) $request->country_id,
            $request->state_id ? (int) $request->state_id : null,
            $cart->subtotal
        );

        return $this->successResponse([
            'methods' => $methods,
        ], 'Shipping methods retrieved');
    }

    /**
     * Get available payment methods.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/checkout/payment-methods',
        summary: 'Get available payment methods',
        description: 'Returns available payment methods configured for the tenant',
        tags: ['Tenant Frontend - Checkout']
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
        description: 'Payment methods retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Payment methods retrieved'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'methods', type: 'array', items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'id', type: 'string', example: 'stripe'),
                            new OA\Property(property: 'name', type: 'string', example: 'Credit Card'),
                            new OA\Property(property: 'description', type: 'string'),
                            new OA\Property(property: 'icon', type: 'string', nullable: true),
                            new OA\Property(property: 'is_default', type: 'boolean'),
                        ]
                    )),
                ]),
            ]
        )
    )]
    public function paymentMethods(): JsonResponse
    {
        $methods = $this->checkoutService->getAvailablePaymentMethods();

        return $this->successResponse([
            'methods' => $methods,
        ], 'Payment methods retrieved');
    }

    /**
     * Calculate checkout totals.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/checkout/calculate',
        summary: 'Calculate checkout totals',
        description: 'Calculate shipping, tax, and total amounts based on selected options',
        tags: ['Tenant Frontend - Checkout']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'X-Cart-Token',
        in: 'header',
        required: false,
        description: 'Guest cart token',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['country_id'],
            properties: [
                new OA\Property(property: 'country_id', type: 'integer', example: 1),
                new OA\Property(property: 'state_id', type: 'integer', example: 5),
                new OA\Property(property: 'shipping_method_id', type: 'integer', example: 1),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Checkout totals calculated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'subtotal', type: 'number', example: 99.99),
                    new OA\Property(property: 'coupon_discount', type: 'number', example: 10.00),
                    new OA\Property(property: 'shipping_cost', type: 'number', example: 5.99),
                    new OA\Property(property: 'tax_amount', type: 'number', example: 8.50),
                    new OA\Property(property: 'tax_percentage', type: 'number', example: 8.5),
                    new OA\Property(property: 'total', type: 'number', example: 104.48),
                ]),
            ]
        )
    )]
    public function calculate(Request $request): JsonResponse
    {
        $request->validate([
            'country_id' => 'required|integer',
            'state_id' => 'nullable|integer',
            'shipping_method_id' => 'nullable|integer',
        ]);

        $cart = $this->cartService->getCart($request);

        if (!$cart || $cart->items->isEmpty()) {
            return $this->errorResponse('Cart is empty', 400);
        }

        // Calculate shipping
        $shippingCost = 0;
        if ($request->shipping_method_id) {
            $shippingCost = $this->shippingService->calculateShipping(
                (int) $request->shipping_method_id,
                $cart->subtotal
            );
        }

        // Calculate tax
        $taxData = $this->taxService->calculateTax(
            (int) $request->country_id,
            $request->state_id ? (int) $request->state_id : null,
            $cart->subtotal - $cart->discount_amount
        );

        // Calculate total
        $subtotal = $cart->subtotal;
        $couponDiscount = $cart->discount_amount;
        $total = $subtotal - $couponDiscount + $shippingCost + $taxData['tax_amount'];

        return $this->successResponse([
            'subtotal' => round($subtotal, 2),
            'coupon_discount' => round($couponDiscount, 2),
            'shipping_cost' => round($shippingCost, 2),
            'tax_amount' => round($taxData['tax_amount'], 2),
            'tax_percentage' => $taxData['tax_percentage'],
            'total' => round($total, 2),
        ]);
    }

    /**
     * Process checkout and create order.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/checkout',
        summary: 'Process checkout',
        description: 'Process the checkout, create the order, and initiate payment',
        tags: ['Tenant Frontend - Checkout']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'X-Cart-Token',
        in: 'header',
        required: false,
        description: 'Guest cart token',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'email', 'phone', 'address', 'city', 'country_id', 'shipping_method_id', 'payment_method'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                new OA\Property(property: 'phone', type: 'string', example: '+1234567890'),
                new OA\Property(property: 'address', type: 'string', example: '123 Main St'),
                new OA\Property(property: 'city', type: 'string', example: 'New York'),
                new OA\Property(property: 'state_id', type: 'integer', example: 5),
                new OA\Property(property: 'country_id', type: 'integer', example: 1),
                new OA\Property(property: 'zipcode', type: 'string', example: '10001'),
                new OA\Property(property: 'shipping_method_id', type: 'integer', example: 1),
                new OA\Property(property: 'payment_method', type: 'string', enum: ['stripe', 'paypal', 'cod'], example: 'stripe'),
                new OA\Property(property: 'notes', type: 'string', example: 'Please leave at door'),
                new OA\Property(property: 'payment_token', type: 'string', description: 'Payment token from frontend (for Stripe/PayPal)'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Order created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Order created successfully'),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'order', ref: '#/components/schemas/OrderResource'),
                    new OA\Property(property: 'payment', type: 'object', properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'pending'),
                        new OA\Property(property: 'redirect_url', type: 'string', nullable: true, description: 'Redirect URL for external payment'),
                        new OA\Property(property: 'client_secret', type: 'string', nullable: true, description: 'Stripe client secret for frontend'),
                    ]),
                ]),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Checkout failed',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Cart is empty'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $cart = $this->cartService->getCart($request);

        if (!$cart || $cart->items->isEmpty()) {
            return $this->errorResponse('Cart is empty', 400);
        }

        // Validate stock availability
        $stockValidation = $this->cartService->validateStock($cart);
        if (!$stockValidation['valid']) {
            return $this->errorResponse($stockValidation['message'], 400);
        }

        try {
            $result = $this->checkoutService->processCheckout($cart, $request->validated());

            // Clear the cart after successful order creation
            $this->cartService->clearCart($request);

            return $this->successResponse([
                'order' => new OrderResource($result['order']),
                'payment' => $result['payment'],
            ], 'Order created successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Handle payment webhook/callback.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/checkout/webhook/{gateway}',
        summary: 'Payment webhook handler',
        description: 'Handle payment gateway webhooks (Stripe, PayPal, etc.)',
        tags: ['Tenant Frontend - Checkout']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'gateway',
        in: 'path',
        required: true,
        description: 'Payment gateway name',
        schema: new OA\Schema(type: 'string', enum: ['stripe', 'paypal'])
    )]
    #[OA\Response(
        response: 200,
        description: 'Webhook processed',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
            ]
        )
    )]
    public function webhook(Request $request, string $gateway): JsonResponse
    {
        try {
            $this->checkoutService->handleWebhook($gateway, $request);
            return $this->successResponse(null, 'Webhook processed');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Verify payment status.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/checkout/verify/{orderNumber}',
        summary: 'Verify payment status',
        description: 'Verify the payment status of an order after redirect from payment gateway',
        tags: ['Tenant Frontend - Checkout']
    )]
    #[OA\Parameter(
        name: 'tenant',
        in: 'path',
        required: true,
        description: 'Tenant ID',
        schema: new OA\Schema(type: 'string', example: 'acme-corp')
    )]
    #[OA\Parameter(
        name: 'orderNumber',
        in: 'path',
        required: true,
        description: 'Order number',
        schema: new OA\Schema(type: 'string', example: 'ORD-00000001')
    )]
    #[OA\Response(
        response: 200,
        description: 'Payment status retrieved',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'data', type: 'object', properties: [
                    new OA\Property(property: 'order', ref: '#/components/schemas/OrderResource'),
                    new OA\Property(property: 'payment_status', type: 'string', example: 'paid'),
                ]),
            ]
        )
    )]
    public function verifyPayment(string $orderNumber): JsonResponse
    {
        $order = $this->checkoutService->verifyPayment($orderNumber);

        if (!$order) {
            return $this->errorResponse('Order not found', 404);
        }

        return $this->successResponse([
            'order' => new OrderResource($order),
            'payment_status' => $order->payment_status,
        ]);
    }
}
