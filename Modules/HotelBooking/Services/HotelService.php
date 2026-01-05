<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\HotelBooking\Entities\Hotel;
use Modules\HotelBooking\Entities\HotelImage;

final class HotelService
{
    /**
     * Get paginated hotels with filters.
     *
     * @param array<string, mixed> $filters
     */
    public function getHotels(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Hotel::query()
            ->withCount(['room_type', 'review'])
            ->withAvg('review', 'ratting');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
        }

        if (!empty($filters['state_id'])) {
            $query->where('state_id', $filters['state_id']);
        }

        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('location', 'like', "%{$keyword}%")
                  ->orWhere('about', 'like', "%{$keyword}%");
            });
        }

        if (!empty($filters['has_restaurant'])) {
            $query->where('restaurant_inside', true);
        }

        if (!empty($filters['amenity_ids']) && is_array($filters['amenity_ids'])) {
            $query->whereHas('hotel_amenities', function ($q) use ($filters) {
                $q->whereIn('amenities_id', $filters['amenity_ids']);
            });
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';

        if ($sortBy === 'rating') {
            $query->orderBy('review_avg_ratting', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get a single hotel by ID with relationships.
     */
    public function getHotel(int $id): ?Hotel
    {
        return Hotel::with([
            'hotel_images',
            'hotel_amenities',
            'room_type',
            'country',
            'state',
        ])
            ->withCount(['room_type', 'review'])
            ->withAvg('review', 'ratting')
            ->find($id);
    }

    /**
     * Get a hotel by slug.
     */
    public function getHotelBySlug(string $slug): ?Hotel
    {
        return Hotel::with([
            'hotel_images',
            'hotel_amenities',
            'room_type.room_type_amenities',
            'room_type.bed_type',
            'country',
            'state',
        ])
            ->withCount(['room_type', 'review'])
            ->withAvg('review', 'ratting')
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Create a new hotel.
     *
     * @param array<string, mixed> $data
     */
    public function createHotel(array $data): Hotel
    {
        return DB::transaction(function () use ($data) {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateUniqueSlug($data['name']);
            }

            $hotel = Hotel::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'location' => $data['location'],
                'about' => $data['about'] ?? null,
                'distance' => $data['distance'] ?? null,
                'restaurant_inside' => $data['restaurant_inside'] ?? false,
                'country_id' => $data['country_id'] ?? null,
                'state_id' => $data['state_id'] ?? null,
                'status' => $data['status'] ?? true,
            ]);

            // Sync amenities
            if (!empty($data['amenity_ids'])) {
                $hotel->hotel_amenities()->sync($data['amenity_ids']);
            }

            // Add images
            if (!empty($data['images'])) {
                $this->syncHotelImages($hotel, $data['images']);
            }

            return $hotel->fresh(['hotel_images', 'hotel_amenities']);
        });
    }

    /**
     * Update an existing hotel.
     *
     * @param array<string, mixed> $data
     */
    public function updateHotel(Hotel $hotel, array $data): Hotel
    {
        return DB::transaction(function () use ($hotel, $data) {
            // Update slug if name changed and slug not explicitly provided
            if (isset($data['name']) && !isset($data['slug']) && $data['name'] !== $hotel->name) {
                $data['slug'] = $this->generateUniqueSlug($data['name'], $hotel->id);
            }

            $hotel->update(array_filter([
                'name' => $data['name'] ?? null,
                'slug' => $data['slug'] ?? null,
                'location' => $data['location'] ?? null,
                'about' => $data['about'] ?? null,
                'distance' => $data['distance'] ?? null,
                'restaurant_inside' => $data['restaurant_inside'] ?? null,
                'country_id' => $data['country_id'] ?? null,
                'state_id' => $data['state_id'] ?? null,
                'status' => $data['status'] ?? null,
            ], fn($value) => $value !== null));

            // Sync amenities if provided
            if (isset($data['amenity_ids'])) {
                $hotel->hotel_amenities()->sync($data['amenity_ids']);
            }

            // Sync images if provided
            if (isset($data['images'])) {
                $this->syncHotelImages($hotel, $data['images']);
            }

            return $hotel->fresh(['hotel_images', 'hotel_amenities']);
        });
    }

    /**
     * Delete a hotel.
     */
    public function deleteHotel(Hotel $hotel): bool
    {
        return DB::transaction(function () use ($hotel) {
            // Delete related images
            $hotel->hotel_images()->delete();

            // Detach amenities
            $hotel->hotel_amenities()->detach();

            return $hotel->delete();
        });
    }

    /**
     * Toggle hotel status.
     */
    public function toggleStatus(Hotel $hotel): Hotel
    {
        $hotel->status = !$hotel->status;
        $hotel->save();

        return $hotel;
    }

    /**
     * Sync hotel images.
     *
     * @param array<string> $images
     */
    public function syncHotelImages(Hotel $hotel, array $images): void
    {
        // Delete existing images
        $hotel->hotel_images()->delete();

        // Add new images
        foreach ($images as $index => $image) {
            HotelImage::create([
                'hotel_id' => $hotel->id,
                'image' => $image,
                'is_primary' => $index === 0,
            ]);
        }
    }

    /**
     * Get hotels with minimum price (for listings).
     */
    public function getHotelsWithMinPrice(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Hotel::query()
            ->withCount(['room_type', 'review'])
            ->withAvg('review', 'ratting')
            ->addSelect([
                'min_price' => \Modules\HotelBooking\Entities\RoomType::selectRaw('MIN(base_charge)')
                    ->whereColumn('hotel_id', 'hotels.id'),
            ]);

        // Apply same filters as getHotels
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
        }

        if (!empty($filters['state_id'])) {
            $query->where('state_id', $filters['state_id']);
        }

        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('location', 'like', "%{$keyword}%");
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * Generate a unique slug for a hotel.
     */
    private function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = Hotel::where('slug', $slug);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
