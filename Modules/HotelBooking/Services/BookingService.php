<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Services;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\HotelBooking\Entities\BookingInformation;
use Modules\HotelBooking\Entities\BookingRoomType;
use Modules\HotelBooking\Entities\Hotel;
use Modules\HotelBooking\Entities\RoomType;
use Modules\HotelBooking\Entities\CancellationPolicy;

class BookingService
{
    protected RoomHoldService $roomHoldService;
    protected PricingService $pricingService;
    protected InventoryService $inventoryService;

    public function __construct(
        RoomHoldService $roomHoldService,
        PricingService $pricingService,
        InventoryService $inventoryService
    ) {
        $this->roomHoldService = $roomHoldService;
        $this->pricingService = $pricingService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Get paginated bookings with filters.
     */
    public function getBookings(array $filters = []): LengthAwarePaginator
    {
        $query = BookingInformation::with(['hotel', 'user', 'bookingRoomTypes.roomType'])
            ->latest();

        // Filter by hotel
        if (!empty($filters['hotel_id'])) {
            $query->where('hotel_id', $filters['hotel_id']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by payment status
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        // Filter by date range
        if (!empty($filters['from_date'])) {
            $query->where('check_in_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->where('check_out_date', '<=', $filters['to_date']);
        }

        // Filter by guest name/email
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                $q->where('guest_name', 'like', "%{$search}%")
                  ->orWhere('guest_email', 'like', "%{$search}%")
                  ->orWhere('booking_code', 'like', "%{$search}%");
            });
        }

        // Filter by user
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Today's check-ins
        if (!empty($filters['today_checkins'])) {
            $query->todayCheckIns();
        }

        // Today's check-outs
        if (!empty($filters['today_checkouts'])) {
            $query->todayCheckOuts();
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get a single booking by ID.
     */
    public function getBooking(int $id): ?BookingInformation
    {
        return BookingInformation::with([
            'hotel',
            'user',
            'bookingRoomTypes.roomType',
            'cancellationPolicy',
        ])->find($id);
    }

    /**
     * Get booking by booking code.
     */
    public function getBookingByCode(string $bookingCode): ?BookingInformation
    {
        return BookingInformation::with([
            'hotel',
            'user',
            'bookingRoomTypes.roomType',
        ])->where('booking_code', $bookingCode)->first();
    }

    /**
     * Get bookings for a user.
     */
    public function getUserBookings(int $userId, array $filters = []): LengthAwarePaginator
    {
        $filters['user_id'] = $userId;
        return $this->getBookings($filters);
    }

    /**
     * Create a booking from hold token.
     */
    public function createBookingFromHold(string $holdToken, array $guestData, ?int $userId = null): ?BookingInformation
    {
        // Validate and refresh hold
        if (!$this->roomHoldService->validateAndRefreshHold($holdToken)) {
            return null;
        }

        // Get hold summary
        $holdSummary = $this->roomHoldService->getHoldSummary($holdToken);

        if (!$holdSummary) {
            return null;
        }

        return DB::transaction(function () use ($holdToken, $holdSummary, $guestData, $userId) {
            // Get hotel from first room type
            $firstRoomTypeId = $holdSummary['rooms'][0]['room_type_id'];
            $roomType = RoomType::find($firstRoomTypeId);
            $hotel = $roomType->hotel;

            // Generate booking code
            $bookingCode = $this->generateBookingCode();

            // Create booking
            $booking = BookingInformation::create([
                'booking_code' => $bookingCode,
                'hotel_id' => $hotel->id,
                'user_id' => $userId,
                'guest_name' => $guestData['guest_name'],
                'guest_email' => $guestData['guest_email'],
                'guest_phone' => $guestData['guest_phone'] ?? null,
                'check_in_date' => $holdSummary['check_in_date'],
                'check_out_date' => $holdSummary['check_out_date'],
                'check_in_time' => $guestData['check_in_time'] ?? '15:00:00',
                'check_out_time' => $guestData['check_out_time'] ?? '11:00:00',
                'adults' => $guestData['adults'] ?? 2,
                'children' => $guestData['children'] ?? 0,
                'total_rooms' => collect($holdSummary['rooms'])->sum('quantity'),
                'nights' => $holdSummary['pricing']['nights'],
                'room_subtotal' => $holdSummary['pricing']['rooms_subtotal'],
                'meal_total' => $holdSummary['pricing']['meals_total'] ?? 0,
                'extras_total' => $holdSummary['pricing']['extras_total'] ?? 0,
                'subtotal' => $holdSummary['pricing']['subtotal'],
                'tax_rate' => $holdSummary['pricing']['tax_rate'],
                'tax_amount' => $holdSummary['pricing']['tax_amount'],
                'total_amount' => $holdSummary['pricing']['total'],
                'currency' => $holdSummary['pricing']['currency'] ?? 'SAR',
                'status' => BookingInformation::STATUS_PENDING,
                'payment_status' => BookingInformation::PAYMENT_PENDING,
                'cancellation_policy_id' => $hotel->cancellation_policy_id,
                'special_requests' => $guestData['special_requests'] ?? null,
                'meal_plan' => $guestData['meal_plan'] ?? null,
                'extras' => $guestData['extras'] ?? null,
            ]);

            // Create booking room types (multi-room support)
            foreach ($holdSummary['rooms'] as $room) {
                $roomTypeData = collect($holdSummary['pricing']['room_breakdowns'])
                    ->firstWhere('room_type_id', $room['room_type_id']);

                BookingRoomType::create([
                    'booking_id' => $booking->id,
                    'room_type_id' => $room['room_type_id'],
                    'quantity' => $room['quantity'],
                    'unit_price' => $roomTypeData ? ($roomTypeData['room_subtotal'] / $room['quantity'] / $holdSummary['pricing']['nights']) : 0,
                    'subtotal' => $roomTypeData['room_subtotal'] ?? 0,
                    'meal_plan' => $guestData['room_meal_plans'][$room['room_type_id']] ?? null,
                    'meal_total' => $roomTypeData['meal_total'] ?? 0,
                ]);
            }

            // Convert holds to booking (decreases inventory)
            $this->roomHoldService->convertHoldsToBooking($holdToken, $booking->id);

            return $booking->fresh(['hotel', 'bookingRoomTypes.roomType']);
        });
    }

    /**
     * Create a booking directly (without hold, for admin).
     */
    public function createBookingDirect(array $data): ?BookingInformation
    {
        return DB::transaction(function () use ($data) {
            // Calculate pricing
            $pricing = $this->pricingService->calculateMultiRoomPrice(
                $data['rooms'],
                $data['check_in_date'],
                $data['check_out_date'],
                [
                    'tax_rate' => $data['tax_rate'] ?? 0.15,
                    'extras' => $data['extras'] ?? [],
                ]
            );

            // Check availability and lock
            foreach ($data['rooms'] as $room) {
                $available = $this->inventoryService->checkAvailabilityWithLock(
                    $room['room_type_id'],
                    $data['check_in_date'],
                    $data['check_out_date'],
                    $room['quantity']
                );

                if ($available === false) {
                    throw new \Exception("Room type {$room['room_type_id']} not available for the selected dates.");
                }
            }

            // Get hotel
            $firstRoomType = RoomType::find($data['rooms'][0]['room_type_id']);
            $hotel = $firstRoomType->hotel;

            // Create booking
            $booking = BookingInformation::create([
                'booking_code' => $this->generateBookingCode(),
                'hotel_id' => $hotel->id,
                'user_id' => $data['user_id'] ?? null,
                'guest_name' => $data['guest_name'],
                'guest_email' => $data['guest_email'],
                'guest_phone' => $data['guest_phone'] ?? null,
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'check_in_time' => $data['check_in_time'] ?? '15:00:00',
                'check_out_time' => $data['check_out_time'] ?? '11:00:00',
                'adults' => $data['adults'] ?? 2,
                'children' => $data['children'] ?? 0,
                'total_rooms' => collect($data['rooms'])->sum('quantity'),
                'nights' => $pricing['nights'],
                'room_subtotal' => $pricing['rooms_subtotal'],
                'meal_total' => $pricing['meals_total'],
                'extras_total' => $pricing['extras_total'],
                'subtotal' => $pricing['subtotal'],
                'tax_rate' => $pricing['tax_rate'],
                'tax_amount' => $pricing['tax_amount'],
                'total_amount' => $pricing['total'],
                'currency' => $pricing['currency'],
                'status' => $data['status'] ?? BookingInformation::STATUS_PENDING,
                'payment_status' => $data['payment_status'] ?? BookingInformation::PAYMENT_PENDING,
                'cancellation_policy_id' => $hotel->cancellation_policy_id,
                'special_requests' => $data['special_requests'] ?? null,
                'meal_plan' => $data['meal_plan'] ?? null,
                'extras' => $data['extras'] ?? null,
            ]);

            // Create booking room types
            foreach ($data['rooms'] as $room) {
                $roomTypeData = collect($pricing['room_breakdowns'])
                    ->firstWhere('room_type_id', $room['room_type_id']);

                BookingRoomType::create([
                    'booking_id' => $booking->id,
                    'room_type_id' => $room['room_type_id'],
                    'quantity' => $room['quantity'],
                    'unit_price' => $roomTypeData['room_subtotal'] / $room['quantity'] / $pricing['nights'],
                    'subtotal' => $roomTypeData['room_subtotal'],
                    'meal_plan' => $room['meal_plan'] ?? null,
                    'meal_total' => $roomTypeData['meal_total'] ?? 0,
                ]);

                // Decrease inventory
                $this->inventoryService->decreaseAvailability(
                    $room['room_type_id'],
                    $data['check_in_date'],
                    $data['check_out_date'],
                    $room['quantity']
                );
            }

            return $booking->fresh(['hotel', 'bookingRoomTypes.roomType']);
        });
    }

    /**
     * Update booking status.
     */
    public function updateStatus(int $bookingId, string $status, ?string $notes = null): BookingInformation
    {
        $booking = BookingInformation::findOrFail($bookingId);
        
        $booking->update([
            'status' => $status,
            'admin_notes' => $notes ? $booking->admin_notes . "\n" . $notes : $booking->admin_notes,
        ]);

        return $booking->fresh();
    }

    /**
     * Confirm a booking (after payment).
     */
    public function confirmBooking(int $bookingId, array $paymentData = []): BookingInformation
    {
        $booking = BookingInformation::findOrFail($bookingId);

        $booking->update([
            'status' => BookingInformation::STATUS_CONFIRMED,
            'payment_status' => BookingInformation::PAYMENT_PAID,
            'paid_amount' => $paymentData['amount'] ?? $booking->total_amount,
            'payment_method' => $paymentData['method'] ?? null,
            'payment_reference' => $paymentData['reference'] ?? null,
            'confirmed_at' => now(),
        ]);

        return $booking->fresh();
    }

    /**
     * Process check-in.
     */
    public function checkIn(int $bookingId): BookingInformation
    {
        $booking = BookingInformation::findOrFail($bookingId);

        if (!$booking->canCheckIn()) {
            throw new \Exception('Check-in is not available at this time.');
        }

        if ($booking->status !== BookingInformation::STATUS_CONFIRMED) {
            throw new \Exception('Booking must be confirmed before check-in.');
        }

        $booking->update([
            'status' => BookingInformation::STATUS_CHECKED_IN,
            'checked_in_at' => now(),
        ]);

        return $booking->fresh();
    }

    /**
     * Process check-out.
     */
    public function checkOut(int $bookingId): BookingInformation
    {
        $booking = BookingInformation::findOrFail($bookingId);

        if ($booking->status !== BookingInformation::STATUS_CHECKED_IN) {
            throw new \Exception('Guest must be checked in before check-out.');
        }

        $booking->update([
            'status' => BookingInformation::STATUS_CHECKED_OUT,
            'checked_out_at' => now(),
        ]);

        return $booking->fresh();
    }

    /**
     * Cancel a booking.
     */
    public function cancelBooking(int $bookingId, string $reason, bool $processRefund = true): array
    {
        $booking = BookingInformation::findOrFail($bookingId);

        if (!$booking->canBeCancelled()) {
            throw new \Exception('This booking cannot be cancelled.');
        }

        return DB::transaction(function () use ($booking, $reason, $processRefund) {
            // Calculate refund
            $refundInfo = $this->pricingService->calculateRefundAmount(
                $booking->paid_amount ?? $booking->total_amount,
                $booking->check_in_date,
                $booking->cancellation_policy_id
            );

            // Update booking
            $booking->update([
                'status' => BookingInformation::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
                'refund_amount' => $refundInfo['refund_amount'],
                'refund_status' => $processRefund && $refundInfo['refund_amount'] > 0 
                    ? BookingInformation::REFUND_PENDING 
                    : BookingInformation::REFUND_NOT_APPLICABLE,
            ]);

            // Restore inventory
            foreach ($booking->bookingRoomTypes as $bookingRoomType) {
                $this->inventoryService->increaseAvailability(
                    $bookingRoomType->room_type_id,
                    $booking->check_in_date,
                    $booking->check_out_date,
                    $bookingRoomType->quantity
                );
            }

            return [
                'booking' => $booking->fresh(),
                'refund_info' => $refundInfo,
            ];
        });
    }

    /**
     * Mark as no-show.
     */
    public function markNoShow(int $bookingId): BookingInformation
    {
        $booking = BookingInformation::findOrFail($bookingId);

        if ($booking->status !== BookingInformation::STATUS_CONFIRMED) {
            throw new \Exception('Only confirmed bookings can be marked as no-show.');
        }

        // Check if check-in date has passed
        if (Carbon::parse($booking->check_in_date)->isFuture()) {
            throw new \Exception('Cannot mark as no-show before check-in date.');
        }

        $booking->update([
            'status' => BookingInformation::STATUS_NO_SHOW,
        ]);

        // Restore inventory for remaining nights
        foreach ($booking->bookingRoomTypes as $bookingRoomType) {
            $this->inventoryService->increaseAvailability(
                $bookingRoomType->room_type_id,
                Carbon::today()->format('Y-m-d'),
                $booking->check_out_date,
                $bookingRoomType->quantity
            );
        }

        return $booking->fresh();
    }

    /**
     * Update booking details (admin).
     */
    public function updateBooking(int $bookingId, array $data): BookingInformation
    {
        $booking = BookingInformation::findOrFail($bookingId);

        $updateData = [];

        // Allowed updates
        $allowedFields = [
            'guest_name', 'guest_email', 'guest_phone',
            'adults', 'children', 'special_requests',
            'check_in_time', 'check_out_time',
            'admin_notes',
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (!empty($updateData)) {
            $booking->update($updateData);
        }

        return $booking->fresh();
    }

    /**
     * Generate unique booking code.
     */
    protected function generateBookingCode(): string
    {
        do {
            $code = 'BK' . strtoupper(Str::random(8));
        } while (BookingInformation::where('booking_code', $code)->exists());

        return $code;
    }

    /**
     * Get booking statistics.
     */
    public function getBookingStats(?int $hotelId = null, ?string $period = 'month'): array
    {
        $query = BookingInformation::query();

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        // Date range based on period
        $startDate = match ($period) {
            'today' => Carbon::today(),
            'week' => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year' => Carbon::now()->startOfYear(),
            default => Carbon::now()->startOfMonth(),
        };

        $query->where('created_at', '>=', $startDate);

        $bookings = $query->get();

        return [
            'total_bookings' => $bookings->count(),
            'confirmed_bookings' => $bookings->where('status', BookingInformation::STATUS_CONFIRMED)->count(),
            'cancelled_bookings' => $bookings->where('status', BookingInformation::STATUS_CANCELLED)->count(),
            'checked_in' => $bookings->where('status', BookingInformation::STATUS_CHECKED_IN)->count(),
            'checked_out' => $bookings->where('status', BookingInformation::STATUS_CHECKED_OUT)->count(),
            'no_shows' => $bookings->where('status', BookingInformation::STATUS_NO_SHOW)->count(),
            'total_revenue' => $bookings->where('payment_status', BookingInformation::PAYMENT_PAID)->sum('total_amount'),
            'total_rooms_booked' => $bookings->sum('total_rooms'),
            'total_nights' => $bookings->sum('nights'),
            'average_booking_value' => $bookings->avg('total_amount'),
            'period' => $period,
            'start_date' => $startDate->format('Y-m-d'),
        ];
    }

    /**
     * Get today's arrivals.
     */
    public function getTodayArrivals(?int $hotelId = null): Collection
    {
        $query = BookingInformation::with(['hotel', 'user', 'bookingRoomTypes.roomType'])
            ->todayCheckIns()
            ->whereIn('status', [
                BookingInformation::STATUS_CONFIRMED,
            ]);

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        return $query->orderBy('check_in_time')->get();
    }

    /**
     * Get today's departures.
     */
    public function getTodayDepartures(?int $hotelId = null): Collection
    {
        $query = BookingInformation::with(['hotel', 'user', 'bookingRoomTypes.roomType'])
            ->todayCheckOuts()
            ->whereIn('status', [
                BookingInformation::STATUS_CHECKED_IN,
            ]);

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        return $query->orderBy('check_out_time')->get();
    }

    /**
     * Get in-house guests.
     */
    public function getInHouseGuests(?int $hotelId = null): Collection
    {
        $query = BookingInformation::with(['hotel', 'user', 'bookingRoomTypes.roomType'])
            ->where('status', BookingInformation::STATUS_CHECKED_IN);

        if ($hotelId) {
            $query->where('hotel_id', $hotelId);
        }

        return $query->orderBy('check_out_date')->get();
    }

    /**
     * Send booking confirmation email.
     */
    public function sendConfirmationEmail(int $bookingId): bool
    {
        $booking = $this->getBooking($bookingId);

        if (!$booking) {
            return false;
        }

        // TODO: Implement email sending via Mail facade
        // Mail::to($booking->guest_email)->send(new BookingConfirmation($booking));

        return true;
    }

    /**
     * Get upcoming bookings for a user.
     */
    public function getUpcomingBookings(int $userId, int $limit = 5): Collection
    {
        return BookingInformation::with(['hotel', 'bookingRoomTypes.roomType'])
            ->where('user_id', $userId)
            ->where('check_in_date', '>=', Carbon::today())
            ->whereIn('status', [
                BookingInformation::STATUS_PENDING,
                BookingInformation::STATUS_CONFIRMED,
            ])
            ->orderBy('check_in_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Get past bookings for a user.
     */
    public function getPastBookings(int $userId, int $limit = 10): Collection
    {
        return BookingInformation::with(['hotel', 'bookingRoomTypes.roomType'])
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query->where('check_out_date', '<', Carbon::today())
                      ->orWhere('status', BookingInformation::STATUS_CHECKED_OUT)
                      ->orWhere('status', BookingInformation::STATUS_CANCELLED);
            })
            ->orderByDesc('check_out_date')
            ->limit($limit)
            ->get();
    }
}
