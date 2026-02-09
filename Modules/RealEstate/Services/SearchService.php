<?php

declare(strict_types=1);

namespace Modules\RealEstate\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Modules\RealEstate\Entities\Area;
use Modules\RealEstate\Entities\Compound;
use Modules\RealEstate\Entities\Property;

/**
 * Search Service
 * 
 * Handles advanced search functionality for real estate listings including
 * full-text search, faceted search, autocomplete, and search suggestions.
 */
class SearchService
{
    /**
     * Cache TTL for search results in seconds.
     */
    protected int $cacheTtl;

    public function __construct()
    {
        $this->cacheTtl = config('realestate.cache.search_ttl', 300);
    }

    /**
     * Perform comprehensive property search.
     */
    public function searchProperties(array $params): LengthAwarePaginator
    {
        $query = Property::query()
            ->with(['compound.area', 'compound.developer', 'compound', 'propertyType', 'primaryImage'])
            ->active();

        // Full-text search on title and description
        if (!empty($params['q'])) {
            $searchTerm = $params['q'];
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('title', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhereJsonContains('title', $searchTerm)
                    ->orWhereJsonContains('description', $searchTerm);
            });
        }

        // Location filters - Properties don't have direct area_id, must join through compound
        if (!empty($params['area_id'])) {
            $areaIds = $this->getAreaIdsWithChildren($params['area_id']);
            $query->whereHas('compound', function ($q) use ($areaIds) {
                $q->whereIn('area_id', $areaIds);
            });
        }

        if (!empty($params['compound_id'])) {
            $query->where('compound_id', $params['compound_id']);
        }

        // Property type filter
        if (!empty($params['property_type_id'])) {
            $query->where('property_type_id', $params['property_type_id']);
        }

        // Developer filter - Properties don't have developer_id, must join through compound
        if (!empty($params['developer_id'])) {
            $query->whereHas('compound', function ($q) use ($params) {
                $q->where('developer_id', $params['developer_id']);
            });
        }

        // Listing type filter (sale/rent) - mapped to 'purpose' in API via accessor
        if (!empty($params['purpose'])) {
            $query->where('listing_type', $params['purpose']);
        }

        // Price range
        if (!empty($params['min_price'])) {
            $query->where('price', '>=', $params['min_price']);
        }
        if (!empty($params['max_price'])) {
            $query->where('price', '<=', $params['max_price']);
        }

        // Area range (property size)
        if (!empty($params['min_area'])) {
            $query->where('area', '>=', $params['min_area']);
        }
        if (!empty($params['max_area'])) {
            $query->where('area', '<=', $params['max_area']);
        }

        // Room filters
        if (!empty($params['bedrooms'])) {
            if (is_array($params['bedrooms'])) {
                $query->whereIn('bedrooms', $params['bedrooms']);
            } else {
                $query->where('bedrooms', '>=', $params['bedrooms']);
            }
        }

        if (!empty($params['bathrooms'])) {
            $query->where('bathrooms', '>=', $params['bathrooms']);
        }

        // Finishing type
        if (!empty($params['finishing'])) {
            $query->where('finishing', $params['finishing']);
        }

        // Delivery year filter - database has delivery_date, so extract year
        if (!empty($params['delivery_year'])) {
            $query->whereYear('delivery_date', $params['delivery_year']);
        }

        if (!empty($params['ready_to_move'])) {
            $query->where('delivery_year', '<=', now()->year);
        }

        // Amenities filter
        if (!empty($params['amenities'])) {
            $amenityIds = is_array($params['amenities']) ? $params['amenities'] : [$params['amenities']];
            $query->whereHas('amenities', function ($q) use ($amenityIds) {
                $q->whereIn('re_amenities.id', $amenityIds);
            }, '>=', count($amenityIds));
        }

        // Featured only
        if (!empty($params['featured'])) {
            $query->featured();
        }

        // Sorting
        $sortField = $params['sort'] ?? 'created_at';
        $sortDirection = $params['direction'] ?? 'desc';
        
        $allowedSorts = ['created_at', 'price', 'area', 'bedrooms', 'views_count'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        return $query->paginate($params['per_page'] ?? 15);
    }

    /**
     * Perform compound search.
     */
    public function searchCompounds(array $params): LengthAwarePaginator
    {
        $query = Compound::query()
            ->with(['area', 'developer', 'primaryImage'])
            ->withCount('properties')
            ->active();

        // Full-text search
        if (!empty($params['q'])) {
            $searchTerm = $params['q'];
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('description', 'like', "%{$searchTerm}%")
                    ->orWhereJsonContains('name', $searchTerm);
            });
        }

        // Location filter
        if (!empty($params['area_id'])) {
            $areaIds = $this->getAreaIdsWithChildren($params['area_id']);
            $query->whereIn('area_id', $areaIds);
        }

        // Developer filter
        if (!empty($params['developer_id'])) {
            $query->where('developer_id', $params['developer_id']);
        }

        // Price range
        if (!empty($params['min_price'])) {
            $query->where('max_price', '>=', $params['min_price']);
        }
        if (!empty($params['max_price'])) {
            $query->where('min_price', '<=', $params['max_price']);
        }

        // Featured only
        if (!empty($params['featured'])) {
            $query->featured();
        }

        // Sorting
        $sortField = $params['sort'] ?? 'created_at';
        $sortDirection = $params['direction'] ?? 'desc';
        
        $allowedSorts = ['created_at', 'name', 'min_price', 'total_units'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        return $query->paginate($params['per_page'] ?? 15);
    }

    /**
     * Get autocomplete suggestions.
     */
    public function getAutocompleteSuggestions(string $query, int $limit = 10): array
    {
        $callback = function () use ($query, $limit) {
            $suggestions = [];

            // Properties
            $properties = Property::where('title', 'like', "%{$query}%")
                ->orWhereJsonContains('title', $query)
                ->active()
                ->limit($limit)
                ->get(['id', 'title', 'slug']);

            foreach ($properties as $property) {
                $suggestions[] = [
                    'type' => 'property',
                    'id' => $property->id,
                    'title' => $property->title,
                    'slug' => $property->slug,
                    'url' => "/properties/{$property->id}-{$property->slug}",
                ];
            }

            // Compounds
            $compounds = Compound::where('name', 'like', "%{$query}%")
                ->orWhereJsonContains('name', $query)
                ->active()
                ->limit($limit)
                ->get(['id', 'name', 'slug']);

            foreach ($compounds as $compound) {
                $suggestions[] = [
                    'type' => 'compound',
                    'id' => $compound->id,
                    'title' => $compound->name,
                    'slug' => $compound->slug,
                    'url' => "/compounds/{$compound->id}-{$compound->slug}",
                ];
            }

            // Areas
            $areas = Area::where('name', 'like', "%{$query}%")
                ->orWhereJsonContains('name', $query)
                ->active()
                ->limit($limit)
                ->get(['id', 'name', 'slug']);

            foreach ($areas as $area) {
                $suggestions[] = [
                    'type' => 'area',
                    'id' => $area->id,
                    'title' => $area->name,
                    'slug' => $area->slug,
                    'url' => "/areas/{$area->id}-{$area->slug}",
                ];
            }

            // Sort by relevance (exact match first)
            usort($suggestions, function ($a, $b) use ($query) {
                $aStarts = stripos($a['title'], $query) === 0;
                $bStarts = stripos($b['title'], $query) === 0;
                
                if ($aStarts && !$bStarts) return -1;
                if (!$aStarts && $bStarts) return 1;
                
                return strcasecmp($a['title'], $b['title']);
            });

            return array_slice($suggestions, 0, $limit);
        };

        if ($this->cacheSupportsTagging()) {
            $cacheKey = 'autocomplete_' . md5($query . '_' . $limit);
            return Cache::remember($cacheKey, 60, $callback);
        }

        return $callback();
    }

    /**
     * Get search facets (filters with counts).
     */
    public function getSearchFacets(array $params = []): array
    {
        $callback = function () use ($params) {
            $baseQuery = Property::active();

            // Apply base filters if any
            if (!empty($params['area_id'])) {
                $areaIds = $this->getAreaIdsWithChildren($params['area_id']);
                $baseQuery->whereIn('area_id', $areaIds);
            }

            return [
                'property_types' => $this->getPropertyTypeFacets($baseQuery->clone()),
                'areas' => $this->getAreaFacets(),
                'bedrooms' => $this->getBedroomFacets($baseQuery->clone()),
                'price_ranges' => $this->getPriceRangeFacets($baseQuery->clone()),
                'finishing' => $this->getFinishingFacets($baseQuery->clone()),
                'purposes' => $this->getPurposeFacets($baseQuery->clone()),
            ];
        };

        if ($this->cacheSupportsTagging()) {
            $cacheKey = 'search_facets_' . md5(json_encode($params));
            return Cache::remember($cacheKey, $this->cacheTtl, $callback);
        }

        return $callback();
    }

    /**
     * Get property type facets.
     */
    protected function getPropertyTypeFacets(Builder $query): array
    {
        return $query->selectRaw('property_type_id, COUNT(*) as count')
            ->groupBy('property_type_id')
            ->with('propertyType:id,name')
            ->get()
            ->map(fn ($item) => [
                'id' => $item->property_type_id,
                'name' => $item->propertyType?->name,
                'count' => $item->count,
            ])
            ->filter(fn ($item) => $item['name'] !== null)
            ->values()
            ->toArray();
    }

    /**
     * Get area facets.
     */
    protected function getAreaFacets(): array
    {
        return Area::whereNull('parent_id')
            ->active()
            ->withCount(['properties' => fn ($q) => $q->active()])
            ->having('properties_count', '>', 0)
            ->orderByDesc('properties_count')
            ->limit(20)
            ->get()
            ->map(fn ($area) => [
                'id' => $area->id,
                'name' => $area->name,
                'count' => $area->properties_count,
            ])
            ->toArray();
    }

    /**
     * Get bedroom facets.
     */
    protected function getBedroomFacets(Builder $query): array
    {
        return $query->selectRaw('bedrooms, COUNT(*) as count')
            ->whereNotNull('bedrooms')
            ->groupBy('bedrooms')
            ->orderBy('bedrooms')
            ->get()
            ->map(fn ($item) => [
                'value' => $item->bedrooms,
                'label' => $item->bedrooms . ' ' . ($item->bedrooms === 1 ? 'Bedroom' : 'Bedrooms'),
                'count' => $item->count,
            ])
            ->toArray();
    }

    /**
     * Get price range facets.
     */
    protected function getPriceRangeFacets(Builder $query): array
    {
        $ranges = [
            ['min' => 0, 'max' => 1000000, 'label' => 'Under 1M'],
            ['min' => 1000000, 'max' => 2000000, 'label' => '1M - 2M'],
            ['min' => 2000000, 'max' => 5000000, 'label' => '2M - 5M'],
            ['min' => 5000000, 'max' => 10000000, 'label' => '5M - 10M'],
            ['min' => 10000000, 'max' => null, 'label' => '10M+'],
        ];

        return collect($ranges)->map(function ($range) use ($query) {
            $rangeQuery = $query->clone();
            $rangeQuery->where('price', '>=', $range['min']);
            
            if ($range['max']) {
                $rangeQuery->where('price', '<', $range['max']);
            }

            return [
                'min' => $range['min'],
                'max' => $range['max'],
                'label' => $range['label'],
                'count' => $rangeQuery->count(),
            ];
        })->filter(fn ($range) => $range['count'] > 0)->values()->toArray();
    }

    /**
     * Get finishing facets.
     */
    protected function getFinishingFacets(Builder $query): array
    {
        return $query->selectRaw('finishing, COUNT(*) as count')
            ->whereNotNull('finishing')
            ->groupBy('finishing')
            ->get()
            ->map(fn ($item) => [
                'value' => $item->finishing,
                'label' => ucfirst($item->finishing),
                'count' => $item->count,
            ])
            ->toArray();
    }

    /**
     * Get purpose facets (sale/rent).
     */
    protected function getPurposeFacets(Builder $query): array
    {
        return $query->selectRaw('listing_type, COUNT(*) as count')
            ->groupBy('listing_type')
            ->get()
            ->map(fn ($item) => [
                'value' => $item->listing_type,
                'label' => ucfirst($item->listing_type),
                'count' => $item->count,
            ])
            ->toArray();
    }

    /**
     * Get area IDs including all children.
     */
    protected function getAreaIdsWithChildren(int $areaId): array
    {
        $callback = function () use ($areaId) {
            $ids = [$areaId];
            $children = Area::where('parent_id', $areaId)->pluck('id');

            foreach ($children as $childId) {
                $ids = array_merge($ids, $this->getAreaIdsWithChildrenDirect($childId));
            }

            return array_unique($ids);
        };

        if ($this->cacheSupportsTagging()) {
            return Cache::remember("area_ids_with_children_{$areaId}", $this->cacheTtl, $callback);
        }

        return $callback();
    }

    /**
     * Get area IDs including all children (without cache for recursive calls).
     */
    protected function getAreaIdsWithChildrenDirect(int $areaId): array
    {
        $ids = [$areaId];
        $children = Area::where('parent_id', $areaId)->pluck('id');

        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getAreaIdsWithChildrenDirect($childId));
        }

        return array_unique($ids);
    }

    /**
     * Get popular searches (cached).
     */
    public function getPopularSearches(int $limit = 10): array
    {
        $callback = function () use ($limit) {
            // This could be populated from a search_logs table in a real implementation
            return [
                ['term' => 'apartment new cairo', 'count' => 150],
                ['term' => 'villa sheikh zayed', 'count' => 120],
                ['term' => 'studio 6th october', 'count' => 100],
                ['term' => 'duplex maadi', 'count' => 80],
            ];
        };

        if ($this->cacheSupportsTagging()) {
            return Cache::remember('popular_searches', 3600, $callback);
        }

        return $callback();
    }

    /**
     * Get nearby properties based on coordinates.
     *
     * @param float $latitude Center latitude
     * @param float $longitude Center longitude
     * @param int $radius Radius in kilometers
     * @param int $limit Maximum results
     * @return array Properties with distance
     */
    public function getNearbyProperties(float $latitude, float $longitude, int $radius = 5, int $limit = 10): array
    {
        // Haversine formula for distance calculation
        $properties = Property::selectRaw("
                *,
                (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance
            ", [$latitude, $longitude, $latitude])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->having('distance', '<', $radius)
            ->orderBy('distance')
            ->limit($limit)
            ->active()
            ->with(['compound.area', 'propertyType', 'primaryImage'])
            ->get();

        return $properties->toArray();
    }

    /**
     * Search properties within bounding box (map viewport).
     *
     * @param float $northEastLat Northeast corner latitude
     * @param float $northEastLng Northeast corner longitude
     * @param float $southWestLat Southwest corner latitude
     * @param float $southWestLng Southwest corner longitude
     * @param array $filters Additional filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchPropertiesInBounds(
        float $northEastLat,
        float $northEastLng,
        float $southWestLat,
        float $southWestLng,
        array $filters = []
    ) {
        $query = Property::query()
            ->whereBetween('latitude', [$southWestLat, $northEastLat])
            ->whereBetween('longitude', [$southWestLng, $northEastLng])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->active();

        // Apply additional filters
        if (!empty($filters['property_type_id'])) {
            $query->where('property_type_id', $filters['property_type_id']);
        }

        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (!empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (!empty($filters['bedrooms'])) {
            $query->where('bedrooms', '>=', $filters['bedrooms']);
        }

        if (!empty($filters['purpose'])) {
            $query->where('listing_type', $filters['purpose']);
        }

        return $query->with(['compound.area', 'compound.developer', 'propertyType', 'primaryImage', 'compound'])
            ->limit(500) // Limit for performance
            ->get();
    }

    /**
     * Get properties clustered for map display.
     *
     * @param int $zoom Map zoom level
     * @param float|null $northEastLat
     * @param float|null $northEastLng
     * @param float|null $southWestLat
     * @param float|null $southWestLng
     * @return array Cluster data with property counts
     */
    public function getPropertyClusters(
        int $zoom,
        ?float $northEastLat = null,
        ?float $northEastLng = null,
        ?float $southWestLat = null,
        ?float $southWestLng = null
    ): array {
        // Determine clustering precision based on zoom level
        $precision = match (true) {
            $zoom >= 15 => 4, // Individual properties
            $zoom >= 12 => 3, // Small clusters
            $zoom >= 9 => 2,  // Medium clusters
            default => 1      // Large clusters
        };

        $query = Property::selectRaw("
                ROUND(latitude, ?) as cluster_lat,
                ROUND(longitude, ?) as cluster_lng,
                COUNT(*) as property_count,
                MIN(price) as min_price,
                MAX(price) as max_price,
                AVG(price) as avg_price
            ", [$precision, $precision])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->active();

        // Apply bounds if provided
        if ($northEastLat && $northEastLng && $southWestLat && $southWestLng) {
            $query->whereBetween('latitude', [$southWestLat, $northEastLat])
                ->whereBetween('longitude', [$southWestLng, $northEastLng]);
        }

        return $query->groupBy('cluster_lat', 'cluster_lng')
            ->having('property_count', '>', 0)
            ->get()
            ->toArray();
    }

    /**
     * Calculate distance between two coordinates in kilometers.
     *
     * @param float $lat1 First latitude
     * @param float $lon1 First longitude
     * @param float $lat2 Second latitude
     * @param float $lon2 Second longitude
     * @return float Distance in kilometers
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth radius in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get properties near an area center.
     *
     * @param int $areaId
     * @param int $radius Radius in kilometers
     * @param int $limit
     * @return array
     */
    public function getPropertiesNearArea(int $areaId, int $radius = 10, int $limit = 20): array
    {
        $area = Area::find($areaId);

        if (!$area || !$area->latitude || !$area->longitude) {
            return [];
        }

        return $this->getNearbyProperties($area->latitude, $area->longitude, $radius, $limit);
    }

    /**
     * Check if the cache store supports tagging.
     */
    protected function cacheSupportsTagging(): bool
    {
        try {
            $driver = config('cache.default');
            return in_array($driver, ['redis', 'memcached', 'array']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Perform global search across all entities (Properties, Compounds, Areas, Developers).
     * 
     * @param array $params Search parameters including:
     *   - q (string, required): Search query term (min 2 chars)
     *   - purpose (string, optional): Listing type (sale/rent) - applies to properties only
     *   - property_type_id (int, optional): Property type filter
     *   - min_price/max_price (numeric, optional): Price range filter
     *   - bedrooms/bathrooms (int, optional): Room filters
     *   - area_id (int, optional): Location filter
     *   - developer_id (int, optional): Developer filter
     *   - amenities (string/array, optional): Amenity filters
     *   - finishing (string, optional): Finishing level
     *   - entity_type (string, optional): Filter by entity type (all|properties|compounds|areas|developers)
     *   - per_page (int, optional): Results per page (default: 15)
     *   - page (int, optional): Page number (default: 1)
     *   - sort (string, optional): Sort field (relevance|created_at|price)
     * 
     * @return array Array containing:
     *   - data: Collection of unified search results with relevance scores
     *   - meta: Pagination and statistics metadata
     *   - filters_applied: Applied filters for transparency
     */
    public function globalSearch(array $params): array
    {
        // Validate required parameters
        if (empty($params['q']) || strlen($params['q']) < 2) {
            throw new \InvalidArgumentException('Search query (q) is required and must be at least 2 characters.');
        }

        // Generate cache key from query parameters
        $cacheKey = $this->generateGlobalSearchCacheKey($params);

        // Check cache if supported
        if ($this->cacheSupportsTagging()) {
            $cachedResult = Cache::get($cacheKey);
            if ($cachedResult !== null) {
                return $cachedResult;
            }
        }

        // Determine which entity types to search
        $entityType = $params['entity_type'] ?? 'all';
        $searchProperties = in_array($entityType, ['all', 'properties']);
        $searchCompounds = in_array($entityType, ['all', 'compounds']);
        $searchAreas = in_array($entityType, ['all', 'areas']);
        $searchDevelopers = in_array($entityType, ['all', 'developers']);

        // Execute searches for enabled entity types
        $results = [];

        if ($searchProperties) {
            $results['properties'] = $this->searchPropertiesForGlobal($params);
        }

        if ($searchCompounds) {
            $results['compounds'] = $this->searchCompoundsForGlobal($params);
        }

        if ($searchAreas) {
            $results['areas'] = $this->searchAreasForGlobal($params);
        }

        if ($searchDevelopers) {
            $results['developers'] = $this->searchDevelopersForGlobal($params);
        }

        // Merge all results into a single collection
        $mergedResults = collect([]);
        $counts = [
            'properties' => 0,
            'compounds' => 0,
            'areas' => 0,
            'developers' => 0,
        ];

        foreach ($results as $entityType => $collection) {
            $mergedResults = $mergedResults->concat($collection);
            $counts[$entityType] = $collection->count();
        }

        // Total count before pagination
        $totalCount = $mergedResults->count();

        // Paginate results
        $perPage = $params['per_page'] ?? 15;
        $page = $params['page'] ?? 1;
        $lastPage = $totalCount > 0 ? (int) ceil($totalCount / $perPage) : 1;
        
        // Slice results for current page
        $offset = ($page - 1) * $perPage;
        $paginatedResults = $mergedResults->slice($offset, $perPage)->values();

        // Prepare response structure
        $response = [
            'data' => $paginatedResults,
            'meta' => [
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
                'counts_by_type' => $counts,
            ],
            'filters_applied' => array_filter([
                'q' => $params['q'] ?? null,
                'purpose' => $params['purpose'] ?? null,
                'property_type_id' => $params['property_type_id'] ?? null,
                'min_price' => $params['min_price'] ?? null,
                'max_price' => $params['max_price'] ?? null,
                'bedrooms' => $params['bedrooms'] ?? null,
                'bathrooms' => $params['bathrooms'] ?? null,
                'area_id' => $params['area_id'] ?? null,
                'developer_id' => $params['developer_id'] ?? null,
                'amenities' => $params['amenities'] ?? null,
                'finishing' => $params['finishing'] ?? null,
                'entity_type' => $entityType,
            ]),
        ];

        // Cache the result for 5 minutes (300 seconds)
        if ($this->cacheSupportsTagging()) {
            Cache::put($cacheKey, $response, 300);
        }

        return $response;
    }

    /**
     * Search properties for global search with relevance data.
     * 
     * @param array $params Search parameters
     * @return \Illuminate\Database\Eloquent\Collection Collection of properties with type indicator
     */
    protected function searchPropertiesForGlobal(array $params): \Illuminate\Database\Eloquent\Collection
    {
        $searchTerm = $params['q'];
        
        $query = Property::query()
            ->with(['compound.area', 'compound.developer', 'propertyType', 'primaryImage'])
            ->active();

        // Full-text search on title and description
        $query->where(function (Builder $q) use ($searchTerm) {
            $q->where('title', 'like', "%{$searchTerm}%")
                ->orWhere('description', 'like', "%{$searchTerm}%")
                ->orWhereJsonContains('title', $searchTerm)
                ->orWhereJsonContains('description', $searchTerm);
        });

        // Apply purpose filter (sale/rent)
        if (!empty($params['purpose'])) {
            $query->where('listing_type', $params['purpose']);
        }

        // Property type filter
        if (!empty($params['property_type_id'])) {
            $query->where('property_type_id', $params['property_type_id']);
        }

        // Price range
        if (!empty($params['min_price'])) {
            $query->where('price', '>=', $params['min_price']);
        }
        if (!empty($params['max_price'])) {
            $query->where('price', '<=', $params['max_price']);
        }

        // Bedrooms filter
        if (!empty($params['bedrooms'])) {
            if (is_array($params['bedrooms'])) {
                $query->whereIn('bedrooms', $params['bedrooms']);
            } else {
                $query->where('bedrooms', '>=', $params['bedrooms']);
            }
        }

        // Bathrooms filter
        if (!empty($params['bathrooms'])) {
            $query->where('bathrooms', '>=', $params['bathrooms']);
        }

        // Area filter - Properties don't have direct area_id, must join through compound
        if (!empty($params['area_id'])) {
            $areaIds = $this->getAreaIdsWithChildren($params['area_id']);
            $query->whereHas('compound', function ($q) use ($areaIds) {
                $q->whereIn('area_id', $areaIds);
            });
        }

        // Developer filter - Properties don't have developer_id, must join through compound
        if (!empty($params['developer_id'])) {
            $query->whereHas('compound', function ($q) use ($params) {
                $q->where('developer_id', $params['developer_id']);
            });
        }

        // Amenities filter
        if (!empty($params['amenities'])) {
            $amenityIds = is_array($params['amenities']) ? $params['amenities'] : explode(',', $params['amenities']);
            $query->whereHas('amenities', function ($q) use ($amenityIds) {
                $q->whereIn('re_amenities.id', $amenityIds);
            });
        }

        // Finishing filter
        if (!empty($params['finishing'])) {
            $query->where('finishing', $params['finishing']);
        }

        // Limit results to 50 for performance
        $properties = $query->limit(50)->get();

        // Add type indicator and relevance score placeholder to each result
        return $properties->map(function ($property) use ($searchTerm) {
            $property->entity_type = 'property';
            $property->search_term = $searchTerm;
            // Relevance score will be calculated later in Phase 2.4
            $property->relevance_score = 0;
            return $property;
        });
    }

    /**
     * Search compounds for global search with relevance data.
     * 
     * @param array $params Search parameters
     * @return \Illuminate\Database\Eloquent\Collection Collection of compounds with type indicator
     */
    protected function searchCompoundsForGlobal(array $params): \Illuminate\Database\Eloquent\Collection
    {
        $searchTerm = $params['q'];
        
        $query = Compound::query()
            ->with(['area', 'developer', 'primaryImage'])
            ->withCount('properties')
            ->active();

        // Full-text search on title (note: compounds use 'title' not 'name') and description
        $query->where(function (Builder $q) use ($searchTerm) {
            $q->where('title', 'like', "%{$searchTerm}%")
                ->orWhere('description', 'like', "%{$searchTerm}%")
                ->orWhereJsonContains('title', $searchTerm);
        });

        // Area filter
        if (!empty($params['area_id'])) {
            $areaIds = $this->getAreaIdsWithChildren($params['area_id']);
            $query->whereIn('area_id', $areaIds);
        }

        // Developer filter
        if (!empty($params['developer_id'])) {
            $query->where('developer_id', $params['developer_id']);
        }

        // Price range (compound has min_price and max_price)
        if (!empty($params['min_price'])) {
            $query->where('max_price', '>=', $params['min_price']);
        }
        if (!empty($params['max_price'])) {
            $query->where('min_price', '<=', $params['max_price']);
        }

        // Amenities filter (compounds also have amenities relation)
        if (!empty($params['amenities'])) {
            $amenityIds = is_array($params['amenities']) ? $params['amenities'] : explode(',', $params['amenities']);
            $query->whereHas('amenities', function ($q) use ($amenityIds) {
                $q->whereIn('re_amenities.id', $amenityIds);
            });
        }

        // Limit results to 50 for performance
        $compounds = $query->limit(50)->get();

        // Add type indicator and relevance score placeholder to each result
        return $compounds->map(function ($compound) use ($searchTerm) {
            $compound->entity_type = 'compound';
            $compound->search_term = $searchTerm;
            // Relevance score will be calculated later in Phase 2.4
            $compound->relevance_score = 0;
            return $compound;
        });
    }

    /**
     * Search areas for global search with relevance data.
     * 
     * @param array $params Search parameters
     * @return \Illuminate\Database\Eloquent\Collection Collection of areas with type indicator
     */
    protected function searchAreasForGlobal(array $params): \Illuminate\Database\Eloquent\Collection
    {
        $searchTerm = $params['q'];
        
        $query = Area::query()
            ->withCount(['properties', 'compounds'])
            ->where('status', true);

        // Full-text search on name
        $query->where('name', 'like', "%{$searchTerm}%");

        // Area filter - if searching in a specific area, include it and its children
        if (!empty($params['area_id'])) {
            $areaIds = $this->getAreaIdsWithChildren($params['area_id']);
            $query->whereIn('id', $areaIds);
        }

        // Limit results to 20 (areas are usually fewer)
        $areas = $query->limit(20)->get();

        // Add type indicator and relevance score placeholder to each result
        return $areas->map(function ($area) use ($searchTerm) {
            $area->entity_type = 'area';
            $area->search_term = $searchTerm;
            // Relevance score will be calculated later in Phase 2.4
            $area->relevance_score = 0;
            return $area;
        });
    }

    /**
     * Search developers for global search with relevance data.
     * 
     * @param array $params Search parameters
     * @return \Illuminate\Database\Eloquent\Collection Collection of developers with type indicator
     */
    protected function searchDevelopersForGlobal(array $params): \Illuminate\Database\Eloquent\Collection
    {
        $searchTerm = $params['q'];
        
        $query = \Modules\RealEstate\Entities\Developer::query()
            ->where('status', true);

        // Full-text search on name and description
        $query->where(function (Builder $q) use ($searchTerm) {
            $q->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('description', 'like', "%{$searchTerm}%");
        });

        // Developer filter - if searching for a specific developer
        if (!empty($params['developer_id'])) {
            $query->where('id', $params['developer_id']);
        }

        // Limit results to 20 (developers are usually fewer)
        $developers = $query->limit(20)->get();

        // Add type indicator and relevance score placeholder to each result
        return $developers->map(function ($developer) use ($searchTerm) {
            $developer->entity_type = 'developer';
            $developer->search_term = $searchTerm;
            // Relevance score will be calculated later in Phase 2.4
            $developer->relevance_score = 0;
            return $developer;
        });
    }

    /**
     * Generate cache key for global search based on query parameters.
     * 
     * @param array $params Search parameters
     * @return string Cache key
     */
    protected function generateGlobalSearchCacheKey(array $params): string
    {
        // Sort parameters for consistent cache keys
        ksort($params);
        
        // Create hash from parameters
        $hash = md5(json_encode($params));
        
        return "global_search:{$hash}";
    }
}
