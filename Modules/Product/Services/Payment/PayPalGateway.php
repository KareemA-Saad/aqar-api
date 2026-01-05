<?php

declare(strict_types=1);

namespace Modules\Product\Services\Payment;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Product\Entities\ProductOrder;

/**
 * PayPal Payment Gateway
 *
 * Handles PayPal payment processing using the PayPal REST API.
 */
class PayPalGateway implements PaymentGatewayInterface
{
    protected string $baseUrl;
    protected ?string $clientId;
    protected ?string $clientSecret;

    public function __construct()
    {
        $this->clientId = config('services.paypal.client_id');
        $this->clientSecret = config('services.paypal.secret');
        $this->baseUrl = config('services.paypal.mode', 'sandbox') === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier(): string
    {
        return 'paypal';
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'PayPal';
    }

    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Get access token from PayPal.
     *
     * @return string
     * @throws \Exception
     */
    protected function getAccessToken(): string
    {
        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->clientSecret)
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to authenticate with PayPal');
        }

        return $response->json('access_token');
    }

    /**
     * {@inheritdoc}
     */
    public function processPayment(ProductOrder $order, array $data): array
    {
        if (!$this->isAvailable()) {
            throw new \Exception('PayPal is not configured');
        }

        try {
            $accessToken = $this->getAccessToken();

            // Create PayPal order
            $response = Http::withToken($accessToken)
                ->post("{$this->baseUrl}/v2/checkout/orders", [
                    'intent' => 'CAPTURE',
                    'purchase_units' => [
                        [
                            'reference_id' => $order->payment_track,
                            'description' => "Order #{$order->payment_track}",
                            'amount' => [
                                'currency_code' => strtoupper(config('app.currency', 'USD')),
                                'value' => number_format($order->total_amount, 2, '.', ''),
                            ],
                        ],
                    ],
                    'application_context' => [
                        'return_url' => route('api.v1.tenant.checkout.verify', [
                            'tenant' => request()->route('tenant'),
                            'orderNumber' => $order->payment_track,
                        ]),
                        'cancel_url' => route('api.v1.tenant.checkout.verify', [
                            'tenant' => request()->route('tenant'),
                            'orderNumber' => $order->payment_track,
                        ]) . '?cancelled=true',
                    ],
                ]);

            if (!$response->successful()) {
                Log::error('PayPal order creation failed', [
                    'response' => $response->json(),
                ]);
                throw new \Exception('Failed to create PayPal order');
            }

            $paypalOrder = $response->json();

            // Find the approval URL
            $approvalUrl = collect($paypalOrder['links'])
                ->firstWhere('rel', 'approve')['href'] ?? null;

            // Update order with PayPal order ID
            $order->update([
                'transaction_id' => $paypalOrder['id'],
            ]);

            return [
                'status' => 'requires_redirect',
                'redirect_url' => $approvalUrl,
                'client_secret' => null,
                'paypal_order_id' => $paypalOrder['id'],
            ];

        } catch (\Exception $e) {
            Log::error('PayPal payment error: ' . $e->getMessage(), [
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
        $payload = $request->all();
        $eventType = $payload['event_type'] ?? null;

        Log::info('PayPal webhook received', ['event_type' => $eventType]);

        switch ($eventType) {
            case 'CHECKOUT.ORDER.APPROVED':
                $this->handleOrderApproved($payload);
                break;

            case 'PAYMENT.CAPTURE.COMPLETED':
                $this->handlePaymentCaptured($payload);
                break;

            case 'PAYMENT.CAPTURE.DENIED':
                $this->handlePaymentDenied($payload);
                break;

            default:
                Log::info('Unhandled PayPal webhook event: ' . $eventType);
        }
    }

    /**
     * Handle order approved event - capture the payment.
     *
     * @param array $payload
     */
    protected function handleOrderApproved(array $payload): void
    {
        $paypalOrderId = $payload['resource']['id'] ?? null;

        if (!$paypalOrderId) {
            return;
        }

        try {
            $accessToken = $this->getAccessToken();

            // Capture the payment
            $response = Http::withToken($accessToken)
                ->post("{$this->baseUrl}/v2/checkout/orders/{$paypalOrderId}/capture");

            if ($response->successful()) {
                $this->handlePaymentCaptured([
                    'resource' => $response->json(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to capture PayPal payment', [
                'paypal_order_id' => $paypalOrderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle payment captured event.
     *
     * @param array $payload
     */
    protected function handlePaymentCaptured(array $payload): void
    {
        $paypalOrderId = $payload['resource']['id'] ?? null;

        if (!$paypalOrderId) {
            return;
        }

        $order = ProductOrder::where('transaction_id', $paypalOrderId)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'paid',
                'status' => 'in_progress',
            ]);

            Log::info('PayPal payment captured', [
                'order_id' => $order->id,
                'paypal_order_id' => $paypalOrderId,
            ]);
        }
    }

    /**
     * Handle payment denied event.
     *
     * @param array $payload
     */
    protected function handlePaymentDenied(array $payload): void
    {
        $paypalOrderId = $payload['resource']['supplementary_data']['related_ids']['order_id'] ?? null;

        if (!$paypalOrderId) {
            return;
        }

        $order = ProductOrder::where('transaction_id', $paypalOrderId)->first();

        if ($order) {
            $order->update([
                'payment_status' => 'failed',
            ]);

            Log::warning('PayPal payment denied', [
                'order_id' => $order->id,
                'paypal_order_id' => $paypalOrderId,
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function verifyPayment(string $transactionId): array
    {
        if (!$this->isAvailable()) {
            throw new \Exception('PayPal is not configured');
        }

        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->get("{$this->baseUrl}/v2/checkout/orders/{$transactionId}");

            if (!$response->successful()) {
                throw new \Exception('Failed to retrieve PayPal order');
            }

            $paypalOrder = $response->json();

            return [
                'status' => $paypalOrder['status'],
                'paid' => $paypalOrder['status'] === 'COMPLETED',
                'amount' => (float) ($paypalOrder['purchase_units'][0]['amount']['value'] ?? 0),
                'currency' => $paypalOrder['purchase_units'][0]['amount']['currency_code'] ?? 'USD',
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
        if (!$this->isAvailable()) {
            throw new \Exception('PayPal is not configured');
        }

        if (!$order->transaction_id) {
            throw new \Exception('No transaction ID found for this order');
        }

        try {
            $accessToken = $this->getAccessToken();

            // First, get the capture ID from the order
            $orderResponse = Http::withToken($accessToken)
                ->get("{$this->baseUrl}/v2/checkout/orders/{$order->transaction_id}");

            if (!$orderResponse->successful()) {
                throw new \Exception('Failed to retrieve PayPal order');
            }

            $paypalOrder = $orderResponse->json();
            $captureId = $paypalOrder['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

            if (!$captureId) {
                throw new \Exception('No capture found for this order');
            }

            // Process refund
            $refundData = [];
            if ($amount !== null) {
                $refundData['amount'] = [
                    'value' => number_format($amount, 2, '.', ''),
                    'currency_code' => strtoupper(config('app.currency', 'USD')),
                ];
            }

            $response = Http::withToken($accessToken)
                ->post("{$this->baseUrl}/v2/payments/captures/{$captureId}/refund", $refundData);

            if (!$response->successful()) {
                throw new \Exception('Refund request failed');
            }

            $refund = $response->json();

            // Update order status
            $order->update([
                'payment_status' => 'refunded',
            ]);

            return [
                'success' => true,
                'refund_id' => $refund['id'],
                'amount' => (float) ($refund['amount']['value'] ?? $order->total_amount),
                'status' => $refund['status'],
            ];
        } catch (\Exception $e) {
            throw new \Exception('Refund failed: ' . $e->getMessage());
        }
    }
}
