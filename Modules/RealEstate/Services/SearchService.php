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
            ->with(['area', 'compound', 'propertyType', 'primaryImage'])
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

        // Location filters
        if (!empty($params['area_id'])) {
            $areaIds = $this->getAreaIdsWithChildren($params['area_id']);
            $query->whereIn('area_id', $areaIds);
        }

        if (!empty($params['compound_id'])) {
            $query->where('compound_id', $params['compound_id']);
        }

        // Property type filter
        if (!empty($params['property_type_id'])) {
            $query->where('property_type_id', $params['property_type_id']);
        }

        // Developer filter
        if (!empty($params['developer_id'])) {
            $query->where('developer_id', $params['developer_id']);
        }

        // Purpose filter (sale/rent)
        if (!empty($params['purpose'])) {
            $query->where('purpose', $params['purpose']);
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

        // Delivery year
        if (!empty($params['delivery_year'])) {
            $query->where('delivery_year', $params['delivery_year']);
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
        $cacheKey = 'autocomplete_' . md5($query . '_' . $limit);

        return Cache::remember($cacheKey, 60, function () use ($query, $limit) {
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
        });
    }

    /**
     * Get search facets (filters with counts).
     */
    public function getSearchFacets(array $params = []): array
    {
        $cacheKey = 'search_facets_' . md5(json_encode($params));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($params) {
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
        });
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
        return $query->selectRaw('purpose, COUNT(*) as count')
            ->groupBy('purpose')
            ->get()
            ->map(fn ($item) => [
                'value' => $item->purpose,
                'label' => ucfirst($item->purpose),
                'count' => $item->count,
            ])
            ->toArray();
    }

    /**
     * Get area IDs including all children.
     */
    protected function getAreaIdsWithChildren(int $areaId): array
    {
        return Cache::remember(
            "area_ids_with_children_{$areaId}",
            $this->cacheTtl,
            function () use ($areaId) {
                $ids = [$areaId];
                $children = Area::where('parent_id', $areaId)->pluck('id');

                foreach ($children as $childId) {
                    $ids = array_merge($ids, $this->getAreaIdsWithChildren($childId));
                }

                return array_unique($ids);
            }
        );
    }

    /**
     * Get popular searches (cached).
     */
    public function getPopularSearches(int $limit = 10): array
    {
        return Cache::remember('popular_searches', 3600, function () use ($limit) {
            // This could be populated from a search_logs table in a real implementation
            return [
                ['term' => 'apartment new cairo', 'count' => 150],
                ['term' => 'villa sheikh zayed', 'count' => 120],
                ['term' => 'studio 6th october', 'count' => 100],
                ['term' => 'duplex maadi', 'count' => 80],
            ];
        });
    }

    /**
     * Get nearby properties based on coordinates.
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
            ->with(['area', 'propertyType', 'primaryImage'])
            ->get();

        return $properties->toArray();
    }
}
