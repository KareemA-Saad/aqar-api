<?php

declare(strict_types=1);

namespace Modules\Appointment\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Facades\Mail;
// use App\Mail\BasicMail;
use Modules\Appointment\Entities\Appointment;
use Modules\Appointment\Entities\AppointmentPaymentAdditionalLog;
use Modules\Appointment\Entities\AppointmentPaymentLog;
use Modules\Appointment\Entities\AppointmentTax;
use Modules\Appointment\Entities\SubAppointment;

/**
 * Service class for managing appointment bookings.
 *
 * Handles booking creation, payment processing, status management, and notifications.
 */
final class AppointmentBookingService
{
    public function __construct(
        private readonly SlotAvailabilityService $slotAvailabilityService,
    ) {}

    /**
     * Get paginated bookings with filters.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getBookings(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = AppointmentPaymentLog::with([
            'appointment',
            'additional_appointment_logs',
            'sub_appointment_log_items',
        ]);

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%");
            });
        }

        // Appointment filter
        if (!empty($filters['appointment_id'])) {
            $query->where('appointment_id', $filters['appointment_id']);
        }

        // User filter
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Payment status filter
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        // Payment gateway filter
        if (!empty($filters['payment_gateway'])) {
            $query->where('payment_gateway', $filters['payment_gateway']);
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->whereDate('appointment_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('appointment_date', '<=', $filters['date_to']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['name', 'email', 'appointment_date', 'total_amount', 'status', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get user's bookings.
     *
     * @param int $userId
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getUserBookings(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['user_id'] = $userId;
        return $this->getBookings($filters, $perPage);
    }

    /**
     * Get a single booking by ID.
     *
     * @param int $id
     * @return AppointmentPaymentLog|null
     */
    public function findById(int $id): ?AppointmentPaymentLog
    {
        return AppointmentPaymentLog::with([
            'appointment',
            'appointment.category',
            'additional_appointment_logs',
            'sub_appointment_log_items',
        ])->find($id);
    }

    /**
     * Initialize a booking (calculate totals without creating).
     *
     * @param array<string, mixed> $data
     * @return array
     */
    public function initializeBooking(array $data): array
    {
        $appointment = Appointment::with('tax')->findOrFail($data['appointment_id']);

        // Check slot availability
        $slotCheck = $this->slotAvailabilityService->checkSlotAvailability(
            $data['appointment_date'],
            $data['appointment_time'],
            $appointment->id
        );

        if (!$slotCheck['available']) {
            return [
                'success' => false,
                'message' => $slotCheck['message'],
            ];
        }

        // Calculate pricing
        $pricing = $this->calculatePricing($appointment, $data);

        return [
            'success' => true,
            'appointment' => [
                'id' => $appointment->id,
                'title' => $appointment->title,
                'price' => $appointment->price,
                'image' => $appointment->image,
            ],
            'booking_details' => [
                'date' => $data['appointment_date'],
                'time' => $data['appointment_time'],
                'persons' => $data['persons'] ?? 1,
            ],
            'pricing' => $pricing,
            'sub_appointments' => $pricing['sub_appointments'],
        ];
    }

    /**
     * Create a new booking.
     *
     * @param array<string, mixed> $data
     * @return AppointmentPaymentLog
     * @throws \Exception
     */
    public function createBooking(array $data): AppointmentPaymentLog
    {
        $appointment = Appointment::with('tax')->findOrFail($data['appointment_id']);

        // Check slot availability
        $slotCheck = $this->slotAvailabilityService->checkSlotAvailability(
            $data['appointment_date'],
            $data['appointment_time'],
            $appointment->id
        );

        if (!$slotCheck['available']) {
            throw new \Exception($slotCheck['message']);
        }

        // Check if date is in the past
        if ($this->slotAvailabilityService->isDateInPast($data['appointment_date'])) {
            throw new \Exception('Cannot book appointments in the past');
        }

        // Calculate pricing
        $pricing = $this->calculatePricing($appointment, $data);

        return DB::transaction(function () use ($data, $appointment, $pricing) {
            // Create booking
            $booking = AppointmentPaymentLog::create([
                'user_id' => $data['user_id'] ?? Auth::id(),
                'appointment_id' => $appointment->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'appointment_date' => $data['appointment_date'],
                'appointment_time' => $data['appointment_time'],
                'appointment_price' => $appointment->price,
                'coupon_type' => $data['coupon_type'] ?? null,
                'coupon_code' => $data['coupon_code'] ?? null,
                'coupon_discount' => $pricing['coupon_discount'],
                'tax_amount' => $pricing['tax_amount'],
                'subtotal' => $pricing['subtotal'],
                'total_amount' => $pricing['total'],
                'payment_gateway' => $data['payment_gateway'] ?? 'manual',
                'status' => 'pending',
                'payment_status' => $data['payment_status'] ?? 'pending',
                'transaction_id' => $data['transaction_id'] ?? null,
                'manual_payment_attachment' => $data['manual_payment_attachment'] ?? null,
            ]);

            // Create additional appointment logs (sub-appointments)
            if (!empty($data['sub_appointment_ids'])) {
                $this->createAdditionalLogs($booking, $appointment, $data['sub_appointment_ids']);
            }

            return $booking->fresh([
                'appointment',
                'additional_appointment_logs',
                'sub_appointment_log_items',
            ]);
        });
    }

    /**
     * Update booking status.
     *
     * @param AppointmentPaymentLog $booking
     * @param string $status
     * @param string|null $reason
     * @return AppointmentPaymentLog
     */
    public function updateStatus(AppointmentPaymentLog $booking, string $status, ?string $reason = null): AppointmentPaymentLog
    {
        $allowedStatuses = ['pending', 'confirmed', 'complete', 'cancelled', 'rejected'];

        if (!in_array($status, $allowedStatuses, true)) {
            throw new \InvalidArgumentException('Invalid status: ' . $status);
        }

        $booking->update([
            'status' => $status,
        ]);

        // TODO: Uncomment when email integration is ready
        // Send notification email
        // $this->sendStatusNotification($booking, $status, $reason);

        return $booking->fresh();
    }

    /**
     * Confirm a booking.
     *
     * @param AppointmentPaymentLog $booking
     * @return AppointmentPaymentLog
     */
    public function confirmBooking(AppointmentPaymentLog $booking): AppointmentPaymentLog
    {
        return $this->updateStatus($booking, 'confirmed');
    }

    /**
     * Complete a booking.
     *
     * @param AppointmentPaymentLog $booking
     * @return AppointmentPaymentLog
     */
    public function completeBooking(AppointmentPaymentLog $booking): AppointmentPaymentLog
    {
        return $this->updateStatus($booking, 'complete');
    }

    /**
     * Cancel a booking.
     *
     * @param AppointmentPaymentLog $booking
     * @param string|null $reason
     * @return AppointmentPaymentLog
     */
    public function cancelBooking(AppointmentPaymentLog $booking, ?string $reason = null): AppointmentPaymentLog
    {
        return $this->updateStatus($booking, 'cancelled', $reason);
    }

    /**
     * Reschedule a booking.
     *
     * @param AppointmentPaymentLog $booking
     * @param string $newDate
     * @param string $newTime
     * @return AppointmentPaymentLog
     * @throws \Exception
     */
    public function rescheduleBooking(AppointmentPaymentLog $booking, string $newDate, string $newTime): AppointmentPaymentLog
    {
        // Check slot availability
        $slotCheck = $this->slotAvailabilityService->checkSlotAvailability(
            $newDate,
            $newTime,
            $booking->appointment_id
        );

        if (!$slotCheck['available']) {
            throw new \Exception($slotCheck['message']);
        }

        // Check if date is in the past
        if ($this->slotAvailabilityService->isDateInPast($newDate)) {
            throw new \Exception('Cannot reschedule to a past date');
        }

        $booking->update([
            'appointment_date' => $newDate,
            'appointment_time' => $newTime,
        ]);

        // TODO: Uncomment when email integration is ready
        // Send reschedule notification
        // $this->sendRescheduleNotification($booking);

        return $booking->fresh();
    }

    /**
     * Approve manual payment.
     *
     * @param AppointmentPaymentLog $booking
     * @return AppointmentPaymentLog
     */
    public function approveManualPayment(AppointmentPaymentLog $booking): AppointmentPaymentLog
    {
        $booking->update([
            'payment_status' => 'complete',
            'status' => 'confirmed',
        ]);

        // TODO: Uncomment when email integration is ready
        // Send approval notification
        // $this->sendPaymentApprovalNotification($booking);

        return $booking->fresh();
    }

    /**
     * Update payment status.
     *
     * @param AppointmentPaymentLog $booking
     * @param string $paymentStatus
     * @param string|null $transactionId
     * @return AppointmentPaymentLog
     */
    public function updatePaymentStatus(
        AppointmentPaymentLog $booking,
        string $paymentStatus,
        ?string $transactionId = null
    ): AppointmentPaymentLog {
        $updateData = ['payment_status' => $paymentStatus];

        if ($transactionId) {
            $updateData['transaction_id'] = $transactionId;
        }

        // Auto-confirm on successful payment
        if ($paymentStatus === 'complete') {
            $updateData['status'] = 'confirmed';
        }

        $booking->update($updateData);

        return $booking->fresh();
    }

    /**
     * Get booking statistics.
     *
     * @param array<string, mixed> $filters
     * @return array
     */
    public function getStatistics(array $filters = []): array
    {
        $query = AppointmentPaymentLog::query();

        // Apply date filter
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Apply appointment filter
        if (!empty($filters['appointment_id'])) {
            $query->where('appointment_id', $filters['appointment_id']);
        }

        $bookings = $query->get();

        $stats = [
            'total_bookings' => $bookings->count(),
            'pending' => $bookings->where('status', 'pending')->count(),
            'confirmed' => $bookings->where('status', 'confirmed')->count(),
            'completed' => $bookings->where('status', 'complete')->count(),
            'cancelled' => $bookings->where('status', 'cancelled')->count(),
            'total_revenue' => $bookings->whereIn('payment_status', ['complete'])->sum('total_amount'),
            'pending_revenue' => $bookings->where('payment_status', 'pending')->sum('total_amount'),
            'by_gateway' => $bookings->groupBy('payment_gateway')->map->count()->toArray(),
        ];

        // Calculate daily averages if date range specified
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $days = max(1, \Carbon\Carbon::parse($filters['date_from'])->diffInDays($filters['date_to']) + 1);
            $stats['avg_daily_bookings'] = round($stats['total_bookings'] / $days, 2);
            $stats['avg_daily_revenue'] = round($stats['total_revenue'] / $days, 2);
        }

        return $stats;
    }

    /**
     * Calculate pricing for a booking.
     *
     * @param Appointment $appointment
     * @param array<string, mixed> $data
     * @return array
     */
    private function calculatePricing(Appointment $appointment, array $data): array
    {
        $appointmentPrice = (float) $appointment->price;
        $subAppointmentTotal = 0;
        $subAppointments = [];

        // Calculate sub-appointment prices
        if (!empty($data['sub_appointment_ids']) && $appointment->sub_appointment_status) {
            $subAppointmentModels = SubAppointment::whereIn('id', $data['sub_appointment_ids'])->get();

            foreach ($subAppointmentModels as $subAppointment) {
                $subAppointmentTotal += (float) $subAppointment->price;
                $subAppointments[] = [
                    'id' => $subAppointment->id,
                    'title' => $subAppointment->title,
                    'price' => (float) $subAppointment->price,
                ];
            }
        }

        // Calculate subtotal
        $subtotal = $appointmentPrice + $subAppointmentTotal;

        // Calculate coupon discount
        $couponDiscount = 0;
        if (!empty($data['coupon_code'])) {
            // TODO: Integrate with coupon system
            // $couponDiscount = $this->calculateCouponDiscount($data['coupon_code'], $subtotal);
        }

        $afterDiscount = $subtotal - $couponDiscount;

        // Calculate tax
        $taxAmount = 0;
        $taxType = null;
        if ($appointment->tax_status && $appointment->tax) {
            $taxType = $appointment->tax->tax_type;
            $taxPercentage = (float) $appointment->tax->tax_amount;

            if ($taxType === 'exclusive') {
                // Add tax on top
                $taxAmount = ($afterDiscount * $taxPercentage) / 100;
            } else {
                // Tax inclusive - extract from total
                $taxAmount = ($afterDiscount * $taxPercentage) / (100 + $taxPercentage);
            }
        }

        // Calculate total
        $total = $taxType === 'exclusive'
            ? $afterDiscount + $taxAmount
            : $afterDiscount; // For inclusive, tax is already in the price

        return [
            'appointment_price' => $appointmentPrice,
            'sub_appointments_total' => $subAppointmentTotal,
            'sub_appointments' => $subAppointments,
            'subtotal' => $subtotal,
            'coupon_discount' => $couponDiscount,
            'after_discount' => $afterDiscount,
            'tax_type' => $taxType,
            'tax_percentage' => $appointment->tax?->tax_amount ?? 0,
            'tax_amount' => round($taxAmount, 2),
            'total' => round($total, 2),
        ];
    }

    /**
     * Create additional appointment logs (sub-appointments).
     *
     * @param AppointmentPaymentLog $booking
     * @param Appointment $appointment
     * @param array<int> $subAppointmentIds
     * @return void
     */
    private function createAdditionalLogs(
        AppointmentPaymentLog $booking,
        Appointment $appointment,
        array $subAppointmentIds
    ): void {
        $subAppointments = SubAppointment::whereIn('id', $subAppointmentIds)->get();

        foreach ($subAppointments as $subAppointment) {
            AppointmentPaymentAdditionalLog::create([
                'appointment_payment_log_id' => $booking->id,
                'appointment_id' => $appointment->id,
                'sub_appointment_id' => $subAppointment->id,
                'appointment_price' => $appointment->price,
                'sub_appointment_price' => $subAppointment->price,
            ]);
        }
    }

    /**
     * Delete a booking.
     *
     * @param AppointmentPaymentLog $booking
     * @return bool
     */
    public function deleteBooking(AppointmentPaymentLog $booking): bool
    {
        return DB::transaction(function () use ($booking) {
            // Delete additional logs
            $booking->additional_appointment_logs()->delete();

            return $booking->delete();
        });
    }

    /**
     * Bulk update booking status.
     *
     * @param array<int> $ids
     * @param string $status
     * @return int Number of updated bookings
     */
    public function bulkUpdateStatus(array $ids, string $status): int
    {
        return AppointmentPaymentLog::whereIn('id', $ids)->update(['status' => $status]);
    }

    // TODO: Uncomment when email integration is ready
    // /**
    //  * Send status notification email.
    //  *
    //  * @param AppointmentPaymentLog $booking
    //  * @param string $status
    //  * @param string|null $reason
    //  * @return void
    //  */
    // private function sendStatusNotification(AppointmentPaymentLog $booking, string $status, ?string $reason = null): void
    // {
    //     // Implementation for email notification
    // }

    // /**
    //  * Send reschedule notification email.
    //  *
    //  * @param AppointmentPaymentLog $booking
    //  * @return void
    //  */
    // private function sendRescheduleNotification(AppointmentPaymentLog $booking): void
    // {
    //     // Implementation for email notification
    // }

    // /**
    //  * Send payment approval notification email.
    //  *
    //  * @param AppointmentPaymentLog $booking
    //  * @return void
    //  */
    // private function sendPaymentApprovalNotification(AppointmentPaymentLog $booking): void
    // {
    //     // Implementation for email notification
    // }
}
