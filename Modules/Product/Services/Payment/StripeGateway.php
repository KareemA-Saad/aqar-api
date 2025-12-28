<?php

declare(strict_types=1);

namespace Modules\Product\Services\Payment;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Product\Entities\ProductOrder;

/**
 * Stripe Payment Gateway
 *
 * Handles Stripe payment processing using Payment Intents.
 */
class StripeGateway implements PaymentGatewayInterface
{
    protected ?\Stripe\StripeClient $stripe = null;

    public function __construct()
    {
        $secretKey = config('services.stripe.secret');
        if ($secretKey) {
            $this->stripe = new \Stripe\StripeClient($secretKey);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return 'stripe';
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Credit/Debit Card (Stripe)';
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return !empty(config('services.stripe.key')) && 
               !empty(config('services.stripe.secret'));
    }

    /**
     * {@inheritdoc}
     */
    public function processPayment(ProductOrder $order, array $data): array
    {
        if (!$this->stripe) {
            throw new \Exception('Stripe is not configured');
        }

        try {
            // Create or retrieve Payment Intent
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int) ($order->total_amount * 100), // Amount in cents
                'currency' => strtolower(config('app.currency', 'usd')),
                'description' => "Order #{$order->payment_track}",
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->payment_track,
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            // Update order with payment intent ID
            $order->update([
                'transaction_id' => $paymentIntent->id,
            ]);

            return [
                'status' => 'requires_confirmation',
                'client_secret' => $paymentIntent->client_secret,
                'redirect_url' => null,
                'payment_intent_id' => $paymentIntent->id,
            ];

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe payment error: ' . $e->getMessage(), [
                'order_id' => $order->id,
            ]);
            throw new \Exception('Payment processing failed: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handleWebhook(Request $request): void
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            throw new \Exception('Invalid payload');
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            throw new \Exception('Invalid signature');
        }

        // Handle the event
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $paymentIntent = $event->data->object;
                $this->handlePaymentSuccess($paymentIntent);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                $this->handlePaymentFailure($paymentIntent);
                break;

            default:
                Log::info('Unhandled Stripe webhook event: ' . $event->type);
        }
    }

    /**
     * Handle successful payment.
     *
     * @param \Stripe\PaymentIntent $paymentIntent
     */
    protected function handlePaymentSuccess($paymentIntent): void
    {
        $order = ProductOrder::where('transaction_id', $paymentIntent->id)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'in_progress',
            ]);

            Log::info('Stripe payment successful', [
                'order_id' => $order->id,
                'payment_intent' => $paymentIntent->id,
            ]);
        }
    }

    /**
     * Handle failed payment.
     *
     * @param \Stripe\PaymentIntent $paymentIntent
     */
    protected function handlePaymentFailure($paymentIntent): void
    {
        $order = ProductOrder::where('transaction_id', $paymentIntent->id)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'failed',
            ]);

            Log::warning('Stripe payment failed', [
                'order_id' => $order->id,
                'payment_intent' => $paymentIntent->id,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function verifyPayment(string $transactionId): array
    {
        if (!$this->stripe) {
            throw new \Exception('Stripe is not configured');
        }

        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($transactionId);

            return [
                'status' => $paymentIntent->status,
                'paid' => $paymentIntent->status === 'succeeded',
                'amount' => $paymentIntent->amount / 100,
                'currency' => $paymentIntent->currency,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to verify payment: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function refund(ProductOrder $order, ?float $amount = null): array
    {
        if (!$this->stripe) {
            throw new \Exception('Stripe is not configured');
        }

        if (!$order->transaction_id) {
            throw new \Exception('No transaction ID found for this order');
        }

        try {
            $refundData = [
                'payment_intent' => $order->transaction_id,
            ];

            if ($amount !== null) {
                $refundData['amount'] = (int) ($amount * 100);
            }

            $refund = $this->stripe->refunds->create($refundData);

            // Update order status
            $order->update([
                'payment_status' => 'refunded',
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100,
                'status' => $refund->status,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Refund failed: ' . $e->getMessage());
        }
    }
}
