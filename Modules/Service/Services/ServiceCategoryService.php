<?php

declare(strict_types=1);

namespace Modules\Service\Services;

use App\Helpers\SanitizeInput;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Service\Entities\ServiceCategory;

/**
 * Service class for managing service categories.
 */
final class ServiceCategoryService
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
        $query = ServiceCategory::query();

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
        return ServiceCategory::where('status', true)
            ->orderBy('title', 'asc')
            ->get();
    }

    /**
     * Create a new category.
     *
     * @param array<string, mixed> $data
     * @return ServiceCategory
     */
    public function createCategory(array $data): ServiceCategory
    {
        return ServiceCategory::create([
            'title' => SanitizeInput::esc_html($data['title']),
            'status' => $data['status'] ?? true,
        ]);
    }

    /**
     * Update an existing category.
     *
     * @param ServiceCategory $category
     * @param array<string, mixed> $data
     * @return ServiceCategory
     */
    public function updateCategory(ServiceCategory $category, array $data): ServiceCategory
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
     * @param ServiceCategory $category
     * @return bool
     * @throws \Exception
     */
    public function deleteCategory(ServiceCategory $category): bool
    {
        // Check if category has services
        $servicesCount = \Modules\Service\Entities\Service::where('category_id', $category->id)->count();
        
        if ($servicesCount > 0) {
            throw new \Exception('Cannot delete category that has services associated with it.');
        }

        return $category->delete();
    }
}
