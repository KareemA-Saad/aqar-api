<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\HotelBooking\Entities\RoomType;

final class RoomTypeService
{
    /**
     * Get paginated room types with filters.
     *
     * @param array<string, mixed> $filters
     */
    public function getRoomTypes(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = RoomType::query()
            ->with(['hotel', 'bed_type', 'room_type_amenities']);

        if (!empty($filters['hotel_id'])) {
            $query->where('hotel_id', $filters['hotel_id']);
        }

        if (!empty($filters['bed_type_id'])) {
            $query->where('bed_type_id', $filters['bed_type_id']);
        }

        if (!empty($filters['min_guests'])) {
            $query->where('max_guest', '>=', $filters['min_guests']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('base_charge', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('base_charge', '<=', $filters['max_price']);
        }

        if (!empty($filters['amenity_ids']) && is_array($filters['amenity_ids'])) {
            $query->whereHas('room_type_amenities', function ($q) use ($filters) {
                $q->whereIn('amenity_id', $filters['amenity_ids']);
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        return $query->orderBy($sortBy, $sortOrder)->paginate($perPage);
    }

    /**
     * Get room types for a specific hotel.
     */
    public function getRoomTypesByHotel(int $hotelId): Collection
    {
        return RoomType::with(['bed_type', 'room_type_amenities'])
            ->where('hotel_id', $hotelId)
            ->get();
    }

    /**
     * Get a single room type by ID.
     */
    public function getRoomType(int $id): ?RoomType
    {
        return RoomType::with([
            'hotel',
            'bed_type',
            'room_type_amenities',
        ])->find($id);
    }

    /**
     * Create a new room type.
     *
     * @param array<string, mixed> $data
     */
    public function createRoomType(array $data): RoomType
    {
        return DB::transaction(function () use ($data) {
            $roomType = RoomType::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'hotel_id' => $data['hotel_id'],
                'max_guest' => $data['max_guest'],
                'max_adult' => $data['max_adult'] ?? $data['max_guest'],
                'max_child' => $data['max_child'] ?? 0,
                'no_bedroom' => $data['no_bedroom'] ?? 1,
                'no_living_room' => $data['no_living_room'] ?? 0,
                'no_bathrooms' => $data['no_bathrooms'] ?? 1,
                'base_charge' => $data['base_charge'],
                'extra_adult' => $data['extra_adult'] ?? 0,
                'extra_child' => $data['extra_child'] ?? 0,
                'breakfast_price' => $data['breakfast_price'] ?? null,
                'lunch_price' => $data['lunch_price'] ?? null,
                'dinner_price' => $data['dinner_price'] ?? null,
                'bed_type_id' => $data['bed_type_id'] ?? null,
                'extra_bed_type_id' => $data['extra_bed_type_id'] ?? null,
            ]);

            // Sync amenities
            if (!empty($data['amenity_ids'])) {
                $roomType->room_type_amenities()->sync($data['amenity_ids']);
            }

            return $roomType->fresh(['hotel', 'bed_type', 'room_type_amenities']);
        });
    }

    /**
     * Update an existing room type.
     *
     * @param array<string, mixed> $data
     */
    public function updateRoomType(RoomType $roomType, array $data): RoomType
    {
        return DB::transaction(function () use ($roomType, $data) {
            $roomType->update(array_filter([
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
                'hotel_id' => $data['hotel_id'] ?? null,
                'max_guest' => $data['max_guest'] ?? null,
                'max_adult' => $data['max_adult'] ?? null,
                'max_child' => $data['max_child'] ?? null,
                'no_bedroom' => $data['no_bedroom'] ?? null,
                'no_living_room' => $data['no_living_room'] ?? null,
                'no_bathrooms' => $data['no_bathrooms'] ?? null,
                'base_charge' => $data['base_charge'] ?? null,
                'extra_adult' => $data['extra_adult'] ?? null,
                'extra_child' => $data['extra_child'] ?? null,
                'breakfast_price' => $data['breakfast_price'] ?? null,
                'lunch_price' => $data['lunch_price'] ?? null,
                'dinner_price' => $data['dinner_price'] ?? null,
                'bed_type_id' => $data['bed_type_id'] ?? null,
                'extra_bed_type_id' => $data['extra_bed_type_id'] ?? null,
            ], fn($value) => $value !== null));

            // Sync amenities if provided
            if (isset($data['amenity_ids'])) {
                $roomType->room_type_amenities()->sync($data['amenity_ids']);
            }

            return $roomType->fresh(['hotel', 'bed_type', 'room_type_amenities']);
        });
    }

    /**
     * Delete a room type.
     */
    public function deleteRoomType(RoomType $roomType): bool
    {
        return DB::transaction(function () use ($roomType) {
            // Detach amenities
            $roomType->room_type_amenities()->detach();

            return $roomType->delete();
        });
    }

    /**
     * Get room types with available rooms for date range.
     *
     * @param array<string, mixed> $filters
     */
    public function getAvailableRoomTypes(
        int $hotelId,
        string $checkIn,
        string $checkOut,
        int $adults = 1,
        int $children = 0,
        int $rooms = 1,
        array $filters = []
    ): Collection {
        $query = RoomType::with(['bed_type', 'room_type_amenities'])
            ->where('hotel_id', $hotelId)
            ->where('max_adult', '>=', $adults)
            ->where('max_child', '>=', $children);

        // Add available_rooms count based on inventory
        $query->addSelect([
            'available_rooms' => \Modules\HotelBooking\Entities\Inventory::selectRaw('MIN(available_room)')
                ->whereColumn('room_type_id', 'room_types.id')
                ->whereBetween('date', [$checkIn, $checkOut])
                ->where('status', true),
        ]);

        // Filter by minimum available rooms
        $query->having('available_rooms', '>=', $rooms);

        // Additional filters
        if (!empty($filters['min_price'])) {
            $query->where('base_charge', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('base_charge', '<=', $filters['max_price']);
        }

        if (!empty($filters['amenity_ids'])) {
            $query->whereHas('room_type_amenities', function ($q) use ($filters) {
                $q->whereIn('amenity_id', $filters['amenity_ids']);
            });
        }

        return $query->get();
    }
}
