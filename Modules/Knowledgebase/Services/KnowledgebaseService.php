<?php

declare(strict_types=1);

namespace Modules\Knowledgebase\Services;

use App\Helpers\SanitizeInput;
use App\Models\MetaInfo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Knowledgebase\Entities\Knowledgebase;
use Modules\Knowledgebase\Entities\KnowledgebaseCategory;

/**
 * Service class for managing knowledgebase articles.
 */
final class KnowledgebaseService
{
    /**
     * Get paginated list of knowledgebase articles with optional filters.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getKnowledgebases(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Knowledgebase::query()->with(['category', 'metainfo']);

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
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

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['title', 'views', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get published knowledgebase articles for public display.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPublishedKnowledgebases(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['status'] = true;
        return $this->getKnowledgebases($filters, $perPage);
    }

    /**
     * Get knowledgebase articles by category.
     *
     * @param int $categoryId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getKnowledgebasesByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getPublishedKnowledgebases([
            'category_id' => $categoryId
        ], $perPage);
    }

    /**
     * Search knowledgebase articles.
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchKnowledgebases(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getPublishedKnowledgebases([
            'search' => $query
        ], $perPage);
    }

    /**
     * Get knowledgebase article by slug.
     *
     * @param string $slug
     * @return Knowledgebase|null
     */
    public function getKnowledgebaseBySlug(string $slug): ?Knowledgebase
    {
        return Knowledgebase::where('slug', $slug)
            ->where('status', true)
            ->with(['category', 'metainfo'])
            ->first();
    }

    /**
     * Get popular knowledgebase articles.
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopularKnowledgebases(int $limit = 4): Collection
    {
        return Knowledgebase::where('status', true)
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->with(['category'])
            ->get();
    }

    /**
     * Get recent knowledgebase articles.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentKnowledgebases(int $limit = 4): Collection
    {
        return Knowledgebase::where('status', true)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->with(['category'])
            ->get();
    }

    /**
     * Create a new knowledgebase article.
     *
     * @param array<string, mixed> $data
     * @return Knowledgebase
     */
    public function createKnowledgebase(array $data): Knowledgebase
    {
        return DB::transaction(function () use ($data) {
            // Generate slug if not provided
            $slug = $data['slug'] ?? Str::slug($data['title']);
            $slug = $this->ensureUniqueSlug($slug);

            $knowledgebase = Knowledgebase::create([
                'title' => SanitizeInput::esc_html($data['title']),
                'slug' => $slug,
                'description' => $data['description'],
                'category_id' => $data['category_id'] ?? null,
                'image' => $data['image'] ?? null,
                'files' => $data['files'] ?? null,
                'views' => 0,
                'status' => $data['status'] ?? false,
            ]);

            // Create meta info if provided
            if (!empty($data['meta_title']) || !empty($data['meta_description'])) {
                $knowledgebase->metainfo()->create([
                    'meta_title' => $data['meta_title'] ?? null,
                    'meta_description' => $data['meta_description'] ?? null,
                    'meta_tags' => $data['meta_tags'] ?? null,
                ]);
            }

            return $knowledgebase->load(['category', 'metainfo']);
        });
    }

    /**
     * Update an existing knowledgebase article.
     *
     * @param Knowledgebase $knowledgebase
     * @param array<string, mixed> $data
     * @return Knowledgebase
     */
    public function updateKnowledgebase(Knowledgebase $knowledgebase, array $data): Knowledgebase
    {
        return DB::transaction(function () use ($knowledgebase, $data) {
            // Generate slug if title changed
            if (isset($data['title']) && $data['title'] !== $knowledgebase->title) {
                $slug = $data['slug'] ?? Str::slug($data['title']);
                $data['slug'] = $this->ensureUniqueSlug($slug, $knowledgebase->id);
            }

            // Sanitize inputs
            if (isset($data['title'])) {
                $data['title'] = SanitizeInput::esc_html($data['title']);
            }

            $knowledgebase->update($data);

            // Update meta info if provided
            if (isset($data['meta_title']) || isset($data['meta_description'])) {
                $metaData = [
                    'meta_title' => $data['meta_title'] ?? null,
                    'meta_description' => $data['meta_description'] ?? null,
                    'meta_tags' => $data['meta_tags'] ?? null,
                ];

                if ($knowledgebase->metainfo) {
                    $knowledgebase->metainfo->update($metaData);
                } else {
                    $knowledgebase->metainfo()->create($metaData);
                }
            }

            return $knowledgebase->load(['category', 'metainfo']);
        });
    }

    /**
     * Delete a knowledgebase article.
     *
     * @param Knowledgebase $knowledgebase
     * @return bool
     */
    public function deleteKnowledgebase(Knowledgebase $knowledgebase): bool
    {
        return DB::transaction(function () use ($knowledgebase) {
            $knowledgebase->metainfo()?->delete();
            return $knowledgebase->delete();
        });
    }

    /**
     * Increment view count for a knowledgebase article.
     *
     * @param Knowledgebase $knowledgebase
     * @return void
     */
    public function incrementViews(Knowledgebase $knowledgebase): void
    {
        $knowledgebase->increment('views');
    }

    /**
     * Clone an existing knowledgebase article.
     *
     * @param Knowledgebase $knowledgebase
     * @return Knowledgebase
     */
    public function cloneKnowledgebase(Knowledgebase $knowledgebase): Knowledgebase
    {
        return DB::transaction(function () use ($knowledgebase) {
            $clone = $knowledgebase->replicate();
            
            // Set status to 0 (draft) for cloned article
            $clone->status = false;
            
            // Generate new unique slug
            $clone->slug = $this->ensureUniqueSlug($knowledgebase->slug . '-copy');
            
            // Reset views counter
            $clone->views = 0;
            
            $clone->save();

            // Clone meta info if exists
            if ($knowledgebase->metainfo) {
                $metaClone = $knowledgebase->metainfo->replicate();
                $clone->metainfo()->save($metaClone);
            }

            return $clone->load(['category', 'metainfo']);
        });
    }

    /**
     * Get related knowledgebase articles (same category).
     *
     * @param Knowledgebase $knowledgebase
     * @param int $limit
     * @return Collection
     */
    public function getRelatedKnowledgebases(Knowledgebase $knowledgebase, int $limit = 4): Collection
    {
        return Knowledgebase::where('category_id', $knowledgebase->category_id)
            ->where('id', '!=', $knowledgebase->id)
            ->where('status', true)
            ->with(['category'])
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Ensure unique slug for knowledgebase article.
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
            $query = Knowledgebase::where('slug', $slug);
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
