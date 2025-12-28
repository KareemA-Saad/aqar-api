<?php

declare(strict_types=1);

namespace Modules\Product\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Product\Entities\Cart;
use Modules\Product\Entities\ProductOrder;
use Modules\Product\Entities\ProductOrderDetails;
use Modules\Product\Services\Payment\PaymentGatewayFactory;

/**
 * Checkout Service
 *
 * Handles the checkout process including order creation and payment processing.
 */
class CheckoutService
{
    public function __construct(
        private readonly ShippingService $shippingService,
        private readonly TaxService $taxService,
    ) {}

    /**
     * Get available payment methods for the tenant.
     *
     * @return array
     */
    public function getAvailablePaymentMethods(): array
    {
        // Get enabled payment methods from tenant settings
        $methods = [];

        // Check if Stripe is configured
        if ($this->isPaymentMethodEnabled('stripe')) {
            $methods[] = [
                'id' => 'stripe',
                'name' => 'Credit/Debit Card',
                'description' => 'Pay securely with your credit or debit card',
                'icon' => 'stripe',
                'is_default' => true,
            ];
        }

        // Check if PayPal is configured
        if ($this->isPaymentMethodEnabled('paypal')) {
            $methods[] = [
                'id' => 'paypal',
                'name' => 'PayPal',
                'description' => 'Pay with your PayPal account',
                'icon' => 'paypal',
                'is_default' => false,
            ];
        }

        // Cash on Delivery is typically always available
        if ($this->isPaymentMethodEnabled('cod')) {
            $methods[] = [
                'id' => 'cod',
                'name' => 'Cash on Delivery',
                'description' => 'Pay when you receive your order',
                'icon' => 'cash',
                'is_default' => false,
            ];
        }

        return $methods;
    }

    /**
     * Check if a payment method is enabled.
     *
     * @param string $method
     * @return bool
     */
    protected function isPaymentMethodEnabled(string $method): bool
    {
        // Check tenant settings for payment method configuration
        // For now, return true for basic methods
        return match ($method) {
            'stripe' => !empty(config('services.stripe.key')),
            'paypal' => !empty(config('services.paypal.client_id')),
            'cod' => true, // COD is typically always available
            default => false,
        };
    }

    /**
     * Process the checkout and create an order.
     *
     * @param Cart $cart
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function processCheckout(Cart $cart, array $data): array
    {
        return DB::transaction(function () use ($cart, $data) {
            // Calculate shipping
            $shippingCost = $this->shippingService->calculateShipping(
                (int) $data['shipping_method_id'],
                $cart->subtotal
            );

            // Calculate tax
            $taxData = $this->taxService->calculateTax(
                (int) $data['country_id'],
                isset($data['state_id']) ? (int) $data['state_id'] : null,
                $cart->subtotal - $cart->discount_amount
            );

            // Calculate totals
            $subtotal = $cart->subtotal;
            $couponDiscount = $cart->discount_amount;
            $taxAmount = $taxData['tax_amount'];
            $totalAmount = $subtotal - $couponDiscount + $shippingCost + $taxAmount;

            // Generate order number
            $orderNumber = $this->generateOrderNumber();

            // Create the order
            $order = ProductOrder::create([
                'user_id' => $cart->user_id,
                'payment_track' => $orderNumber,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state_id'] ?? null,
                'country' => $data['country_id'],
                'zipcode' => $data['zipcode'] ?? null,
                'message' => $data['notes'] ?? null,
                'coupon' => $cart->coupon_code,
                'coupon_discounted' => $couponDiscount,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'selected_shipping_option' => $data['shipping_method_id'],
                'payment_gateway' => $data['payment_method'],
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            // Create order items
            foreach ($cart->items as $item) {
                ProductOrderDetails::create([
                    'product_order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name ?? 'Product',
                    'variant' => $item->variant_name,
                    'variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price,
                ]);

                // Decrease stock
                $this->decreaseStock($item);
            }

            // Process payment
            $paymentResult = $this->processPayment($order, $data);

            return [
                'order' => $order->fresh(['sale_details', 'getCountry', 'getState']),
                'payment' => $paymentResult,
            ];
        });
    }

    /**
     * Generate a unique order number.
     *
     * @return string
     */
    protected function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $timestamp = now()->format('ymd');
        $random = strtoupper(Str::random(6));

        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Decrease stock for an item.
     *
     * @param \Modules\Product\Entities\CartItem $item
     */
    protected function decreaseStock($item): void
    {
        if ($item->variant_id) {
            // Decrease variant stock
            $variant = \Modules\Product\Entities\ProductInventoryDetail::find($item->variant_id);
            if ($variant) {
                $variant->decrement('stock_count', $item->quantity);
            }
        } else {
            // Decrease product stock
            $product = \Modules\Product\Entities\Product::find($item->product_id);
            if ($product) {
                $product->decrement('stock_count', $item->quantity);
            }
        }
    }

    /**
     * Process payment based on the selected method.
     *
     * @param ProductOrder $order
     * @param array $data
     * @return array
     * @throws \Exception
     */
    protected function processPayment(ProductOrder $order, array $data): array
    {
        $paymentMethod = $data['payment_method'];

        // Handle Cash on Delivery
        if ($paymentMethod === 'cod') {
            return [
                'status' => 'pending',
                'redirect_url' => null,
                'client_secret' => null,
                'message' => 'Order placed. Pay on delivery.',
            ];
        }

        // Get payment gateway
        try {
            $gateway = PaymentGatewayFactory::create($paymentMethod);
            return $gateway->processPayment($order, $data);
        } catch (\Exception $e) {
            // If payment fails, we should still have the order
            // Update payment status to failed
            $order->update(['payment_status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Handle payment webhook from gateway.
     *
     * @param string $gateway
     * @param \Illuminate\Http\Request $request
     * @return void
     * @throws \Exception
     */
    public function handleWebhook(string $gateway, $request): void
    {
        $paymentGateway = PaymentGatewayFactory::create($gateway);
        $paymentGateway->handleWebhook($request);
    }

    /**
     * Verify payment status for an order.
     *
     * @param string $orderNumber
     * @return ProductOrder|null
     */
    public function verifyPayment(string $orderNumber): ?ProductOrder
    {
        return ProductOrder::where('payment_track', $orderNumber)
            ->with(['sale_details', 'getCountry', 'getState'])
            ->first();
    }
}
