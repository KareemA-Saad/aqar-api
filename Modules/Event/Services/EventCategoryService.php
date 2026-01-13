<?php

declare(strict_types=1);

namespace Modules\Event\Services;

use App\Helpers\SanitizeInput;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Event\Entities\EventCategory;

/**
 * Service class for managing event categories.
 */
final class EventCategoryService
{
    /**
     * Get paginated list of categories with optional filters.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getCategories(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = EventCategory::query()->withCount('events');

        // Search filter
        if (!empty($filters['search'])) {
            $query->where('title', 'like', "%{$filters['search']}%");
        }

        // Status filter
        if (isset($filters['status'])) {
            $query->where('status', (bool) $filters['status']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'id';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['id', 'title', 'created_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get all active categories.
     *
     * @return Collection
     */
    public function getActiveCategories(): Collection
    {
        return EventCategory::where('status', true)
            ->withCount('events')
            ->orderBy('title', 'asc')
            ->get();
    }

    /**
     * Create a new category.
     *
     * @param array<string, mixed> $data
     * @return EventCategory
     */
    public function createCategory(array $data): EventCategory
    {
        return EventCategory::create([
            'title' => SanitizeInput::esc_html($data['title']),
            'status' => $data['status'] ?? true,
        ]);
    }

    /**
     * Update an existing category.
     *
     * @param EventCategory $category
     * @param array<string, mixed> $data
     * @return EventCategory
     */
    public function updateCategory(EventCategory $category, array $data): EventCategory
    {
        if (isset($data['title'])) {
            $data['title'] = SanitizeInput::esc_html($data['title']);
        }

        $category->update($data);
        return $category->fresh();
    }

    /**
     * Delete a category.
     *
     * @param EventCategory $category
     * @return bool
     * @throws \Exception
     */
    public function deleteCategory(EventCategory $category): bool
    {
        // Check if category has events
        if ($category->events()->exists()) {
            throw new \Exception('Cannot delete category that has events associated with it.');
        }

        return $category->delete();
    }

    /**
     * Check if category can be deleted.
     *
     * @param EventCategory $category
     * @return bool
     */
    public function canDelete(EventCategory $category): bool
    {
        return !$category->events()->exists();
    }
}
