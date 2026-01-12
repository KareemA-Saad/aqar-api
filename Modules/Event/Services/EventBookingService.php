<?php

declare(strict_types=1);

namespace Modules\Event\Services;

use App\Helpers\SanitizeInput;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Event\Entities\Event;
use Modules\Event\Entities\EventPaymentLog;

/**
 * Service class for managing event bookings and payments.
 */
final class EventBookingService
{
    /**
     * Get paginated list of bookings with optional filters.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getBookings(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = EventPaymentLog::query()->with(['event', 'user']);

        // Event filter
        if (!empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        // User filter
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Status filter
        if (isset($filters['status'])) {
            $query->where('status', (bool) $filters['status']);
        }

        // Check-in status filter
        if (isset($filters['check_in_status'])) {
            $query->where('check_in_status', (bool) $filters['check_in_status']);
        }

        // Payment gateway filter
        if (!empty($filters['payment_gateway'])) {
            $query->where('payment_gateway', $filters['payment_gateway']);
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Search filter (name, email, phone)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhere('ticket_code', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['created_at', 'amount', 'ticket_qty', 'check_in_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Create a new booking (for testing - mock payment).
     *
     * @param Event $event
     * @param array<string, mixed> $data
     * @return EventPaymentLog
     * @throws \Exception
     */
    public function createBooking(Event $event, array $data): EventPaymentLog
    {
        return DB::transaction(function () use ($event, $data) {
            // Check ticket availability
            $ticketQty = $data['ticket_qty'] ?? 1;
            
            if ($event->available_ticket < $ticketQty) {
                throw new \Exception('Not enough tickets available.');
            }

            // Calculate amount
            $amount = $event->cost * $ticketQty;

            // Get user if authenticated
            $userId = null;
            if (Auth::guard('api_tenant_user')->check()) {
                $userId = Auth::guard('api_tenant_user')->id();
            }

            // Generate unique ticket code
            $ticketCode = $this->generateUniqueTicketCode();

            // Create payment log
            $booking = EventPaymentLog::create([
                'event_id' => $event->id,
                'user_id' => $userId,
                'name' => SanitizeInput::esc_html($data['name']),
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => SanitizeInput::esc_html($data['address'] ?? ''),
                'ticket_qty' => $ticketQty,
                'amount' => $amount,
                'payment_gateway' => $data['payment_gateway'] ?? 'test',
                'transaction_id' => $data['transaction_id'] ?? 'TEST-' . Str::upper(Str::random(12)),
                'ticket_code' => $ticketCode,
                'track' => $data['track'] ?? Str::random(20),
                'status' => $data['status'] ?? true, // For testing, auto-approve
                'manual_payment_attachment' => $data['manual_payment_attachment'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            // Update ticket availability
            $event->decrement('available_ticket', $ticketQty);

            return $booking->load(['event', 'user']);
        });
    }

    /**
     * Process payment through gateway (placeholder for future implementation).
     *
     * @param EventPaymentLog $booking
     * @param string $gateway
     * @param array<string, mixed> $paymentData
     * @return array<string, mixed>
     */
    public function processPayment(EventPaymentLog $booking, string $gateway, array $paymentData = []): array
    {
        // TODO: Implement real payment gateway integration
        // For now, return mock success response
        
        /* 
         * Future implementation will support:
         * - PayPal
         * - Stripe
         * - Razorpay
         * - Paytm
         * - Mollie
         * - PayStack
         * - Flutterwave
         * - Midtrans
         * - Cashfree
         * - Instamojo
         * - Marcadopago
         * - Squareup
         * - Cinetpay
         * - Paytabs
         * - Billplz
         * - Zitopay
         * - Toyyibpay
         * - Iyzico
         * - Authorize.net
         * - Manual bank transfer
         * And more...
         */

        return [
            'success' => true,
            'message' => 'Payment processed successfully (TEST MODE)',
            'transaction_id' => $booking->transaction_id,
            'gateway' => $gateway,
        ];
    }

    /**
     * Update booking/payment status.
     *
     * @param EventPaymentLog $booking
     * @param bool $status
     * @return EventPaymentLog
     */
    public function updatePaymentStatus(EventPaymentLog $booking, bool $status): EventPaymentLog
    {
        return DB::transaction(function () use ($booking, $status) {
            $oldStatus = $booking->status;
            $booking->update(['status' => $status]);

            // If changing from pending to approved, ensure tickets are reserved
            // If changing from approved to pending/rejected, release tickets
            if (!$oldStatus && $status) {
                // Approved - tickets already decremented during booking creation
            } elseif ($oldStatus && !$status) {
                // Rejected/Cancelled - return tickets
                $booking->event->increment('available_ticket', $booking->ticket_qty);
            }

            return $booking->fresh(['event', 'user']);
        });
    }

    /**
     * Check-in attendee at event.
     *
     * @param EventPaymentLog $booking
     * @return EventPaymentLog
     * @throws \Exception
     */
    public function checkInAttendee(EventPaymentLog $booking): EventPaymentLog
    {
        if (!$booking->status) {
            throw new \Exception('Cannot check-in. Booking is not confirmed.');
        }

        if ($booking->check_in_status) {
            throw new \Exception('Attendee already checked in.');
        }

        $booking->update([
            'check_in_status' => true,
            'check_in_at' => now(),
        ]);

        return $booking->fresh();
    }

    /**
     * Undo check-in for attendee.
     *
     * @param EventPaymentLog $booking
     * @return EventPaymentLog
     */
    public function undoCheckIn(EventPaymentLog $booking): EventPaymentLog
    {
        $booking->update([
            'check_in_status' => false,
            'check_in_at' => null,
        ]);

        return $booking->fresh();
    }

    /**
     * Get booking by ticket code.
     *
     * @param string $ticketCode
     * @return EventPaymentLog|null
     */
    public function getBookingByTicketCode(string $ticketCode): ?EventPaymentLog
    {
        return EventPaymentLog::where('ticket_code', $ticketCode)
            ->with(['event', 'user'])
            ->first();
    }

    /**
     * Get booking statistics for an event.
     *
     * @param int $eventId
     * @return array<string, mixed>
     */
    public function getEventBookingStatistics(int $eventId): array
    {
        $bookings = EventPaymentLog::where('event_id', $eventId)->where('status', true);

        return [
            'total_bookings' => $bookings->count(),
            'total_revenue' => $bookings->sum('amount'),
            'total_tickets_sold' => $bookings->sum('ticket_qty'),
            'total_checked_in' => $bookings->where('check_in_status', true)->count(),
            'pending_check_ins' => $bookings->where('check_in_status', false)->count(),
        ];
    }

    /**
     * Generate revenue report for date range.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function generateRevenueReport(array $filters = []): array
    {
        $query = EventPaymentLog::where('status', true);

        if (!empty($filters['event_id'])) {
            $query->where('event_id', $filters['event_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $bookings = $query->with('event')->get();

        return [
            'total_bookings' => $bookings->count(),
            'total_revenue' => $bookings->sum('amount'),
            'total_tickets_sold' => $bookings->sum('ticket_qty'),
            'average_booking_value' => $bookings->count() > 0 
                ? round($bookings->sum('amount') / $bookings->count(), 2)
                : 0,
            'bookings_by_gateway' => $bookings->groupBy('payment_gateway')->map->count()->toArray(),
            'revenue_by_event' => $bookings->groupBy('event.title')->map->sum('amount')->toArray(),
        ];
    }

    /**
     * Generate unique ticket code.
     *
     * @return string
     */
    private function generateUniqueTicketCode(): string
    {
        do {
            $code = 'TKT-' . strtoupper(Str::random(8));
        } while (EventPaymentLog::where('ticket_code', $code)->exists());

        return $code;
    }
}
