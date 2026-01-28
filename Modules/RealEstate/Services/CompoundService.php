<?php

declare(strict_types=1);

namespace Modules\RealEstate\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\RealEstate\Entities\Compound;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Compound Service
 * 
 * Handles all business logic for compound/project management including
 * CRUD operations, caching, and statistics calculations.
 */
class CompoundService
{
    /**
     * Cache TTL in seconds.
     */
    protected int $cacheTtl;

    public function __construct()
    {
        $this->cacheTtl = config('realestate.cache.compounds_ttl', 600);
    }

    /**
     * Get paginated compounds with filters.
     */
    public function getPaginatedCompounds(array $filters = []): LengthAwarePaginator
    {
        $query = QueryBuilder::for(Compound::class)
            ->allowedFilters([
                AllowedFilter::exact('area_id'),
                AllowedFilter::exact('developer_id'),
                AllowedFilter::scope('featured'),
                AllowedFilter::scope('status'),
                AllowedFilter::scope('price_range', 'priceRange'),
            ])
            ->allowedSorts(['created_at', 'name', 'min_price', 'total_units'])
            ->allowedIncludes(['area', 'developer', 'amenities', 'images', 'properties'])
            ->with(['area', 'developer', 'primaryImage'])
            ->active()
            ->withCount('properties');

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get a single compound by ID.
     */
    public function getCompound(int $id, array $relations = []): ?Compound
    {
        $defaultRelations = ['area', 'developer', 'amenities', 'images'];
        $relations = array_merge($defaultRelations, $relations);

        return Compound::with($relations)->withCount('properties')->find($id);
    }

    /**
     * Get compound by ID and slug for public URL.
     */
    public function getCompoundByIdAndSlug(int $id, string $slug): ?Compound
    {
        return Compound::with(['area', 'developer', 'amenities', 'images'])
            ->withCount('properties')
            ->where('id', $id)
            ->where('slug', $slug)
            ->active()
            ->first();
    }

    /**
     * Create a new compound.
     */
    public function createCompound(array $data): Compound
    {
        return DB::transaction(function () use ($data) {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Ensure unique slug
            $data['slug'] = $this->generateUniqueSlug($data['slug']);

            // Extract relations
            $amenities = $data['amenities'] ?? [];
            $images = $data['images'] ?? [];
            unset($data['amenities'], $data['images']);

            // Create compound
            $compound = Compound::create($data);

            // Sync amenities
            if (!empty($amenities)) {
                $compound->amenities()->sync($amenities);
            }

            // Create images
            if (!empty($images)) {
                $this->syncCompoundImages($compound, $images);
            }

            // Clear cache
            $this->clearCompoundCache();

            return $compound->fresh(['area', 'developer', 'amenities', 'images']);
        });
    }

    /**
     * Update an existing compound.
     */
    public function updateCompound(Compound $compound, array $data): Compound
    {
        return DB::transaction(function () use ($compound, $data) {
            // Update slug if name changed and slug not provided
            if (isset($data['name']) && empty($data['slug']) && $data['name'] !== $compound->name) {
                $data['slug'] = $this->generateUniqueSlug(Str::slug($data['name']), $compound->id);
            }

            // Extract relations
            $amenities = $data['amenities'] ?? null;
            $images = $data['images'] ?? null;
            unset($data['amenities'], $data['images']);

            // Update compound
            $compound->update($data);

            // Sync amenities if provided
            if ($amenities !== null) {
                $compound->amenities()->sync($amenities);
            }

            // Update images if provided
            if ($images !== null) {
                $this->syncCompoundImages($compound, $images);
            }

            // Clear cache
            $this->clearCompoundCache();

            return $compound->fresh(['area', 'developer', 'amenities', 'images']);
        });
    }

    /**
     * Delete a compound (soft delete).
     */
    public function deleteCompound(Compound $compound): bool
    {
        $this->clearCompoundCache();
        return $compound->delete();
    }

    /**
     * Bulk update compounds.
     */
    public function bulkAction(array $ids, string $action): int
    {
        $count = 0;

        switch ($action) {
            case 'delete':
                $count = Compound::whereIn('id', $ids)->delete();
                break;
            case 'publish':
                $count = Compound::whereIn('id', $ids)->update(['status' => true]);
                break;
            case 'unpublish':
                $count = Compound::whereIn('id', $ids)->update(['status' => false]);
                break;
            case 'feature':
                $count = Compound::whereIn('id', $ids)->update(['is_featured' => true]);
                break;
            case 'unfeature':
                $count = Compound::whereIn('id', $ids)->update(['is_featured' => false]);
                break;
        }

        // Clear cache
        $this->clearCompoundCache();

        return $count;
    }

    /**
     * Get featured compounds.
     */
    public function getFeaturedCompounds(int $limit = 10): Collection
    {
        return Cache::remember(
            'featured_compounds_' . $limit,
            $this->cacheTtl,
            fn () => Compound::with(['area', 'developer', 'primaryImage'])
                ->withCount('properties')
                ->featured()
                ->active()
                ->latest()
                ->limit($limit)
                ->get()
        );
    }

    /**
     * Get compounds by developer.
     */
    public function getCompoundsByDeveloper(int $developerId, int $limit = 10): Collection
    {
        return Compound::with(['area', 'primaryImage'])
            ->withCount('properties')
            ->where('developer_id', $developerId)
            ->active()
            ->limit($limit)
            ->get();
    }

    /**
     * Get compounds by area.
     */
    public function getCompoundsByArea(int $areaId, int $limit = 10): Collection
    {
        return Compound::with(['developer', 'primaryImage'])
            ->withCount('properties')
            ->where('area_id', $areaId)
            ->active()
            ->limit($limit)
            ->get();
    }

    /**
     * Get compound statistics.
     */
    public function getStatistics(): array
    {
        return Cache::remember(
            'compound_statistics',
            $this->cacheTtl,
            function () {
                $compounds = Compound::with('properties')->get();
                
                return [
                    'total' => $compounds->count(),
                    'active' => $compounds->where('status', true)->count(),
                    'featured' => $compounds->where('is_featured', true)->where('status', true)->count(),
                    'total_properties' => $compounds->sum(fn ($c) => $c->properties->count()),
                    'avg_properties_per_compound' => $compounds->avg(fn ($c) => $c->properties->count()),
                ];
            }
        );
    }

    /**
     * Update compound price stats from properties.
     */
    public function updatePriceStats(Compound $compound): void
    {
        $stats = $compound->properties()
            ->active()
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        $compound->update([
            'min_price' => $stats->min_price,
            'max_price' => $stats->max_price,
        ]);
    }

    /**
     * Sync compound images.
     */
    protected function syncCompoundImages(Compound $compound, array $images): void
    {
        // Delete existing images
        $compound->images()->delete();

        // Create new images
        foreach ($images as $index => $image) {
            $compound->images()->create([
                'image_path' => $image['path'] ?? $image,
                'type' => $image['type'] ?? 'gallery',
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

        $query = Compound::where('slug', $slug);
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $query = Compound::where('slug', $slug);
            if ($exceptId) {
                $query->where('id', '!=', $exceptId);
            }
            $counter++;
        }

        return $slug;
    }

    /**
     * Clear compound-related cache.
     */
    protected function clearCompoundCache(): void
    {
        Cache::forget('featured_compounds_*');
        Cache::forget('compound_statistics');
    }
}
