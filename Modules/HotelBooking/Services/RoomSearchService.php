<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\HotelBooking\Entities\Hotel;
use Modules\HotelBooking\Entities\RoomType;
use Modules\HotelBooking\Entities\Inventory;
use Modules\HotelBooking\Entities\RoomHold;

class RoomSearchService
{
    protected PricingService $pricingService;

    public function __construct(PricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * Search for available room types based on criteria.
     */
    public function searchAvailableRooms(array $criteria): LengthAwarePaginator
    {
        $checkIn = $criteria['check_in_date'];
        $checkOut = $criteria['check_out_date'];
        $guests = $criteria['guests'] ?? 1;
        $rooms = $criteria['rooms'] ?? 1;

        // Validate dates
        $checkInDate = Carbon::parse($checkIn);
        $checkOutDate = Carbon::parse($checkOut);
        
        if ($checkInDate->isPast() || $checkOutDate->lte($checkInDate)) {
            return new LengthAwarePaginator([], 0, $criteria['per_page'] ?? 15);
        }

        $nights = $checkInDate->diffInDays($checkOutDate);

        // Build query
        $query = RoomType::query()
            ->with(['hotel', 'images', 'amenities', 'bedTypes'])
            ->whereHas('hotel', function (Builder $q) use ($criteria) {
                $q->where('status', 1);
                
                // Filter by hotel ID
                if (!empty($criteria['hotel_id'])) {
                    $q->where('id', $criteria['hotel_id']);
                }

                // Filter by location/city
                if (!empty($criteria['city'])) {
                    $q->where('city', 'like', '%' . $criteria['city'] . '%');
                }

                // Filter by country
                if (!empty($criteria['country'])) {
                    $q->where('country', $criteria['country']);
                }

                // Filter by star rating
                if (!empty($criteria['min_stars'])) {
                    $q->where('star_rating', '>=', $criteria['min_stars']);
                }
            })
            ->where('status', 1)
            ->where('max_guests', '>=', ceil($guests / $rooms));

        // Filter by amenities
        if (!empty($criteria['amenities'])) {
            $amenityIds = is_array($criteria['amenities']) ? $criteria['amenities'] : [$criteria['amenities']];
            $query->whereHas('amenities', function (Builder $q) use ($amenityIds) {
                $q->whereIn('amenities.id', $amenityIds);
            }, '>=', count($amenityIds));
        }

        // Get all matching room types first
        $roomTypes = $query->get();

        // Filter by availability for the date range
        $availableRoomTypes = $roomTypes->filter(function ($roomType) use ($checkIn, $checkOut, $rooms, $nights) {
            return $this->checkAvailability($roomType->id, $checkIn, $checkOut, $rooms, $nights);
        });

        // Calculate prices and add to each room type
        $roomTypesWithPrices = $availableRoomTypes->map(function ($roomType) use ($checkIn, $checkOut) {
            $pricing = $this->pricingService->calculateRoomPrice(
                $roomType->id,
                $checkIn,
                $checkOut,
                1,
                ['tax_rate' => 0.15]
            );

            $roomType->calculated_price = $pricing['total'];
            $roomType->price_per_night = $pricing['nights'] > 0 
                ? round($pricing['room_subtotal'] / $pricing['nights'], 2) 
                : 0;
            $roomType->nights = $pricing['nights'];

            return $roomType;
        });

        // Apply price filters
        if (!empty($criteria['min_price'])) {
            $roomTypesWithPrices = $roomTypesWithPrices->filter(function ($roomType) use ($criteria) {
                return $roomType->price_per_night >= $criteria['min_price'];
            });
        }

        if (!empty($criteria['max_price'])) {
            $roomTypesWithPrices = $roomTypesWithPrices->filter(function ($roomType) use ($criteria) {
                return $roomType->price_per_night <= $criteria['max_price'];
            });
        }

        // Sort results
        $sortBy = $criteria['sort_by'] ?? 'price_low';
        $roomTypesWithPrices = $this->sortResults($roomTypesWithPrices, $sortBy);

        // Paginate
        $perPage = $criteria['per_page'] ?? 15;
        $page = $criteria['page'] ?? 1;
        $total = $roomTypesWithPrices->count();
        $items = $roomTypesWithPrices->forPage($page, $perPage)->values();

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    /**
     * Check if a room type is available for a date range.
     */
    public function checkAvailability(int $roomTypeId, string $checkIn, string $checkOut, int $requiredRooms = 1, ?int $nights = null): bool
    {
        $checkInDate = Carbon::parse($checkIn);
        $checkOutDate = Carbon::parse($checkOut);
        $nights = $nights ?? $checkInDate->diffInDays($checkOutDate);

        // Get inventory for all nights (excluding checkout date)
        $inventories = Inventory::where('room_type_id', $roomTypeId)
            ->whereBetween('date', [
                $checkIn,
                $checkOutDate->copy()->subDay()->format('Y-m-d')
            ])
            ->where('is_available', true)
            ->get();

        // Must have inventory for all nights
        if ($inventories->count() < $nights) {
            return false;
        }

        // Check if enough rooms available for all nights (excluding held rooms)
        foreach ($inventories as $inventory) {
            $availableRooms = $inventory->available_rooms;
            
            // Subtract active holds
            $heldRooms = $this->getHeldRoomsCount($roomTypeId, $inventory->date);
            $effectiveAvailable = $availableRooms - $heldRooms;

            if ($effectiveAvailable < $requiredRooms) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get held rooms count for a date (excludes expired holds - lazy cleanup).
     */
    protected function getHeldRoomsCount(int $roomTypeId, string $date): int
    {
        // Clean up expired holds (lazy cleanup)
        RoomHold::expired()->delete();

        return RoomHold::where('room_type_id', $roomTypeId)
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>', $date)
            ->active()
            ->sum('quantity');
    }

    /**
     * Search hotels with available rooms.
     */
    public function searchHotels(array $criteria): LengthAwarePaginator
    {
        $checkIn = $criteria['check_in_date'];
        $checkOut = $criteria['check_out_date'];
        $guests = $criteria['guests'] ?? 1;
        $rooms = $criteria['rooms'] ?? 1;

        $query = Hotel::query()
            ->with(['images', 'amenities'])
            ->where('status', 1);

        // Filter by location
        if (!empty($criteria['city'])) {
            $query->where('city', 'like', '%' . $criteria['city'] . '%');
        }

        if (!empty($criteria['country'])) {
            $query->where('country', $criteria['country']);
        }

        // Filter by star rating
        if (!empty($criteria['min_stars'])) {
            $query->where('star_rating', '>=', $criteria['min_stars']);
        }

        if (!empty($criteria['max_stars'])) {
            $query->where('star_rating', '<=', $criteria['max_stars']);
        }

        // Filter by amenities
        if (!empty($criteria['amenities'])) {
            $amenityIds = is_array($criteria['amenities']) ? $criteria['amenities'] : [$criteria['amenities']];
            $query->whereHas('amenities', function (Builder $q) use ($amenityIds) {
                $q->whereIn('amenities.id', $amenityIds);
            });
        }

        // Filter hotels that have available room types
        $query->whereHas('roomTypes', function (Builder $q) use ($checkIn, $checkOut, $guests, $rooms) {
            $q->where('status', 1)
              ->where('max_guests', '>=', ceil($guests / $rooms));
        });

        // Get hotels
        $hotels = $query->get();

        // Filter by actual availability and calculate min price
        $availableHotels = $hotels->filter(function ($hotel) use ($checkIn, $checkOut, $rooms, $guests) {
            $hasAvailable = false;
            $minPrice = null;

            foreach ($hotel->roomTypes as $roomType) {
                if ($roomType->status && $roomType->max_guests >= ceil($guests / $rooms)) {
                    if ($this->checkAvailability($roomType->id, $checkIn, $checkOut, $rooms)) {
                        $hasAvailable = true;
                        $lowestPrice = $this->pricingService->getLowestPrice($roomType->id, $checkIn, $checkOut);
                        if ($lowestPrice !== null && ($minPrice === null || $lowestPrice < $minPrice)) {
                            $minPrice = $lowestPrice;
                        }
                    }
                }
            }

            if ($hasAvailable) {
                $hotel->min_price = $minPrice;
                return true;
            }

            return false;
        });

        // Apply price filters
        if (!empty($criteria['min_price'])) {
            $availableHotels = $availableHotels->filter(fn ($h) => $h->min_price >= $criteria['min_price']);
        }

        if (!empty($criteria['max_price'])) {
            $availableHotels = $availableHotels->filter(fn ($h) => $h->min_price <= $criteria['max_price']);
        }

        // Sort
        $sortBy = $criteria['sort_by'] ?? 'price_low';
        $availableHotels = $this->sortHotels($availableHotels, $sortBy);

        // Paginate
        $perPage = $criteria['per_page'] ?? 15;
        $page = $criteria['page'] ?? 1;
        $total = $availableHotels->count();
        $items = $availableHotels->forPage($page, $perPage)->values();

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    /**
     * Get available room types for a specific hotel.
     */
    public function getAvailableRoomTypesForHotel(int $hotelId, string $checkIn, string $checkOut, int $guests = 1, int $rooms = 1): Collection
    {
        $roomTypes = RoomType::with(['images', 'amenities', 'bedTypes'])
            ->where('hotel_id', $hotelId)
            ->where('status', 1)
            ->where('max_guests', '>=', ceil($guests / $rooms))
            ->get();

        return $roomTypes->filter(function ($roomType) use ($checkIn, $checkOut, $rooms) {
            return $this->checkAvailability($roomType->id, $checkIn, $checkOut, $rooms);
        })->map(function ($roomType) use ($checkIn, $checkOut) {
            $pricing = $this->pricingService->calculateRoomPrice(
                $roomType->id,
                $checkIn,
                $checkOut,
                1,
                ['tax_rate' => 0.15]
            );

            $roomType->calculated_price = $pricing['total'];
            $roomType->price_per_night = $pricing['nights'] > 0 
                ? round($pricing['room_subtotal'] / $pricing['nights'], 2) 
                : 0;
            $roomType->daily_breakdown = $pricing['daily_breakdown'];

            return $roomType;
        })->values();
    }

    /**
     * Get instant availability calendar for a room type.
     */
    public function getAvailabilityCalendar(int $roomTypeId, string $month): array
    {
        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        $inventories = Inventory::where('room_type_id', $roomTypeId)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->get()
            ->keyBy(fn ($inv) => Carbon::parse($inv->date)->format('Y-m-d'));

        $calendar = [];
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $inventory = $inventories->get($dateString);
            $heldRooms = $this->getHeldRoomsCount($roomTypeId, $dateString);

            $availableRooms = ($inventory?->available_rooms ?? 0) - $heldRooms;
            $isAvailable = $inventory?->is_available ?? false;

            $calendar[$dateString] = [
                'date' => $dateString,
                'available' => $isAvailable && $availableRooms > 0,
                'available_rooms' => max(0, $availableRooms),
                'price' => $inventory?->price ?? null,
                'min_stay' => $inventory?->min_stay ?? 1,
                'is_past' => $date->isPast(),
            ];
        }

        return $calendar;
    }

    /**
     * Sort room type results.
     */
    protected function sortResults(Collection $results, string $sortBy): Collection
    {
        return match ($sortBy) {
            'price_low' => $results->sortBy('price_per_night'),
            'price_high' => $results->sortByDesc('price_per_night'),
            'rating' => $results->sortByDesc(fn ($r) => $r->hotel?->rating ?? 0),
            'name' => $results->sortBy('name'),
            'guests' => $results->sortByDesc('max_guests'),
            default => $results->sortBy('price_per_night'),
        };
    }

    /**
     * Sort hotel results.
     */
    protected function sortHotels(Collection $hotels, string $sortBy): Collection
    {
        return match ($sortBy) {
            'price_low' => $hotels->sortBy('min_price'),
            'price_high' => $hotels->sortByDesc('min_price'),
            'rating' => $hotels->sortByDesc('rating'),
            'stars' => $hotels->sortByDesc('star_rating'),
            'name' => $hotels->sortBy('name'),
            default => $hotels->sortBy('min_price'),
        };
    }

    /**
     * Get search suggestions based on partial input.
     */
    public function getSuggestions(string $query, string $type = 'all'): Collection
    {
        $suggestions = collect();

        if ($type === 'all' || $type === 'hotel') {
            $hotels = Hotel::where('status', 1)
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                      ->orWhere('city', 'like', "%{$query}%");
                })
                ->limit(5)
                ->get(['id', 'name', 'city', 'country']);

            foreach ($hotels as $hotel) {
                $suggestions->push([
                    'type' => 'hotel',
                    'id' => $hotel->id,
                    'text' => $hotel->name,
                    'subtext' => "{$hotel->city}, {$hotel->country}",
                ]);
            }
        }

        if ($type === 'all' || $type === 'city') {
            $cities = Hotel::where('status', 1)
                ->where('city', 'like', "%{$query}%")
                ->distinct()
                ->limit(5)
                ->pluck('city', 'country');

            foreach ($cities as $country => $city) {
                $suggestions->push([
                    'type' => 'city',
                    'text' => $city,
                    'subtext' => $country,
                ]);
            }
        }

        return $suggestions->unique('text')->take(10);
    }

    /**
     * Get popular destinations.
     */
    public function getPopularDestinations(int $limit = 10): Collection
    {
        return Hotel::where('status', 1)
            ->select('city', 'country', DB::raw('COUNT(*) as hotel_count'))
            ->groupBy('city', 'country')
            ->orderByDesc('hotel_count')
            ->limit($limit)
            ->get();
    }
}
