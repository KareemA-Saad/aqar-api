<?php

declare(strict_types=1);

namespace Modules\RealEstate\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use Modules\RealEstate\Entities\SavedSearch;
use Modules\RealEstate\Entities\Property;

/**
 * SavedSearch Service
 * 
 * Handles business logic for saved search management including:
 * - CRUD operations with validation
 * - Criteria matching against properties
 * - Match count estimation
 */
class SavedSearchService
{
    /**
     * Maximum saved searches per user.
     */
    const MAX_SEARCHES_PER_USER = 10;

    /**
     * Maximum criteria JSON size in bytes.
     */
    const MAX_CRITERIA_SIZE = 4096; // 4KB

    /**
     * Get all saved searches for a user.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserSearches(int $userId): Collection
    {
        Log::info('SavedSearchService: Fetching saved searches', ['user_id' => $userId]);

        return SavedSearch::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get a single saved search by ID.
     *
     * @param int $id
     * @param int $userId
     * @return SavedSearch|null
     */
    public function getSearch(int $id, int $userId): ?SavedSearch
    {
        return SavedSearch::where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Create a new saved search.
     *
     * @param int $userId
     * @param array $data ['name', 'criteria', 'alerts_enabled', 'alert_frequency']
     * @return SavedSearch
     * @throws \Exception
     */
    public function createSearch(int $userId, array $data): SavedSearch
    {
        Log::info('SavedSearchService: Creating saved search', [
            'user_id' => $userId,
            'name' => $data['name']
        ]);

        // Check user limit
        $existingCount = SavedSearch::where('user_id', $userId)->count();
        if ($existingCount >= self::MAX_SEARCHES_PER_USER) {
            throw new \Exception("Maximum of " . self::MAX_SEARCHES_PER_USER . " saved searches allowed per user.");
        }

        // Check duplicate name (case-insensitive)
        $duplicate = SavedSearch::where('user_id', $userId)
            ->whereRaw('LOWER(name) = ?', [strtolower($data['name'])])
            ->exists();

        if ($duplicate) {
            throw new \Exception("A saved search with this name already exists.");
        }

        // Validate criteria size
        $criteriaJson = json_encode($data['criteria']);
        if (strlen($criteriaJson) > self::MAX_CRITERIA_SIZE) {
            throw new \Exception("Search criteria too complex. Maximum size is " . self::MAX_CRITERIA_SIZE . " bytes.");
        }

        // Create the saved search
        $search = SavedSearch::create([
            'user_id' => $userId,
            'name' => $data['name'],
            'criteria' => $data['criteria'],
            'alerts_enabled' => $data['alerts_enabled'] ?? true,
            'alert_frequency' => $data['alert_frequency'] ?? 'daily',
        ]);

        Log::info('SavedSearchService: Created saved search', ['id' => $search->id]);

        return $search;
    }

    /**
     * Update an existing saved search.
     *
     * @param int $id
     * @param int $userId
     * @param array $data ['name', 'criteria', 'alerts_enabled', 'alert_frequency']
     * @return SavedSearch|null
     * @throws \Exception
     */
    public function updateSearch(int $id, int $userId, array $data): ?SavedSearch
    {
        Log::info('SavedSearchService: Updating saved search', ['id' => $id, 'user_id' => $userId]);

        $search = $this->getSearch($id, $userId);
        if (!$search) {
            return null;
        }

        // Check duplicate name if name is being changed (case-insensitive)
        if (isset($data['name']) && strtolower($data['name']) !== strtolower($search->name)) {
            $duplicate = SavedSearch::where('user_id', $userId)
                ->where('id', '!=', $id)
                ->whereRaw('LOWER(name) = ?', [strtolower($data['name'])])
                ->exists();

            if ($duplicate) {
                throw new \Exception("A saved search with this name already exists.");
            }
        }

        // Validate criteria size if criteria is being updated
        if (isset($data['criteria'])) {
            $criteriaJson = json_encode($data['criteria']);
            if (strlen($criteriaJson) > self::MAX_CRITERIA_SIZE) {
                throw new \Exception("Search criteria too complex. Maximum size is " . self::MAX_CRITERIA_SIZE . " bytes.");
            }
        }

        // Update allowed fields
        $search->update(array_filter($data, function ($key) {
            return in_array($key, ['name', 'criteria', 'alerts_enabled', 'alert_frequency']);
        }, ARRAY_FILTER_USE_KEY));

        Log::info('SavedSearchService: Updated saved search', ['id' => $id]);

        return $search->fresh();
    }

    /**
     * Delete a saved search.
     *
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function deleteSearch(int $id, int $userId): bool
    {
        Log::info('SavedSearchService: Deleting saved search', ['id' => $id, 'user_id' => $userId]);

        $search = $this->getSearch($id, $userId);
        if (!$search) {
            return false;
        }

        $deleted = $search->delete();

        Log::info('SavedSearchService: Deleted saved search', ['id' => $id, 'deleted' => $deleted]);

        return $deleted;
    }

    /**
     * Toggle alerts for a saved search.
     *
     * @param int $id
     * @param int $userId
     * @param bool $enabled
     * @return SavedSearch|null
     */
    public function toggleAlerts(int $id, int $userId, bool $enabled): ?SavedSearch
    {
        Log::info('SavedSearchService: Toggling alerts', [
            'id' => $id,
            'user_id' => $userId,
            'enabled' => $enabled
        ]);

        $search = $this->getSearch($id, $userId);
        if (!$search) {
            return null;
        }

        $search->update(['alerts_enabled' => $enabled]);

        Log::info('SavedSearchService: Alerts toggled', ['id' => $id, 'alerts_enabled' => $enabled]);

        return $search->fresh();
    }

    /**
     * Find properties matching saved search criteria.
     *
     * @param SavedSearch $search
     * @param int|null $limit Limit results (default: no limit)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findMatchingProperties(SavedSearch $search, ?int $limit = null): Collection
    {
        Log::info('SavedSearchService: Finding matching properties', [
            'search_id' => $search->id,
            'criteria' => $search->criteria
        ]);

        $query = Property::query()
            ->with(['compound.area', 'compound.developer', 'propertyType', 'primaryImage'])
            ->active(); // Only published and available properties

        $criteria = $search->criteria;

        // Apply filters based on criteria
        if (isset($criteria['area_id'])) {
            $query->whereHas('compound', function ($q) use ($criteria) {
                $q->where('area_id', $criteria['area_id']);
            });
        }

        if (isset($criteria['property_type_id'])) {
            $query->where('property_type_id', $criteria['property_type_id']);
        }

        if (isset($criteria['listing_type'])) {
            $query->where('listing_type', $criteria['listing_type']);
        }

        if (isset($criteria['price_min'])) {
            $query->where('price', '>=', $criteria['price_min']);
        }

        if (isset($criteria['price_max'])) {
            $query->where('price', '<=', $criteria['price_max']);
        }

        if (isset($criteria['bedrooms'])) {
            $query->where('bedrooms', $criteria['bedrooms']);
        }

        if (isset($criteria['bedrooms_min'])) {
            $query->where('bedrooms', '>=', $criteria['bedrooms_min']);
        }

        if (isset($criteria['bedrooms_max'])) {
            $query->where('bedrooms', '<=', $criteria['bedrooms_max']);
        }

        if (isset($criteria['bathrooms'])) {
            $query->where('bathrooms', $criteria['bathrooms']);
        }

        if (isset($criteria['bathrooms_min'])) {
            $query->where('bathrooms', '>=', $criteria['bathrooms_min']);
        }

        if (isset($criteria['area_min'])) {
            $query->where('area', '>=', $criteria['area_min']);
        }

        if (isset($criteria['area_max'])) {
            $query->where('area', '<=', $criteria['area_max']);
        }

        if (isset($criteria['finishing'])) {
            $query->where('finishing', $criteria['finishing']);
        }

        if (isset($criteria['payment_option'])) {
            $query->where('payment_option', $criteria['payment_option']);
        }

        if (isset($criteria['is_featured']) && $criteria['is_featured']) {
            $query->where('is_featured', true);
        }

        // Order by newest first
        $query->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        $properties = $query->get();

        Log::info('SavedSearchService: Found matching properties', [
            'search_id' => $search->id,
            'match_count' => $properties->count()
        ]);

        return $properties;
    }

    /**
     * Get count of properties matching search criteria.
     *
     * @param array $criteria
     * @return int
     */
    public function getMatchCount(array $criteria): int
    {
        Log::info('SavedSearchService: Calculating match count', ['criteria' => $criteria]);

        $query = Property::query()->active();

        // Apply same filters as findMatchingProperties
        if (isset($criteria['area_id'])) {
            $query->whereHas('compound', function ($q) use ($criteria) {
                $q->where('area_id', $criteria['area_id']);
            });
        }

        if (isset($criteria['property_type_id'])) {
            $query->where('property_type_id', $criteria['property_type_id']);
        }

        if (isset($criteria['listing_type'])) {
            $query->where('listing_type', $criteria['listing_type']);
        }

        if (isset($criteria['price_min'])) {
            $query->where('price', '>=', $criteria['price_min']);
        }

        if (isset($criteria['price_max'])) {
            $query->where('price', '<=', $criteria['price_max']);
        }

        if (isset($criteria['bedrooms'])) {
            $query->where('bedrooms', $criteria['bedrooms']);
        }

        if (isset($criteria['bedrooms_min'])) {
            $query->where('bedrooms', '>=', $criteria['bedrooms_min']);
        }

        if (isset($criteria['bedrooms_max'])) {
            $query->where('bedrooms', '<=', $criteria['bedrooms_max']);
        }

        if (isset($criteria['bathrooms'])) {
            $query->where('bathrooms', $criteria['bathrooms']);
        }

        if (isset($criteria['bathrooms_min'])) {
            $query->where('bathrooms', '>=', $criteria['bathrooms_min']);
        }

        if (isset($criteria['area_min'])) {
            $query->where('area', '>=', $criteria['area_min']);
        }

        if (isset($criteria['area_max'])) {
            $query->where('area', '<=', $criteria['area_max']);
        }

        if (isset($criteria['finishing'])) {
            $query->where('finishing', $criteria['finishing']);
        }

        if (isset($criteria['payment_option'])) {
            $query->where('payment_option', $criteria['payment_option']);
        }

        if (isset($criteria['is_featured']) && $criteria['is_featured']) {
            $query->where('is_featured', true);
        }

        $count = $query->count();

        Log::info('SavedSearchService: Match count calculated', ['count' => $count]);

        return $count;
    }

    /**
     * Find new properties since last alert for a saved search.
     *
     * @param SavedSearch $search
     * @param int|null $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findNewMatches(SavedSearch $search, ?int $limit = null): Collection
    {
        Log::info('SavedSearchService: Finding new matches since last alert', [
            'search_id' => $search->id,
            'last_alerted_at' => $search->last_alerted_at
        ]);

        $query = Property::query()
            ->with(['compound.area', 'compound.developer', 'propertyType', 'primaryImage'])
            ->active();

        // Only properties created after last alert
        if ($search->last_alerted_at) {
            $query->where('created_at', '>', $search->last_alerted_at);
        }

        $criteria = $search->criteria;

        // Apply same filters as findMatchingProperties
        if (isset($criteria['area_id'])) {
            $query->whereHas('compound', function ($q) use ($criteria) {
                $q->where('area_id', $criteria['area_id']);
            });
        }

        if (isset($criteria['property_type_id'])) {
            $query->where('property_type_id', $criteria['property_type_id']);
        }

        if (isset($criteria['listing_type'])) {
            $query->where('listing_type', $criteria['listing_type']);
        }

        if (isset($criteria['price_min'])) {
            $query->where('price', '>=', $criteria['price_min']);
        }

        if (isset($criteria['price_max'])) {
            $query->where('price', '<=', $criteria['price_max']);
        }

        if (isset($criteria['bedrooms'])) {
            $query->where('bedrooms', $criteria['bedrooms']);
        }

        if (isset($criteria['bedrooms_min'])) {
            $query->where('bedrooms', '>=', $criteria['bedrooms_min']);
        }

        if (isset($criteria['bedrooms_max'])) {
            $query->where('bedrooms', '<=', $criteria['bedrooms_max']);
        }

        if (isset($criteria['bathrooms'])) {
            $query->where('bathrooms', $criteria['bathrooms']);
        }

        if (isset($criteria['bathrooms_min'])) {
            $query->where('bathrooms', '>=', $criteria['bathrooms_min']);
        }

        if (isset($criteria['area_min'])) {
            $query->where('area', '>=', $criteria['area_min']);
        }

        if (isset($criteria['area_max'])) {
            $query->where('area', '<=', $criteria['area_max']);
        }

        if (isset($criteria['finishing'])) {
            $query->where('finishing', $criteria['finishing']);
        }

        if (isset($criteria['payment_option'])) {
            $query->where('payment_option', $criteria['payment_option']);
        }

        if (isset($criteria['is_featured']) && $criteria['is_featured']) {
            $query->where('is_featured', true);
        }

        $query->orderBy('created_at', 'desc');

        if ($limit) {
            $query->limit($limit);
        }

        $properties = $query->get();

        Log::info('SavedSearchService: Found new matches', [
            'search_id' => $search->id,
            'new_match_count' => $properties->count()
        ]);

        return $properties;
    }

    /**
     * Get saved searches due for alert processing.
     *
     * @param string $frequency 'daily' or 'weekly'
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSearchesDueForAlert(string $frequency): Collection
    {
        Log::info('SavedSearchService: Fetching searches due for alert', ['frequency' => $frequency]);

        return SavedSearch::activeAlerts()
            ->frequency($frequency)
            ->where(function ($query) use ($frequency) {
                // Never alerted OR last alert was more than threshold ago
                $query->whereNull('last_alerted_at')
                    ->orWhere(function ($q) use ($frequency) {
                        if ($frequency === 'daily') {
                            $q->where('last_alerted_at', '<=', now()->subHours(24));
                        } else { // weekly
                            $q->where('last_alerted_at', '<=', now()->subDays(7));
                        }
                    });
            })
            ->with('user')
            ->get();
    }
}
