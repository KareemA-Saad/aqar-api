<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\HotelBooking\Entities\Amenity;

final class AmenityService
{
    /**
     * Get all amenities.
     */
    public function getAmenities(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Amenity::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['keyword'])) {
            $query->where('name', 'like', "%{$filters['keyword']}%");
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Get all active amenities (for dropdowns).
     */
    public function getActiveAmenities(): Collection
    {
        return Amenity::where('status', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get a single amenity.
     */
    public function getAmenity(int $id): ?Amenity
    {
        return Amenity::find($id);
    }

    /**
     * Create a new amenity.
     *
     * @param array<string, mixed> $data
     */
    public function createAmenity(array $data): Amenity
    {
        return Amenity::create([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? null,
            'status' => $data['status'] ?? true,
        ]);
    }

    /**
     * Update an amenity.
     *
     * @param array<string, mixed> $data
     */
    public function updateAmenity(Amenity $amenity, array $data): Amenity
    {
        $amenity->update(array_filter([
            'name' => $data['name'] ?? null,
            'icon' => $data['icon'] ?? null,
            'status' => $data['status'] ?? null,
        ], fn($value) => $value !== null));

        return $amenity->fresh();
    }

    /**
     * Delete an amenity.
     */
    public function deleteAmenity(Amenity $amenity): bool
    {
        return $amenity->delete();
    }

    /**
     * Toggle amenity status.
     */
    public function toggleStatus(Amenity $amenity): Amenity
    {
        $amenity->status = !$amenity->status;
        $amenity->save();

        return $amenity;
    }
}
