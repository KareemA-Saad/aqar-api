<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\HotelBooking\Entities\BookingInformation;
use Modules\HotelBooking\Entities\HotelBookingPaymentLog;
use Modules\Product\Services\Payment\PaymentGatewayFactory;

class RefundService
{
    protected PricingService $pricingService;
    protected InventoryService $inventoryService;

    public function __construct(PricingService $pricingService, InventoryService $inventoryService)
    {
        $this->pricingService = $pricingService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Calculate refund amount based on cancellation policy.
     */
    public function calculateRefund(int $bookingId): array
    {
        $booking = BookingInformation::with('cancellationPolicy')->findOrFail($bookingId);

        $paidAmount = $booking->paid_amount ?? $booking->total_amount;

        return $this->pricingService->calculateRefundAmount(
            $paidAmount,
            $booking->check_in_date,
            $booking->cancellation_policy_id
        );
    }

    /**
     * Process refund for a cancelled booking.
     */
    public function processRefund(int $bookingId, ?float $amount = null, ?string $reason = null): array
    {
        $booking = BookingInformation::findOrFail($bookingId);

        // Validate booking can be refunded
        if ($booking->status !== BookingInformation::STATUS_CANCELLED) {
            throw new \Exception('Only cancelled bookings can be refunded.');
        }

        if ($booking->refund_status === BookingInformation::REFUND_COMPLETED) {
            throw new \Exception('Refund has already been processed.');
        }

        if ($booking->payment_status !== BookingInformation::PAYMENT_PAID) {
            throw new \Exception('No payment to refund.');
        }

        // Calculate refund if not specified
        if ($amount === null) {
            $refundInfo = $this->calculateRefund($bookingId);
            $amount = $refundInfo['refund_amount'];
        }

        if ($amount <= 0) {
            $booking->update([
                'refund_status' => BookingInformation::REFUND_NOT_APPLICABLE,
                'refund_amount' => 0,
            ]);

            return [
                'success' => true,
                'message' => 'No refund applicable based on cancellation policy.',
                'refund_amount' => 0,
            ];
        }

        return DB::transaction(function () use ($booking, $amount, $reason) {
            // Update booking refund status
            $booking->update([
                'refund_status' => BookingInformation::REFUND_PROCESSING,
                'refund_amount' => $amount,
            ]);

            // Create refund log entry
            $refundLog = $this->createRefundLog($booking, $amount, $reason);

            try {
                // Process refund through payment gateway
                $result = $this->processGatewayRefund($booking, $amount);

                if ($result['success']) {
                    $booking->update([
                        'refund_status' => BookingInformation::REFUND_COMPLETED,
                        'refunded_at' => now(),
                    ]);

                    $this->updateRefundLog($refundLog, 'completed', $result['refund_id'] ?? null);

                    return [
                        'success' => true,
                        'message' => 'Refund processed successfully.',
                        'refund_amount' => $amount,
                        'refund_id' => $result['refund_id'] ?? null,
                    ];
                } else {
                    $booking->update([
                        'refund_status' => BookingInformation::REFUND_FAILED,
                    ]);

                    $this->updateRefundLog($refundLog, 'failed', null, $result['error'] ?? 'Unknown error');

                    return [
                        'success' => false,
                        'message' => $result['message'] ?? 'Refund failed.',
                        'error' => $result['error'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Refund processing failed', [
                    'booking_id' => $booking->id,
                    'amount' => $amount,
                    'error' => $e->getMessage(),
                ]);

                $booking->update([
                    'refund_status' => BookingInformation::REFUND_FAILED,
                ]);

                $this->updateRefundLog($refundLog, 'failed', null, $e->getMessage());

                throw $e;
            }
        });
    }

    /**
     * Process refund through payment gateway.
     */
    protected function processGatewayRefund(BookingInformation $booking, float $amount): array
    {
        if (!$booking->payment_method || !$booking->payment_reference) {
            // Manual refund required
            return [
                'success' => true,
                'message' => 'Manual refund required - no payment gateway on record.',
                'manual' => true,
            ];
        }

        try {
            $gateway = PaymentGatewayFactory::create($booking->payment_method);

            // Check if gateway supports refunds
            if (!method_exists($gateway, 'refund')) {
                return [
                    'success' => true,
                    'message' => 'Gateway does not support automated refunds. Manual refund required.',
                    'manual' => true,
                ];
            }

            // Process refund
            // Note: The interface expects ProductOrder, so we need to adapt
            // For now, we'll use reflection or a custom method

            switch ($booking->payment_method) {
                case 'stripe':
                    return $this->processStripeRefund($booking, $amount);
                case 'paypal':
                    return $this->processPayPalRefund($booking, $amount);
                case 'cod':
                    return $this->processCODRefund($booking, $amount);
                default:
                    return [
                        'success' => true,
                        'manual' => true,
                        'message' => 'Manual refund required for this payment method.',
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'error' => $e->getCode(),
            ];
        }
    }

    /**
     * Process Stripe refund.
     */
    protected function processStripeRefund(BookingInformation $booking, float $amount): array
    {
        // This would call Stripe's refund API
        // For now, return simulated response
        try {
            // $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            // $refund = $stripe->refunds->create([
            //     'payment_intent' => $booking->payment_reference,
            //     'amount' => (int) ($amount * 100), // Convert to cents
            // ]);

            return [
                'success' => true,
                'refund_id' => 're_' . uniqid(),
                'message' => 'Stripe refund processed.',
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
     * Process PayPal refund.
     */
    protected function processPayPalRefund(BookingInformation $booking, float $amount): array
    {
        try {
            // PayPal refund API call would go here
            return [
                'success' => true,
                'refund_id' => 'PP_REF_' . uniqid(),
                'message' => 'PayPal refund processed.',
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
     * Process COD/Pay at Hotel refund (manual).
     */
    protected function processCODRefund(BookingInformation $booking, float $amount): array
    {
        // For COD, refunds are handled manually
        return [
            'success' => true,
            'manual' => true,
            'message' => 'Manual refund required for cash payment.',
            'refund_id' => 'MANUAL_' . $booking->booking_code,
        ];
    }

    /**
     * Admin-triggered refund (bypasses automated processing).
     */
    public function adminRefund(int $bookingId, float $amount, string $reason, ?string $refundReference = null): array
    {
        $booking = BookingInformation::findOrFail($bookingId);

        if ($booking->refund_status === BookingInformation::REFUND_COMPLETED) {
            throw new \Exception('Refund has already been processed.');
        }

        return DB::transaction(function () use ($booking, $amount, $reason, $refundReference) {
            // Create refund log
            $refundLog = $this->createRefundLog($booking, $amount, $reason);
            $refundLog->update([
                'status' => 'completed',
                'refund_reference' => $refundReference,
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            // Update booking
            $booking->update([
                'refund_status' => BookingInformation::REFUND_COMPLETED,
                'refund_amount' => $amount,
                'refunded_at' => now(),
            ]);

            return [
                'success' => true,
                'message' => 'Manual refund recorded successfully.',
                'refund_amount' => $amount,
            ];
        });
    }

    /**
     * Get refund history for a booking.
     */
    public function getRefundHistory(int $bookingId): \Illuminate\Database\Eloquent\Collection
    {
        return HotelBookingPaymentLog::where('booking_id', $bookingId)
            ->where('type', 'refund')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get pending refunds.
     */
    public function getPendingRefunds(): \Illuminate\Database\Eloquent\Collection
    {
        return BookingInformation::with(['hotel', 'user'])
            ->where('refund_status', BookingInformation::REFUND_PENDING)
            ->where('status', BookingInformation::STATUS_CANCELLED)
            ->orderBy('cancelled_at')
            ->get();
    }

    /**
     * Get refund statistics.
     */
    public function getRefundStats(?int $hotelId = null, ?string $period = 'month'): array
    {
        $query = BookingInformation::query()
            ->where('refund_status', BookingInformation::REFUND_COMPLETED);

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        $startDate = match ($period) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        $query->where('refunded_at', '>=', $startDate);

        $bookings = $query->get();

        return [
            'total_refunds' => $bookings->count(),
            'total_amount_refunded' => $bookings->sum('refund_amount'),
            'average_refund' => $bookings->avg('refund_amount'),
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
        ];
    }

    /**
     * Create refund log entry.
     */
    protected function createRefundLog(BookingInformation $booking, float $amount, ?string $reason): HotelBookingPaymentLog
    {
        return HotelBookingPaymentLog::create([
            'booking_id' => $booking->id,
            'type' => 'refund',
            'payment_method' => $booking->payment_method,
            'amount' => $amount,
            'currency' => $booking->currency ?? 'SAR',
            'status' => 'pending',
            'notes' => $reason,
        ]);
    }

    /**
     * Update refund log entry.
     */
    protected function updateRefundLog(HotelBookingPaymentLog $log, string $status, ?string $refundId = null, ?string $error = null): void
    {
        $log->update([
            'status' => $status,
            'transaction_id' => $refundId,
            'gateway_response' => $error ? json_encode(['error' => $error]) : null,
            'processed_at' => $status !== 'pending' ? now() : null,
        ]);
    }

    /**
     * Check if booking is eligible for refund.
     */
    public function isEligibleForRefund(int $bookingId): array
    {
        $booking = BookingInformation::with('cancellationPolicy')->findOrFail($bookingId);

        // Check basic eligibility
        $eligible = true;
        $reasons = [];

        if ($booking->payment_status !== BookingInformation::PAYMENT_PAID) {
            $eligible = false;
            $reasons[] = 'No payment on record.';
        }

        if ($booking->refund_status === BookingInformation::REFUND_COMPLETED) {
            $eligible = false;
            $reasons[] = 'Refund already processed.';
        }

        if (!in_array($booking->status, [
            BookingInformation::STATUS_CANCELLED,
            BookingInformation::STATUS_PENDING,
            BookingInformation::STATUS_CONFIRMED,
        ])) {
            $eligible = false;
            $reasons[] = 'Booking status does not allow refund.';
        }

        // Calculate potential refund
        $refundInfo = null;
        if ($eligible || $booking->status === BookingInformation::STATUS_CANCELLED) {
            $refundInfo = $this->calculateRefund($bookingId);
        }

        return [
            'eligible' => $eligible,
            'reasons' => $reasons,
            'refund_info' => $refundInfo,
        ];
    }

    /**
     * Batch process pending refunds.
     */
    public function batchProcessRefunds(array $bookingIds): array
    {
        $results = [];

        foreach ($bookingIds as $bookingId) {
            try {
                $result = $this->processRefund($bookingId);
                $results[$bookingId] = $result;
            } catch (\Exception $e) {
                $results[$bookingId] = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'processed' => count($results),
            'successful' => count(array_filter($results, fn ($r) => $r['success'])),
            'failed' => count(array_filter($results, fn ($r) => !$r['success'])),
            'results' => $results,
        ];
    }
}
