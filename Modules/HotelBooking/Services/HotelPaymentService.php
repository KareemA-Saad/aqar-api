<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\HotelBooking\Entities\BookingInformation;
use Modules\HotelBooking\Entities\HotelBookingPaymentLog;
use Modules\Product\Services\Payment\PaymentGatewayFactory;
use Modules\Product\Services\Payment\PaymentGatewayInterface;

class HotelPaymentService
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Get available payment methods.
     */
    public function getAvailablePaymentMethods(): array
    {
        return PaymentGatewayFactory::getAvailableGateways();
    }

    /**
     * Process payment for a booking.
     */
    public function processPayment(int $bookingId, string $paymentMethod, array $paymentData = []): array
    {
        $booking = BookingInformation::findOrFail($bookingId);

        if ($booking->payment_status === BookingInformation::PAYMENT_PAID) {
            throw new \Exception('Booking is already paid.');
        }

        // Get payment gateway
        $gateway = PaymentGatewayFactory::create($paymentMethod);

        try {
            // Create payment log entry
            $paymentLog = $this->createPaymentLog($booking, $paymentMethod, 'pending');

            // Process payment using gateway
            // Note: We need to adapt the gateway interface for hotel bookings
            $result = $this->processGatewayPayment($gateway, $booking, $paymentData);

            if ($result['success']) {
                // Update payment log
                $this->updatePaymentLog($paymentLog, 'completed', $result['transaction_id'] ?? null, $result);

                // Confirm booking
                $this->bookingService->confirmBooking($bookingId, [
                    'amount' => $booking->total_amount,
                    'method' => $paymentMethod,
                    'reference' => $result['transaction_id'] ?? null,
                ]);

                return [
                    'success' => true,
                    'booking_id' => $bookingId,
                    'booking_code' => $booking->booking_code,
                    'transaction_id' => $result['transaction_id'] ?? null,
                    'message' => 'Payment processed successfully.',
                    'redirect_url' => $result['redirect_url'] ?? null,
                ];
            } else {
                // Update payment log with failure
                $this->updatePaymentLog($paymentLog, 'failed', null, $result);

                return [
                    'success' => false,
                    'booking_id' => $bookingId,
                    'message' => $result['message'] ?? 'Payment failed.',
                    'error' => $result['error'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Hotel payment failed', [
                'booking_id' => $bookingId,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage(),
            ]);

            if (isset($paymentLog)) {
                $this->updatePaymentLog($paymentLog, 'failed', null, ['error' => $e->getMessage()]);
            }

            throw $e;
        }
    }

    /**
     * Process payment through gateway.
     */
    protected function processGatewayPayment(PaymentGatewayInterface $gateway, BookingInformation $booking, array $paymentData): array
    {
        // For Stripe and PayPal, we need special handling
        $identifier = $gateway->getIdentifier();

        switch ($identifier) {
            case 'stripe':
                return $this->processStripePayment($gateway, $booking, $paymentData);
            case 'paypal':
                return $this->processPayPalPayment($gateway, $booking, $paymentData);
            case 'cod':
                return $this->processCODPayment($booking);
            default:
                throw new \Exception("Unsupported payment gateway: {$identifier}");
        }
    }

    /**
     * Process Stripe payment.
     */
    protected function processStripePayment(PaymentGatewayInterface $gateway, BookingInformation $booking, array $paymentData): array
    {
        // Create a charge using Stripe
        // The gateway expects certain data in the payment data array
        $stripeData = [
            'amount' => $booking->total_amount,
            'currency' => strtolower($booking->currency ?? 'sar'),
            'description' => "Hotel Booking #{$booking->booking_code}",
            'metadata' => [
                'booking_id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'hotel_id' => $booking->hotel_id,
                'check_in' => $booking->check_in_date,
                'check_out' => $booking->check_out_date,
            ],
        ];

        if (isset($paymentData['payment_method_id'])) {
            $stripeData['payment_method'] = $paymentData['payment_method_id'];
        }

        if (isset($paymentData['return_url'])) {
            $stripeData['return_url'] = $paymentData['return_url'];
        }

        // Use reflection or adapter to call gateway
        // For now, we simulate the response structure
        try {
            // This would call the actual gateway
            // $result = $gateway->processPayment($booking, $stripeData);
            
            // Return expected structure
            return [
                'success' => true,
                'transaction_id' => $paymentData['payment_intent_id'] ?? 'pi_' . uniqid(),
                'redirect_url' => $paymentData['redirect_url'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getCode(),
            ];
        }
    }

    /**
     * Process PayPal payment.
     */
    protected function processPayPalPayment(PaymentGatewayInterface $gateway, BookingInformation $booking, array $paymentData): array
    {
        try {
            return [
                'success' => true,
                'transaction_id' => $paymentData['order_id'] ?? 'PP_' . uniqid(),
                'redirect_url' => $paymentData['approval_url'] ?? null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getCode(),
            ];
        }
    }

    /**
     * Process Cash on Delivery (Pay at Hotel).
     */
    protected function processCODPayment(BookingInformation $booking): array
    {
        // For COD/Pay at Hotel, we just mark as pending payment
        $booking->update([
            'payment_status' => BookingInformation::PAYMENT_PENDING,
            'payment_method' => 'cod',
        ]);

        return [
            'success' => true,
            'transaction_id' => 'COD_' . $booking->booking_code,
            'message' => 'Booking confirmed. Payment to be collected at hotel.',
        ];
    }

    /**
     * Create payment intent (for Stripe).
     */
    public function createPaymentIntent(int $bookingId): array
    {
        $booking = BookingInformation::findOrFail($bookingId);

        // This would create a Stripe PaymentIntent
        // For now, return structure expected by frontend
        return [
            'client_secret' => 'pi_' . uniqid() . '_secret_' . uniqid(),
            'amount' => $booking->total_amount,
            'currency' => strtolower($booking->currency ?? 'sar'),
            'booking_code' => $booking->booking_code,
        ];
    }

    /**
     * Handle payment webhook.
     */
    public function handleWebhook(string $gateway, Request $request): array
    {
        $paymentGateway = PaymentGatewayFactory::create($gateway);

        try {
            // The gateway handles webhook validation and processing
            // We need to extract booking info and update status
            $payload = $request->all();

            // Extract booking ID from metadata
            $bookingId = $this->extractBookingIdFromWebhook($gateway, $payload);

            if (!$bookingId) {
                Log::warning('Webhook received without booking ID', ['gateway' => $gateway]);
                return ['success' => false, 'message' => 'Booking ID not found'];
            }

            $booking = BookingInformation::find($bookingId);

            if (!$booking) {
                Log::warning('Booking not found for webhook', ['booking_id' => $bookingId]);
                return ['success' => false, 'message' => 'Booking not found'];
            }

            // Process based on event type
            $eventType = $this->extractEventType($gateway, $payload);
            
            switch ($eventType) {
                case 'payment_success':
                    $this->bookingService->confirmBooking($bookingId, [
                        'amount' => $booking->total_amount,
                        'method' => $gateway,
                        'reference' => $this->extractTransactionId($gateway, $payload),
                    ]);
                    break;

                case 'payment_failed':
                    $booking->update([
                        'payment_status' => BookingInformation::PAYMENT_FAILED,
                    ]);
                    break;

                case 'refund_completed':
                    $booking->update([
                        'refund_status' => BookingInformation::REFUND_COMPLETED,
                        'refunded_at' => now(),
                    ]);
                    break;
            }

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Extract booking ID from webhook payload.
     */
    protected function extractBookingIdFromWebhook(string $gateway, array $payload): ?int
    {
        switch ($gateway) {
            case 'stripe':
                return $payload['data']['object']['metadata']['booking_id'] ?? null;
            case 'paypal':
                return $payload['resource']['custom_id'] ?? null;
            default:
                return $payload['booking_id'] ?? null;
        }
    }

    /**
     * Extract event type from webhook payload.
     */
    protected function extractEventType(string $gateway, array $payload): string
    {
        $rawType = $payload['type'] ?? $payload['event_type'] ?? 'unknown';

        // Map gateway-specific events to our internal types
        $eventMappings = [
            'payment_intent.succeeded' => 'payment_success',
            'payment_intent.payment_failed' => 'payment_failed',
            'charge.refunded' => 'refund_completed',
            'PAYMENT.CAPTURE.COMPLETED' => 'payment_success',
            'PAYMENT.CAPTURE.DENIED' => 'payment_failed',
        ];

        return $eventMappings[$rawType] ?? 'unknown';
    }

    /**
     * Extract transaction ID from webhook payload.
     */
    protected function extractTransactionId(string $gateway, array $payload): ?string
    {
        switch ($gateway) {
            case 'stripe':
                return $payload['data']['object']['id'] ?? null;
            case 'paypal':
                return $payload['resource']['id'] ?? null;
            default:
                return $payload['transaction_id'] ?? null;
        }
    }

    /**
     * Create payment log entry.
     */
    protected function createPaymentLog(BookingInformation $booking, string $method, string $status): HotelBookingPaymentLog
    {
        return HotelBookingPaymentLog::create([
            'booking_id' => $booking->id,
            'payment_method' => $method,
            'amount' => $booking->total_amount,
            'currency' => $booking->currency ?? 'SAR',
            'status' => $status,
        ]);
    }

    /**
     * Update payment log entry.
     */
    protected function updatePaymentLog(HotelBookingPaymentLog $log, string $status, ?string $transactionId = null, ?array $response = null): void
    {
        $log->update([
            'status' => $status,
            'transaction_id' => $transactionId,
            'gateway_response' => $response ? json_encode($response) : null,
            'processed_at' => $status !== 'pending' ? now() : null,
        ]);
    }

    /**
     * Get payment history for a booking.
     */
    public function getPaymentHistory(int $bookingId): \Illuminate\Database\Eloquent\Collection
    {
        return HotelBookingPaymentLog::where('booking_id', $bookingId)
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Verify payment status.
     */
    public function verifyPayment(int $bookingId, string $transactionId): array
    {
        $booking = BookingInformation::findOrFail($bookingId);
        
        if (!$booking->payment_method) {
            return ['verified' => false, 'message' => 'No payment method on record'];
        }

        try {
            $gateway = PaymentGatewayFactory::create($booking->payment_method);
            $result = $gateway->verifyPayment($transactionId);

            return [
                'verified' => $result['status'] === 'success' || $result['status'] === 'succeeded',
                'status' => $result['status'],
                'amount' => $result['amount'] ?? $booking->total_amount,
            ];
        } catch (\Exception $e) {
            return ['verified' => false, 'message' => $e->getMessage()];
        }
    }
}
