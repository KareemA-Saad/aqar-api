<?php

declare(strict_types=1);

namespace Modules\Portfolio\Services;

use App\Helpers\SanitizeInput;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Portfolio\Entities\PortfolioCategory;

/**
 * Service class for managing portfolio categories.
 */
final class PortfolioCategoryService
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
        $query = PortfolioCategory::query();

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
        return PortfolioCategory::where('status', true)
            ->orderBy('title', 'asc')
            ->get();
    }

    /**
     * Create a new category.
     *
     * @param array<string, mixed> $data
     * @return PortfolioCategory
     */
    public function createCategory(array $data): PortfolioCategory
    {
        return PortfolioCategory::create([
            'title' => SanitizeInput::esc_html($data['title']),
            'status' => $data['status'] ?? true,
        ]);
    }

    /**
     * Update an existing category.
     *
     * @param PortfolioCategory $category
     * @param array<string, mixed> $data
     * @return PortfolioCategory
     */
    public function updateCategory(PortfolioCategory $category, array $data): PortfolioCategory
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
     * @param PortfolioCategory $category
     * @return bool
     * @throws \Exception
     */
    public function deleteCategory(PortfolioCategory $category): bool
    {
        // Check if category has portfolios
        $portfoliosCount = \Modules\Portfolio\Entities\Portfolio::where('category_id', $category->id)->count();
        
        if ($portfoliosCount > 0) {
            throw new \Exception('Cannot delete category that has portfolios associated with it.');
        }

        return $category->delete();
    }
}
