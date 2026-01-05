<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\HotelBooking\Entities\Inventory;
use Modules\HotelBooking\Entities\RoomType;

class InventoryService
{
    /**
     * Get inventory for a room type within a date range.
     */
    public function getInventory(int $roomTypeId, string $startDate, string $endDate): Collection
    {
        return Inventory::where('room_type_id', $roomTypeId)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }

    /**
     * Get inventory for a specific date.
     */
    public function getInventoryForDate(int $roomTypeId, string $date): ?Inventory
    {
        return Inventory::where('room_type_id', $roomTypeId)
            ->where('date', $date)
            ->first();
    }

    /**
     * Create or update inventory for a single date.
     */
    public function updateInventory(int $roomTypeId, string $date, array $data): Inventory
    {
        return Inventory::updateOrCreate(
            [
                'room_type_id' => $roomTypeId,
                'date' => $date,
            ],
            [
                'total_rooms' => $data['total_rooms'] ?? null,
                'available_rooms' => $data['available_rooms'] ?? null,
                'price' => $data['price'] ?? null,
                'is_available' => $data['is_available'] ?? true,
            ]
        );
    }

    /**
     * Bulk update inventory for a date range.
     * Useful for setting seasonal prices or blocking dates.
     */
    public function bulkUpdateInventory(int $roomTypeId, string $startDate, string $endDate, array $data): int
    {
        $period = CarbonPeriod::create($startDate, $endDate);
        $count = 0;

        DB::transaction(function () use ($roomTypeId, $period, $data, &$count) {
            foreach ($period as $date) {
                $dateString = $date->format('Y-m-d');
                
                // Check if should apply to specific days of week
                if (!empty($data['days_of_week'])) {
                    if (!in_array($date->dayOfWeek, $data['days_of_week'])) {
                        continue;
                    }
                }

                $inventoryData = [];
                
                if (isset($data['total_rooms'])) {
                    $inventoryData['total_rooms'] = $data['total_rooms'];
                }
                if (isset($data['available_rooms'])) {
                    $inventoryData['available_rooms'] = $data['available_rooms'];
                }
                if (isset($data['price'])) {
                    $inventoryData['price'] = $data['price'];
                }
                if (isset($data['is_available'])) {
                    $inventoryData['is_available'] = $data['is_available'];
                }

                if (!empty($inventoryData)) {
                    $this->updateInventory($roomTypeId, $dateString, $inventoryData);
                    $count++;
                }
            }
        });

        return $count;
    }

    /**
     * Initialize inventory for a room type based on total rooms.
     * Creates inventory records for the next N days.
     */
    public function initializeInventory(int $roomTypeId, int $totalRooms, float $basePrice, int $days = 365): int
    {
        $roomType = RoomType::findOrFail($roomTypeId);
        $startDate = Carbon::today();
        $endDate = $startDate->copy()->addDays($days);
        
        $period = CarbonPeriod::create($startDate, $endDate);
        $count = 0;

        DB::transaction(function () use ($roomTypeId, $totalRooms, $basePrice, $period, &$count) {
            foreach ($period as $date) {
                Inventory::firstOrCreate(
                    [
                        'room_type_id' => $roomTypeId,
                        'date' => $date->format('Y-m-d'),
                    ],
                    [
                        'total_rooms' => $totalRooms,
                        'available_rooms' => $totalRooms,
                        'price' => $basePrice,
                        'is_available' => true,
                    ]
                );
                $count++;
            }
        });

        return $count;
    }

    /**
     * Check availability for a date range with locking.
     * Returns available rooms count or false if not available.
     *
     * @return int|false
     */
    public function checkAvailabilityWithLock(int $roomTypeId, string $startDate, string $endDate, int $requiredRooms = 1): int|false
    {
        $inventories = Inventory::where('room_type_id', $roomTypeId)
            ->whereBetween('date', [$startDate, Carbon::parse($endDate)->subDay()->format('Y-m-d')])
            ->where('is_available', true)
            ->lockForUpdate()
            ->get();

        $period = CarbonPeriod::create($startDate, Carbon::parse($endDate)->subDay());
        $daysRequired = iterator_count($period);

        if ($inventories->count() < $daysRequired) {
            return false;
        }

        $minAvailable = $inventories->min('available_rooms');

        if ($minAvailable < $requiredRooms) {
            return false;
        }

        return $minAvailable;
    }

    /**
     * Decrease available rooms for a date range (when booking is confirmed).
     * Must be called within a transaction with lock.
     */
    public function decreaseAvailability(int $roomTypeId, string $startDate, string $endDate, int $quantity = 1): void
    {
        // Exclude checkout date from inventory reduction
        $checkoutDate = Carbon::parse($endDate);
        $lastNightDate = $checkoutDate->copy()->subDay()->format('Y-m-d');

        Inventory::where('room_type_id', $roomTypeId)
            ->whereBetween('date', [$startDate, $lastNightDate])
            ->where('is_available', true)
            ->where('available_rooms', '>=', $quantity)
            ->decrement('available_rooms', $quantity);
    }

    /**
     * Increase available rooms for a date range (when booking is cancelled).
     */
    public function increaseAvailability(int $roomTypeId, string $startDate, string $endDate, int $quantity = 1): void
    {
        // Exclude checkout date
        $checkoutDate = Carbon::parse($endDate);
        $lastNightDate = $checkoutDate->copy()->subDay()->format('Y-m-d');

        Inventory::where('room_type_id', $roomTypeId)
            ->whereBetween('date', [$startDate, $lastNightDate])
            ->update([
                'available_rooms' => DB::raw("LEAST(available_rooms + {$quantity}, total_rooms)"),
            ]);
    }

    /**
     * Block dates for a room type (e.g., maintenance, renovation).
     */
    public function blockDates(int $roomTypeId, string $startDate, string $endDate, string $reason = null): int
    {
        return $this->bulkUpdateInventory($roomTypeId, $startDate, $endDate, [
            'is_available' => false,
        ]);
    }

    /**
     * Unblock dates for a room type.
     */
    public function unblockDates(int $roomTypeId, string $startDate, string $endDate): int
    {
        return $this->bulkUpdateInventory($roomTypeId, $startDate, $endDate, [
            'is_available' => true,
        ]);
    }

    /**
     * Set seasonal pricing for a room type.
     */
    public function setSeasonalPricing(int $roomTypeId, string $startDate, string $endDate, float $price, ?array $daysOfWeek = null): int
    {
        $data = ['price' => $price];
        
        if ($daysOfWeek !== null) {
            $data['days_of_week'] = $daysOfWeek;
        }

        return $this->bulkUpdateInventory($roomTypeId, $startDate, $endDate, $data);
    }

    /**
     * Set weekend pricing (Friday, Saturday).
     */
    public function setWeekendPricing(int $roomTypeId, string $startDate, string $endDate, float $price): int
    {
        return $this->setSeasonalPricing($roomTypeId, $startDate, $endDate, $price, [5, 6]); // Friday, Saturday
    }

    /**
     * Get calendar view of inventory.
     * Returns array with date as key and inventory info as value.
     */
    public function getCalendarView(int $roomTypeId, string $month): array
    {
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        $inventories = $this->getInventory($roomTypeId, $startDate->format('Y-m-d'), $endDate->format('Y-m-d'))
            ->keyBy('date');

        $calendar = [];
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $inventory = $inventories->get($dateString);

            $calendar[$dateString] = [
                'date' => $dateString,
                'day_of_week' => $date->dayOfWeek,
                'day_name' => $date->format('D'),
                'total_rooms' => $inventory?->total_rooms ?? 0,
                'available_rooms' => $inventory?->available_rooms ?? 0,
                'price' => $inventory?->price ?? null,
                'is_available' => $inventory?->is_available ?? false,
                'occupancy_rate' => $inventory && $inventory->total_rooms > 0
                    ? round((($inventory->total_rooms - $inventory->available_rooms) / $inventory->total_rooms) * 100, 1)
                    : 0,
            ];
        }

        return $calendar;
    }

    /**
     * Get occupancy statistics for a date range.
     */
    public function getOccupancyStats(int $roomTypeId, string $startDate, string $endDate): array
    {
        $inventories = $this->getInventory($roomTypeId, $startDate, $endDate);

        if ($inventories->isEmpty()) {
            return [
                'total_room_nights' => 0,
                'booked_room_nights' => 0,
                'available_room_nights' => 0,
                'occupancy_rate' => 0,
                'average_price' => 0,
                'revenue_potential' => 0,
            ];
        }

        $totalRoomNights = $inventories->sum('total_rooms');
        $availableRoomNights = $inventories->sum('available_rooms');
        $bookedRoomNights = $totalRoomNights - $availableRoomNights;
        $averagePrice = $inventories->avg('price');

        return [
            'total_room_nights' => $totalRoomNights,
            'booked_room_nights' => $bookedRoomNights,
            'available_room_nights' => $availableRoomNights,
            'occupancy_rate' => $totalRoomNights > 0 
                ? round(($bookedRoomNights / $totalRoomNights) * 100, 1) 
                : 0,
            'average_price' => round($averagePrice, 2),
            'revenue_potential' => round($bookedRoomNights * $averagePrice, 2),
        ];
    }

    /**
     * Sync inventory with actual room count.
     * Useful when rooms are added/removed from a room type.
     */
    public function syncInventoryWithRoomCount(int $roomTypeId): void
    {
        $roomType = RoomType::with('rooms')->findOrFail($roomTypeId);
        $activeRooms = $roomType->rooms()->where('status', 1)->count();

        // Update future inventory
        Inventory::where('room_type_id', $roomTypeId)
            ->where('date', '>=', Carbon::today()->format('Y-m-d'))
            ->update([
                'total_rooms' => $activeRooms,
                'available_rooms' => DB::raw("LEAST(available_rooms, {$activeRooms})"),
            ]);
    }
}
