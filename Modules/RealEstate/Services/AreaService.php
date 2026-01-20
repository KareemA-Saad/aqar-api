<?php

declare(strict_types=1);

namespace Modules\RealEstate\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\RealEstate\Entities\Area;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * Area Service
 * 
 * Handles all business logic for area/location management including
 * CRUD operations, caching, and hierarchical operations.
 */
class AreaService
{
    /**
     * Cache TTL in seconds.
     */
    protected int $cacheTtl;

    public function __construct()
    {
        $this->cacheTtl = config('realestate.cache.areas_ttl', 3600);
    }

    /**
     * Get paginated areas with filters.
     */
    public function getPaginatedAreas(array $filters = []): LengthAwarePaginator
    {
        $query = QueryBuilder::for(Area::class)
            ->allowedFilters([
                AllowedFilter::exact('parent_id'),
                AllowedFilter::exact('type'),
                AllowedFilter::scope('featured'),
                AllowedFilter::scope('status'),
            ])
            ->allowedSorts(['created_at', 'name', 'order'])
            ->allowedIncludes(['parent', 'children', 'compounds', 'properties'])
            ->with(['parent'])
            ->withCount(['compounds', 'properties'])
            ->active();

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get all areas as a tree structure.
     */
    public function getAreasTree(): Collection
    {
        return Cache::tags(['areas'])->remember(
            'areas_tree',
            $this->cacheTtl,
            fn () => Area::with(['children' => function ($query) {
                $query->active()->withCount(['compounds', 'properties'])->orderBy('order');
            }])
                ->whereNull('parent_id')
                ->active()
                ->withCount(['compounds', 'properties'])
                ->orderBy('order')
                ->get()
        );
    }

    /**
     * Get root areas (cities/governorates).
     */
    public function getRootAreas(): Collection
    {
        return Cache::tags(['areas'])->remember(
            'root_areas',
            $this->cacheTtl,
            fn () => Area::whereNull('parent_id')
                ->active()
                ->withCount(['compounds', 'properties', 'children'])
                ->orderBy('order')
                ->get()
        );
    }

    /**
     * Get children of an area.
     */
    public function getChildAreas(int $parentId): Collection
    {
        return Area::where('parent_id', $parentId)
            ->active()
            ->withCount(['compounds', 'properties', 'children'])
            ->orderBy('order')
            ->get();
    }

    /**
     * Get a single area by ID.
     */
    public function getArea(int $id, array $relations = []): ?Area
    {
        $defaultRelations = ['parent', 'children'];
        $relations = array_merge($defaultRelations, $relations);

        return Area::with($relations)->withCount(['compounds', 'properties'])->find($id);
    }

    /**
     * Get area by ID and slug for public URL.
     */
    public function getAreaByIdAndSlug(int $id, string $slug): ?Area
    {
        return Area::with(['parent', 'children'])
            ->withCount(['compounds', 'properties'])
            ->where('id', $id)
            ->where('slug', $slug)
            ->active()
            ->first();
    }

    /**
     * Create a new area.
     */
    public function createArea(array $data): Area
    {
        return DB::transaction(function () use ($data) {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Ensure unique slug
            $data['slug'] = $this->generateUniqueSlug($data['slug']);

            // Set order if not provided
            if (!isset($data['order'])) {
                $data['order'] = Area::where('parent_id', $data['parent_id'] ?? null)->max('order') + 1;
            }

            // Create area
            $area = Area::create($data);

            // Clear cache
            $this->clearAreaCache();

            return $area->fresh(['parent']);
        });
    }

    /**
     * Update an existing area.
     */
    public function updateArea(Area $area, array $data): Area
    {
        return DB::transaction(function () use ($area, $data) {
            // Update slug if name changed and slug not provided
            if (isset($data['name']) && empty($data['slug']) && $data['name'] !== $area->name) {
                $data['slug'] = $this->generateUniqueSlug(Str::slug($data['name']), $area->id);
            }

            // Prevent setting self as parent
            if (isset($data['parent_id']) && $data['parent_id'] == $area->id) {
                throw new \InvalidArgumentException('An area cannot be its own parent.');
            }

            // Prevent circular reference
            if (isset($data['parent_id']) && $this->isDescendant($area->id, $data['parent_id'])) {
                throw new \InvalidArgumentException('Cannot set a descendant as parent.');
            }

            // Update area
            $area->update($data);

            // Clear cache
            $this->clearAreaCache();

            return $area->fresh(['parent']);
        });
    }

    /**
     * Delete an area (soft delete).
     */
    public function deleteArea(Area $area): bool
    {
        // Check if area has children
        if ($area->children()->exists()) {
            throw new \InvalidArgumentException('Cannot delete area with child areas. Please delete or move children first.');
        }

        // Check if area has compounds or properties
        if ($area->compounds()->exists() || $area->properties()->exists()) {
            throw new \InvalidArgumentException('Cannot delete area with associated compounds or properties.');
        }

        $this->clearAreaCache();
        return $area->delete();
    }

    /**
     * Reorder areas.
     */
    public function reorderAreas(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                Area::where('id', $id)->update(['order' => $index]);
            }
        });

        $this->clearAreaCache();
    }

    /**
     * Get featured areas.
     */
    public function getFeaturedAreas(int $limit = 10): Collection
    {
        return Cache::tags(['areas'])->remember(
            'featured_areas_' . $limit,
            $this->cacheTtl,
            fn () => Area::with(['parent'])
                ->withCount(['compounds', 'properties'])
                ->featured()
                ->active()
                ->orderBy('order')
                ->limit($limit)
                ->get()
        );
    }

    /**
     * Get area statistics.
     */
    public function getStatistics(): array
    {
        return Cache::tags(['areas'])->remember(
            'area_statistics',
            $this->cacheTtl,
            function () {
                return [
                    'total' => Area::count(),
                    'active' => Area::active()->count(),
                    'featured' => Area::featured()->active()->count(),
                    'root_areas' => Area::whereNull('parent_id')->active()->count(),
                    'with_compounds' => Area::has('compounds')->count(),
                    'with_properties' => Area::has('properties')->count(),
                ];
            }
        );
    }

    /**
     * Search areas by name.
     */
    public function searchAreas(string $query, int $limit = 10): Collection
    {
        return Area::where('name', 'like', "%{$query}%")
            ->orWhereJsonContains('name', $query)
            ->active()
            ->with('parent')
            ->limit($limit)
            ->get();
    }

    /**
     * Get breadcrumbs for an area (path from root to current).
     */
    public function getBreadcrumbs(Area $area): array
    {
        $breadcrumbs = [];
        $current = $area;

        while ($current) {
            array_unshift($breadcrumbs, [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
            ]);
            $current = $current->parent;
        }

        return $breadcrumbs;
    }

    /**
     * Check if areaId is a descendant of potentialAncestorId.
     */
    protected function isDescendant(int $areaId, int $potentialDescendantId): bool
    {
        $descendants = $this->getAllDescendantIds($areaId);
        return in_array($potentialDescendantId, $descendants);
    }

    /**
     * Get all descendant IDs of an area.
     */
    protected function getAllDescendantIds(int $areaId): array
    {
        $ids = [];
        $children = Area::where('parent_id', $areaId)->pluck('id');

        foreach ($children as $childId) {
            $ids[] = $childId;
            $ids = array_merge($ids, $this->getAllDescendantIds($childId));
        }

        return $ids;
    }

    /**
     * Generate unique slug.
     */
    protected function generateUniqueSlug(string $slug, ?int $exceptId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;

        $query = Area::where('slug', $slug);
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }

        while ($query->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $query = Area::where('slug', $slug);
            if ($exceptId) {
                $query->where('id', '!=', $exceptId);
            }
            $counter++;
        }

        return $slug;
    }

    /**
     * Clear area-related cache.
     */
    protected function clearAreaCache(): void
    {
        Cache::tags(['areas'])->flush();
    }
}
