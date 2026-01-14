<?php

declare(strict_types=1);

namespace Modules\Portfolio\Services;

use App\Helpers\SanitizeInput;
use App\Models\MetaInfo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Portfolio\Entities\Portfolio;
use Modules\Portfolio\Entities\PortfolioCategory;

/**
 * Service class for managing portfolios.
 */
final class PortfolioService
{
    /**
     * Get paginated list of portfolios with optional filters.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPortfolios(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Portfolio::query()->with(['category', 'metainfo']);

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('client', 'like', "%{$search}%")
                    ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Status filter
        if (isset($filters['status'])) {
            $query->where('status', (bool) $filters['status']);
        }

        // Tags filter
        if (!empty($filters['tag'])) {
            $query->where('tags', 'like', "%{$filters['tag']}%");
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['title', 'client', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get published portfolios for public display.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPublishedPortfolios(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['status'] = true;
        return $this->getPortfolios($filters, $perPage);
    }

    /**
     * Get portfolios by category.
     *
     * @param int $categoryId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPortfoliosByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getPublishedPortfolios([
            'category_id' => $categoryId
        ], $perPage);
    }

    /**
     * Get portfolios by tag.
     *
     * @param string $tag
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPortfoliosByTag(string $tag, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getPublishedPortfolios([
            'tag' => $tag
        ], $perPage);
    }

    /**
     * Search portfolios.
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchPortfolios(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getPublishedPortfolios([
            'search' => $query
        ], $perPage);
    }

    /**
     * Get portfolio by slug.
     *
     * @param string $slug
     * @return Portfolio|null
     */
    public function getPortfolioBySlug(string $slug): ?Portfolio
    {
        return Portfolio::where('slug', $slug)
            ->where('status', true)
            ->with(['category', 'metainfo'])
            ->first();
    }

    /**
     * Create a new portfolio.
     *
     * @param array<string, mixed> $data
     * @return Portfolio
     */
    public function createPortfolio(array $data): Portfolio
    {
        return DB::transaction(function () use ($data) {
            // Generate slug if not provided
            $slug = $data['slug'] ?? Str::slug($data['title']);
            $slug = $this->ensureUniqueSlug($slug);

            $portfolio = Portfolio::create([
                'title' => SanitizeInput::esc_html($data['title']),
                'slug' => $slug,
                'url' => $data['url'] ?? null,
                'description' => $data['description'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'image' => $data['image'] ?? null,
                'image_gallery' => $data['image_gallery'] ?? null,
                'client' => $data['client'] ?? null,
                'design' => $data['design'] ?? null,
                'typography' => $data['typography'] ?? null,
                'tags' => $data['tags'] ?? null,
                'file' => $data['file'] ?? null,
                'download' => $data['download'] ?? null,
                'status' => $data['status'] ?? false,
            ]);

            // Create meta info if provided
            if (!empty($data['meta_title']) || !empty($data['meta_description'])) {
                $portfolio->metainfo()->create([
                    'meta_title' => $data['meta_title'] ?? null,
                    'meta_description' => $data['meta_description'] ?? null,
                    'meta_tags' => $data['meta_tags'] ?? null,
                ]);
            }

            return $portfolio->load(['category', 'metainfo']);
        });
    }

    /**
     * Update an existing portfolio.
     *
     * @param Portfolio $portfolio
     * @param array<string, mixed> $data
     * @return Portfolio
     */
    public function updatePortfolio(Portfolio $portfolio, array $data): Portfolio
    {
        return DB::transaction(function () use ($portfolio, $data) {
            // Generate slug if title changed
            if (isset($data['title']) && $data['title'] !== $portfolio->title) {
                $slug = $data['slug'] ?? Str::slug($data['title']);
                $data['slug'] = $this->ensureUniqueSlug($slug, $portfolio->id);
            }

            // Sanitize inputs
            if (isset($data['title'])) {
                $data['title'] = SanitizeInput::esc_html($data['title']);
            }

            $portfolio->update($data);

            // Update meta info if provided
            if (isset($data['meta_title']) || isset($data['meta_description'])) {
                $metaData = [
                    'meta_title' => $data['meta_title'] ?? null,
                    'meta_description' => $data['meta_description'] ?? null,
                    'meta_tags' => $data['meta_tags'] ?? null,
                ];

                if ($portfolio->metainfo) {
                    $portfolio->metainfo->update($metaData);
                } else {
                    $portfolio->metainfo()->create($metaData);
                }
            }

            return $portfolio->load(['category', 'metainfo']);
        });
    }

    /**
     * Delete a portfolio.
     *
     * @param Portfolio $portfolio
     * @return bool
     */
    public function deletePortfolio(Portfolio $portfolio): bool
    {
        return DB::transaction(function () use ($portfolio) {
            $portfolio->metainfo()?->delete();
            return $portfolio->delete();
        });
    }

    /**
     * Get related portfolios (same category).
     *
     * @param Portfolio $portfolio
     * @param int $limit
     * @return Collection
     */
    public function getRelatedPortfolios(Portfolio $portfolio, int $limit = 5): Collection
    {
        return Portfolio::where('category_id', $portfolio->category_id)
            ->where('id', '!=', $portfolio->id)
            ->where('status', true)
            ->with(['category'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Clone an existing portfolio.
     *
     * @param Portfolio $portfolio
     * @return Portfolio
     */
    public function clonePortfolio(Portfolio $portfolio): Portfolio
    {
        return DB::transaction(function () use ($portfolio) {
            $clone = $portfolio->replicate();
            
            // Set status to 0 (draft) for cloned portfolio
            $clone->status = false;
            
            // Generate new unique slug
            $clone->slug = $this->ensureUniqueSlug($portfolio->slug . '-copy');
            
            // Reset download counter
            $clone->download = 0;
            
            $clone->save();

            // Clone meta info if exists
            if ($portfolio->metainfo) {
                $metaClone = $portfolio->metainfo->replicate();
                $clone->metainfo()->save($metaClone);
            }

            return $clone->load(['category', 'metainfo']);
        });
    }

    /**
     * Increment download counter for portfolio file.
     *
     * @param Portfolio $portfolio
     * @return bool
     */
    public function incrementDownload(Portfolio $portfolio): bool
    {
        if (empty($portfolio->file)) {
            return false;
        }

        return $portfolio->increment('download');
    }

    /**
     * Get all unique tags from published portfolios.
     *
     * @return array<string>
     */
    public function getAllTags(): array
    {
        $portfolios = Portfolio::where('status', true)
            ->whereNotNull('tags')
            ->select('tags')
            ->get();

        $allTags = [];
        foreach ($portfolios as $portfolio) {
            if (!empty($portfolio->tags)) {
                $tags = array_map('trim', explode(',', $portfolio->tags));
                $allTags = array_merge($allTags, $tags);
            }
        }

        return array_values(array_unique($allTags));
    }

    /**
     * Ensure unique slug for portfolio.
     *
     * @param string $slug
     * @param int|null $excludeId
     * @return string
     */
    private function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $count = 1;

        while (true) {
            $query = Portfolio::where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            
            if (!$query->exists()) {
                break;
            }
            
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
