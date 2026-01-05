<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Modules\HotelBooking\Entities\RoomHold;
use Modules\HotelBooking\Entities\RoomType;

class RoomHoldService
{
    /**
     * Default hold duration in minutes.
     */
    protected int $holdDurationMinutes = 15;

    /**
     * Create holds for room selections during checkout.
     * Returns hold token if successful, null if rooms not available.
     */
    public function createHolds(array $roomSelections, string $checkIn, string $checkOut, ?string $sessionId = null): ?string
    {
        $holdToken = Str::uuid()->toString();
        $sessionId = $sessionId ?? session()->getId();
        $expiresAt = Carbon::now()->addMinutes($this->holdDurationMinutes);

        return DB::transaction(function () use ($roomSelections, $checkIn, $checkOut, $holdToken, $sessionId, $expiresAt) {
            // First, clean up expired holds (lazy cleanup)
            $this->cleanupExpiredHolds();

            // Verify availability and create holds
            foreach ($roomSelections as $selection) {
                $roomTypeId = $selection['room_type_id'];
                $quantity = $selection['quantity'] ?? 1;

                // Check availability with lock
                $isAvailable = $this->checkAvailabilityWithLock($roomTypeId, $checkIn, $checkOut, $quantity);

                if (!$isAvailable) {
                    // Rollback transaction - rooms not available
                    return null;
                }

                // Create hold
                RoomHold::create([
                    'room_type_id' => $roomTypeId,
                    'hold_token' => $holdToken,
                    'session_id' => $sessionId,
                    'check_in_date' => $checkIn,
                    'check_out_date' => $checkOut,
                    'quantity' => $quantity,
                    'expires_at' => $expiresAt,
                ]);
            }

            return $holdToken;
        });
    }

    /**
     * Check availability considering existing holds with lock.
     */
    protected function checkAvailabilityWithLock(int $roomTypeId, string $checkIn, string $checkOut, int $requiredQuantity): bool
    {
        $checkInDate = Carbon::parse($checkIn);
        $checkOutDate = Carbon::parse($checkOut);

        // Get inventory with lock for the date range (excluding checkout)
        $inventories = \Modules\HotelBooking\Entities\Inventory::where('room_type_id', $roomTypeId)
            ->whereBetween('date', [
                $checkIn,
                $checkOutDate->copy()->subDay()->format('Y-m-d')
            ])
            ->where('is_available', true)
            ->lockForUpdate()
            ->get();

        $nights = $checkInDate->diffInDays($checkOutDate);

        if ($inventories->count() < $nights) {
            return false;
        }

        // Check each night
        foreach ($inventories as $inventory) {
            // Get existing active holds for this date
            $heldQuantity = RoomHold::where('room_type_id', $roomTypeId)
                ->where('check_in_date', '<=', $inventory->date)
                ->where('check_out_date', '>', $inventory->date)
                ->active()
                ->sum('quantity');

            $effectiveAvailable = $inventory->available_rooms - $heldQuantity;

            if ($effectiveAvailable < $requiredQuantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extend hold duration.
     */
    public function extendHold(string $holdToken, ?int $additionalMinutes = null): bool
    {
        $additionalMinutes = $additionalMinutes ?? $this->holdDurationMinutes;

        $updated = RoomHold::where('hold_token', $holdToken)
            ->active()
            ->update([
                'expires_at' => Carbon::now()->addMinutes($additionalMinutes),
            ]);

        return $updated > 0;
    }

    /**
     * Release holds by token (e.g., when user abandons checkout).
     */
    public function releaseHolds(string $holdToken): int
    {
        return RoomHold::where('hold_token', $holdToken)->delete();
    }

    /**
     * Release holds by session ID.
     */
    public function releaseHoldsBySession(string $sessionId): int
    {
        return RoomHold::where('session_id', $sessionId)->delete();
    }

    /**
     * Convert holds to booking (marks them as used/deleted).
     * Called when booking is confirmed.
     */
    public function convertHoldsToBooking(string $holdToken, int $bookingId): bool
    {
        return DB::transaction(function () use ($holdToken, $bookingId) {
            $holds = RoomHold::where('hold_token', $holdToken)
                ->active()
                ->lockForUpdate()
                ->get();

            if ($holds->isEmpty()) {
                return false;
            }

            // Decrease inventory for each hold
            $inventoryService = app(InventoryService::class);

            foreach ($holds as $hold) {
                $inventoryService->decreaseAvailability(
                    $hold->room_type_id,
                    $hold->check_in_date,
                    $hold->check_out_date,
                    $hold->quantity
                );
            }

            // Delete holds after converting
            RoomHold::where('hold_token', $holdToken)->delete();

            return true;
        });
    }

    /**
     * Get holds by token.
     */
    public function getHolds(string $holdToken): \Illuminate\Database\Eloquent\Collection
    {
        return RoomHold::with('roomType')
            ->where('hold_token', $holdToken)
            ->active()
            ->get();
    }

    /**
     * Get hold summary with room details and pricing.
     */
    public function getHoldSummary(string $holdToken): ?array
    {
        $holds = $this->getHolds($holdToken);

        if ($holds->isEmpty()) {
            return null;
        }

        $firstHold = $holds->first();
        $pricingService = app(PricingService::class);

        $roomSelections = $holds->map(function ($hold) {
            return [
                'room_type_id' => $hold->room_type_id,
                'quantity' => $hold->quantity,
            ];
        })->toArray();

        $pricing = $pricingService->calculateMultiRoomPrice(
            $roomSelections,
            $firstHold->check_in_date,
            $firstHold->check_out_date
        );

        return [
            'hold_token' => $holdToken,
            'check_in_date' => $firstHold->check_in_date,
            'check_out_date' => $firstHold->check_out_date,
            'expires_at' => $firstHold->expires_at,
            'remaining_seconds' => max(0, Carbon::now()->diffInSeconds($firstHold->expires_at, false)),
            'rooms' => $holds->map(function ($hold) {
                return [
                    'room_type_id' => $hold->room_type_id,
                    'room_type_name' => $hold->roomType?->name,
                    'quantity' => $hold->quantity,
                ];
            }),
            'pricing' => $pricing,
        ];
    }

    /**
     * Check if hold is still valid.
     */
    public function isHoldValid(string $holdToken): bool
    {
        return RoomHold::where('hold_token', $holdToken)
            ->active()
            ->exists();
    }

    /**
     * Get remaining time for hold in seconds.
     */
    public function getRemainingTime(string $holdToken): int
    {
        $hold = RoomHold::where('hold_token', $holdToken)
            ->active()
            ->first();

        if (!$hold) {
            return 0;
        }

        return max(0, Carbon::now()->diffInSeconds($hold->expires_at, false));
    }

    /**
     * Clean up expired holds (lazy cleanup).
     * This is called automatically before creating new holds.
     */
    public function cleanupExpiredHolds(): int
    {
        return RoomHold::expired()->delete();
    }

    /**
     * Get active holds count for a room type on a specific date.
     */
    public function getActiveHoldsCount(int $roomTypeId, string $date): int
    {
        // Clean up first
        $this->cleanupExpiredHolds();

        return RoomHold::where('room_type_id', $roomTypeId)
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>', $date)
            ->active()
            ->sum('quantity');
    }

    /**
     * Get all active holds for admin monitoring.
     */
    public function getAllActiveHolds(): \Illuminate\Database\Eloquent\Collection
    {
        return RoomHold::with(['roomType.hotel'])
            ->active()
            ->orderBy('expires_at')
            ->get();
    }

    /**
     * Set hold duration (for testing or configuration).
     */
    public function setHoldDuration(int $minutes): void
    {
        $this->holdDurationMinutes = $minutes;
    }

    /**
     * Get hold duration in minutes.
     */
    public function getHoldDuration(): int
    {
        return $this->holdDurationMinutes;
    }

    /**
     * Validate and refresh hold before payment.
     * Re-checks availability and extends hold if valid.
     */
    public function validateAndRefreshHold(string $holdToken): bool
    {
        return DB::transaction(function () use ($holdToken) {
            $holds = RoomHold::where('hold_token', $holdToken)
                ->lockForUpdate()
                ->get();

            // Check if any holds exist
            if ($holds->isEmpty()) {
                return false;
            }

            // Check if expired
            $firstHold = $holds->first();
            if (Carbon::now()->isAfter($firstHold->expires_at)) {
                // Clean up expired holds
                RoomHold::where('hold_token', $holdToken)->delete();
                return false;
            }

            // Re-verify availability
            foreach ($holds as $hold) {
                $isAvailable = $this->checkAvailabilityWithLock(
                    $hold->room_type_id,
                    $hold->check_in_date,
                    $hold->check_out_date,
                    $hold->quantity
                );

                if (!$isAvailable) {
                    return false;
                }
            }

            // Extend hold
            $newExpiresAt = Carbon::now()->addMinutes($this->holdDurationMinutes);
            RoomHold::where('hold_token', $holdToken)->update([
                'expires_at' => $newExpiresAt,
            ]);

            return true;
        });
    }
}
