<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\HotelBooking\Entities\Room;
use Modules\HotelBooking\Entities\RoomImage;
use Modules\HotelBooking\Entities\RoomInventory;

final class RoomService
{
    /**
     * Get paginated rooms with filters.
     *
     * @param array<string, mixed> $filters
     */
    public function getRooms(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Room::query()
            ->with(['roomType', 'room_images']);

        if (!empty($filters['room_type_id'])) {
            $query->where('room_type_id', $filters['room_type_id']);
        }

        if (!empty($filters['hotel_id'])) {
            $query->whereHas('roomType', function ($q) use ($filters) {
                $q->where('hotel_id', $filters['hotel_id']);
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['is_featured'])) {
            $query->where('is_featured', $filters['is_featured']);
        }

        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }

    /**
     * Get rooms by room type.
     */
    public function getRoomsByRoomType(int $roomTypeId): Collection
    {
        return Room::with(['room_images'])
            ->where('room_type_id', $roomTypeId)
            ->get();
    }

    /**
     * Get a single room by ID.
     */
    public function getRoom(int $id): ?Room
    {
        return Room::with([
            'roomType.hotel',
            'roomType.bed_type',
            'room_images',
            'country',
            'state',
        ])->find($id);
    }

    /**
     * Create a new room.
     *
     * @param array<string, mixed> $data
     */
    public function createRoom(array $data): Room
    {
        return DB::transaction(function () use ($data) {
            $room = Room::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'room_type_id' => $data['room_type_id'],
                'base_cost' => $data['base_cost'] ?? null,
                'share_value' => $data['share_value'] ?? null,
                'location' => $data['location'] ?? null,
                'type' => $data['type'] ?? null,
                'duration' => $data['duration'] ?? null,
                'is_featured' => $data['is_featured'] ?? false,
                'status' => $data['status'] ?? true,
                'country_id' => $data['country_id'] ?? null,
                'state_id' => $data['state_id'] ?? null,
            ]);

            // Add images
            if (!empty($data['images'])) {
                $this->syncRoomImages($room, $data['images']);
            }

            return $room->fresh(['roomType', 'room_images']);
        });
    }

    /**
     * Update an existing room.
     *
     * @param array<string, mixed> $data
     */
    public function updateRoom(Room $room, array $data): Room
    {
        return DB::transaction(function () use ($room, $data) {
            $room->update(array_filter([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'room_type_id' => $data['room_type_id'] ?? null,
                'base_cost' => $data['base_cost'] ?? null,
                'share_value' => $data['share_value'] ?? null,
                'location' => $data['location'] ?? null,
                'type' => $data['type'] ?? null,
                'duration' => $data['duration'] ?? null,
                'is_featured' => $data['is_featured'] ?? null,
                'status' => $data['status'] ?? null,
                'country_id' => $data['country_id'] ?? null,
                'state_id' => $data['state_id'] ?? null,
            ], fn($value) => $value !== null));

            // Sync images if provided
            if (isset($data['images'])) {
                $this->syncRoomImages($room, $data['images']);
            }

            return $room->fresh(['roomType', 'room_images']);
        });
    }

    /**
     * Delete a room.
     */
    public function deleteRoom(Room $room): bool
    {
        return DB::transaction(function () use ($room) {
            // Delete related images
            $room->room_images()->delete();

            return $room->delete();
        });
    }

    /**
     * Toggle room status.
     */
    public function toggleStatus(Room $room): Room
    {
        $room->status = !$room->status;
        $room->save();

        return $room;
    }

    /**
     * Sync room images.
     *
     * @param array<string> $images
     */
    public function syncRoomImages(Room $room, array $images): void
    {
        // Delete existing images
        $room->room_images()->delete();

        // Add new images
        foreach ($images as $image) {
            RoomImage::create([
                'room_id' => $room->id,
                'image' => $image,
            ]);
        }
    }

    /**
     * Check room availability for a date range.
     */
    public function checkAvailability(int $roomId, string $startDate, string $endDate): bool
    {
        $bookedDates = RoomInventory::where('room_id', $roomId)
            ->whereBetween('booked_date', [$startDate, $endDate])
            ->count();

        return $bookedDates === 0;
    }

    /**
     * Block a room for specific dates.
     */
    public function blockRoom(Room $room, string $startDate, string $endDate, ?string $reason = null): void
    {
        $period = new \DatePeriod(
            new \DateTime($startDate),
            new \DateInterval('P1D'),
            (new \DateTime($endDate))->modify('+1 day')
        );

        foreach ($period as $date) {
            RoomInventory::updateOrCreate(
                [
                    'room_id' => $room->id,
                    'booked_date' => $date->format('Y-m-d'),
                ],
                [
                    'room_type_id' => $room->room_type_id,
                    'inventory_id' => 0, // Blocked, not from inventory
                    'status' => -1, // Blocked status
                ]
            );
        }
    }

    /**
     * Unblock a room for specific dates.
     */
    public function unblockRoom(Room $room, string $startDate, string $endDate): void
    {
        RoomInventory::where('room_id', $room->id)
            ->whereBetween('booked_date', [$startDate, $endDate])
            ->where('status', -1) // Only remove blocked entries
            ->delete();
    }

    /**
     * Get room's booked dates.
     */
    public function getBookedDates(int $roomId, ?string $startDate = null, ?string $endDate = null): Collection
    {
        $query = RoomInventory::where('room_id', $roomId);

        if ($startDate && $endDate) {
            $query->whereBetween('booked_date', [$startDate, $endDate]);
        }

        return $query->pluck('booked_date');
    }
}
