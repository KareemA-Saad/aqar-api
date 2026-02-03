<?php

declare(strict_types=1);

namespace Modules\RealEstate\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\RealEstate\Entities\Property;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Property Service
 * 
 * Handles all business logic for property management including
 * CRUD operations, caching, and statistics calculations.
 */
class PropertyService
{
    /**
     * Cache TTL in seconds.
     */
    protected int $cacheTtl;

    public function __construct()
    {
        $this->cacheTtl = config('realestate.cache.properties_ttl', 300);
    }

    /**
     * Get paginated properties with filters.
     */
    public function getPaginatedProperties(array $filters = []): LengthAwarePaginator
    {
        $query = QueryBuilder::for(Property::class)
            ->allowedFilters([
                AllowedFilter::exact('compound.area_id'),
                AllowedFilter::exact('compound_id'),
                AllowedFilter::exact('property_type_id'),
                AllowedFilter::exact('developer_id'),
                AllowedFilter::exact('purpose'),
                AllowedFilter::exact('finishing'),
                AllowedFilter::scope('price_range', 'priceRange'),
                AllowedFilter::scope('area_range', 'areaRange'),
                AllowedFilter::scope('bedrooms'),
                AllowedFilter::scope('bathrooms'),
                AllowedFilter::scope('delivery_year', 'deliveryYear'),
                AllowedFilter::scope('featured'),
                AllowedFilter::scope('status'),
            ])
            ->allowedSorts(['created_at', 'price', 'area', 'bedrooms', 'views_count'])
            ->allowedIncludes(['area', 'compound', 'propertyType', 'developer', 'images', 'amenities'])
            ->with(['compound.area', 'propertyType', 'primaryImage'])
            ->active();

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get a single property by ID.
     */
    public function getProperty(int|string $id, array $relations = []): ?Property
    {
        $defaultRelations = ['area', 'compound', 'propertyType', 'developer', 'images', 'amenities'];
        $relations = array_merge($defaultRelations, $relations);

        return Property::with($relations)->find((int) $id);
    }

    /**
     * Get property by ID and slug for public URL.
     */
    public function getPropertyByIdAndSlug(int|string $id, string $slug): ?Property
    {
        return Property::with(['compound.area', 'compound', 'propertyType', 'developer', 'images', 'amenities'])
            ->where('id', (int) $id)
            ->where('slug', $slug)
            ->active()
            ->first();
    }

    /**
     * Create a new property.
     */
    public function createProperty(array $data): Property
    {
        return DB::transaction(function () use ($data) {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            // Ensure unique slug
            $data['slug'] = $this->generateUniqueSlug($data['slug']);

            // Extract relations
            $amenities = $data['amenities'] ?? [];
            $images = $data['images'] ?? [];
            unset($data['amenities'], $data['images']);

            // Create property
            $property = Property::create($data);

            // Sync amenities
            if (!empty($amenities)) {
                $property->amenities()->sync($amenities);
            }

            // Create images
            if (!empty($images)) {
                $this->syncPropertyImages($property, $images);
            }

            // Clear cache
            $this->clearPropertyCache($property);

            return $property->fresh(['area', 'compound', 'propertyType', 'developer', 'images', 'amenities']);
        });
    }

    /**
     * Update an existing property.
     */
    public function updateProperty(Property $property, array $data): Property
    {
        return DB::transaction(function () use ($property, $data) {
            // Update slug if title changed and slug not provided
            if (isset($data['title']) && empty($data['slug']) && $data['title'] !== $property->title) {
                $data['slug'] = $this->generateUniqueSlug(Str::slug($data['title']), $property->id);
            }

            // Extract relations
            $amenities = $data['amenities'] ?? null;
            $images = $data['images'] ?? null;
            unset($data['amenities'], $data['images']);

            // Update property
            $property->update($data);

            // Sync amenities if provided
            if ($amenities !== null) {
                $property->amenities()->sync($amenities);
            }

            // Update images if provided
            if ($images !== null) {
                $this->syncPropertyImages($property, $images);
            }

            // Clear cache
            $this->clearPropertyCache($property);

            return $property->fresh(['area', 'compound', 'propertyType', 'developer', 'images', 'amenities']);
        });
    }

    /**
     * Delete a property (soft delete).
     */
    public function deleteProperty(Property $property): bool
    {
        $this->clearPropertyCache($property);
        return $property->delete();
    }

    /**
     * Bulk update properties.
     */
    public function bulkAction(array $ids, string $action): int
    {
        $count = 0;

        switch ($action) {
            case 'delete':
                $count = Property::whereIn('id', $ids)->delete();
                break;
            case 'publish':
                $count = Property::whereIn('id', $ids)->update(['status' => true]);
                break;
            case 'unpublish':
                $count = Property::whereIn('id', $ids)->update(['status' => false]);
                break;
            case 'feature':
                $count = Property::whereIn('id', $ids)->update(['is_featured' => true]);
                break;
            case 'unfeature':
                $count = Property::whereIn('id', $ids)->update(['is_featured' => false]);
                break;
        }

        // Clear cache
        $this->clearPropertyCacheIfSupported();

        return $count;
    }

    /**
     * Get featured properties.
     */
    public function getFeaturedProperties(int $limit = 10): Collection
    {
        $callback = fn () => Property::with(['compound.area', 'propertyType', 'primaryImage'])
            ->featured()
            ->active()
            ->latest()
            ->limit($limit)
            ->get();

        if ($this->cacheSupportsTagging()) {
            return Cache::remember('featured_properties_' . $limit, $this->cacheTtl, $callback);
        }

        return $callback();
    }

    /**
     * Get similar properties.
     */
    public function getSimilarProperties(Property $property, int $limit = 6): Collection
    {
        return Property::with(['compound.area', 'propertyType', 'primaryImage'])
            ->where('id', '!=', $property->id)
            ->where(function ($query) use ($property) {
                // Match by compound (same area) or property type
                $query->where('compound_id', $property->compound_id)
                    ->orWhere('property_type_id', $property->property_type_id);
            })
            ->active()
            ->limit($limit)
            ->get();
    }

    /**
     * Increment property views.
     */
    public function incrementViews(Property $property): void
    {
        $property->increment('views_count');
    }

    /**
     * Get property statistics.
     */
    public function getStatistics(): array
    {
        $callback = function () {
            return [
                'total' => Property::count(),
                'active' => Property::active()->count(),
                'featured' => Property::featured()->active()->count(),
                'for_sale' => Property::forSale()->active()->count(),
                'for_rent' => Property::forRent()->active()->count(),
                'avg_price' => Property::active()->avg('price'),
                'total_inquiries' => Property::withCount('inquiries')->get()->sum('inquiries_count'),
                'total_views' => Property::sum('views_count'),
            ];
        };

        if ($this->cacheSupportsTagging()) {
            return Cache::remember('property_statistics', $this->cacheTtl, $callback);
        }

        return $callback();
    }

    /**
     * Sync property images.
     */
    protected function syncPropertyImages(Property $property, array $images): void
    {
        // Delete existing images
        $property->images()->delete();

        // Create new images
        foreach ($images as $index => $image) {
            $property->images()->create([
                'image_path' => $image['path'] ?? $image,
                'alt_text' => $image['alt_text'] ?? null,
                'is_primary' => $image['is_primary'] ?? ($index === 0),
                'order' => $image['order'] ?? $index,
            ]);
        }
    }

    /**
     * Generate unique slug.
     */
    protected function generateUniqueSlug(string $slug, ?int $exceptId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;

        $query = Property::where('slug', $slug);
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $query = Property::where('slug', $slug);
            if ($exceptId) {
                $query->where('id', '!=', $exceptId);
            }
            $counter++;
        }

        return $slug;
    }

    /**
     * Clear property-related cache.
     */
    protected function clearPropertyCache(Property $property): void
    {
        if (!$this->cacheSupportsTagging()) {
            return;
        }

        Cache::forget('featured_properties_*');
        Cache::forget('property_statistics');
        
        if ($property->compound_id) {
            Cache::forget('compound_' . $property->compound_id);
            
            // Clear area cache through compound
            $compound = $property->compound;
            if ($compound && $compound->area_id) {
                Cache::forget('area_' . $compound->area_id);
            }
        }
    }

    /**
     * Clear property cache if supported (for bulk operations).
     */
    protected function clearPropertyCacheIfSupported(): void
    {
        if (!$this->cacheSupportsTagging()) {
            return;
        }

        Cache::forget('featured_properties_*');
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
}
