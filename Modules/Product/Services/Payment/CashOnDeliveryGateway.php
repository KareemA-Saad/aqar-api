<?php

declare(strict_types=1);

namespace Modules\Product\Services\Payment;

use Illuminate\Http\Request;
use Modules\Product\Entities\ProductOrder;

/**
 * Cash on Delivery Gateway
 *
 * Handles Cash on Delivery payment method.
 */
class CashOnDeliveryGateway implements PaymentGatewayInterface
{
    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return 'cod';
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Cash on Delivery';
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        // COD is typically always available
        // Could check tenant settings here
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function processPayment(ProductOrder $order, array $data): array
    {
        // COD doesn't require online payment processing
        // The order is created with pending payment status

        return [
            'status' => 'pending',
            'redirect_url' => null,
            'client_secret' => null,
            'message' => 'Order placed successfully. Payment will be collected on delivery.',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function handleWebhook(Request $request): void
    {
        // COD doesn't have webhooks
        // Payment is marked as paid when delivered
    }

    /**
     * {@inheritdoc}
     */
    public function verifyPayment(string $transactionId): array
    {
        // For COD, we check the order status directly
        $order = ProductOrder::where('payment_track', $transactionId)
            ->orWhere('id', $transactionId)
            ->first();

        if (!$order) {
            return [
                'status' => 'not_found',
                'paid' => false,
            ];
        }

        return [
            'status' => $order->payment_status,
            'paid' => $order->payment_status === 'paid',
            'amount' => $order->total_amount,
            'currency' => config('app.currency', 'USD'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function refund(ProductOrder $order, ?float $amount = null): array
    {
        // COD refunds are handled manually
        // Just update the order status

        $order->update([
            'payment_status' => 'refunded',
        ]);

        return [
            'success' => true,
            'refund_id' => 'COD-REFUND-' . $order->id,
            'amount' => $amount ?? $order->total_amount,
            'status' => 'completed',
            'message' => 'COD refund processed. Please handle the actual refund manually.',
        ];
    }

    /**
     * Mark COD order as paid (called when delivered).
     *
     * @param ProductOrder $order
     * @return array
     */
    public function markAsPaid(ProductOrder $order): array
    {
        $order->update([
            'payment_status' => 'paid',
        ]);

        return [
            'success' => true,
            'message' => 'Order marked as paid',
        ];
    }
}
