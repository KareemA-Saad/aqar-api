<?php

declare(strict_types=1);

namespace Modules\Knowledgebase\Services;

use App\Helpers\SanitizeInput;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Modules\Knowledgebase\Entities\KnowledgebaseCategory;

/**
 * Service class for managing knowledgebase categories.
 */
final class KnowledgebaseCategoryService
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
        $query = KnowledgebaseCategory::query();

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
        return KnowledgebaseCategory::where('status', true)
            ->orderBy('title', 'asc')
            ->get();
    }

    /**
     * Create a new category.
     *
     * @param array<string, mixed> $data
     * @return KnowledgebaseCategory
     */
    public function createCategory(array $data): KnowledgebaseCategory
    {
        return KnowledgebaseCategory::create([
            'title' => SanitizeInput::esc_html($data['title']),
            'image' => $data['image'] ?? null,
            'status' => $data['status'] ?? true,
        ]);
    }

    /**
     * Update an existing category.
     *
     * @param KnowledgebaseCategory $category
     * @param array<string, mixed> $data
     * @return KnowledgebaseCategory
     */
    public function updateCategory(KnowledgebaseCategory $category, array $data): KnowledgebaseCategory
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
     * @param KnowledgebaseCategory $category
     * @return bool
     * @throws \Exception
     */
    public function deleteCategory(KnowledgebaseCategory $category): bool
    {
        // Check if category has knowledgebase articles
        $articlesCount = \Modules\Knowledgebase\Entities\Knowledgebase::where('category_id', $category->id)->count();
        
        if ($articlesCount > 0) {
            throw new \Exception('Cannot delete category that has knowledgebase articles associated with it.');
        }

        return $category->delete();
    }
}
