<?php

declare(strict_types=1);

namespace Modules\Service\Services;

use App\Helpers\SanitizeInput;
use App\Models\MetaInfo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Service\Entities\Service;
use Modules\Service\Entities\ServiceCategory;

/**
 * Service class for managing services.
 */
final class ServiceService
{
    /**
     * Get paginated list of services with optional filters.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getServices(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Service::query()->with(['category', 'metainfo']);

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('price_plan', 'like', "%{$search}%");
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

        // Price range filter
        if (isset($filters['min_price'])) {
            $query->where('price_plan', '>=', $filters['min_price']);
        }
        if (isset($filters['max_price'])) {
            $query->where('price_plan', '<=', $filters['max_price']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['title', 'price_plan', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get published services for public display.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPublishedServices(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['status'] = true;
        return $this->getServices($filters, $perPage);
    }

    /**
     * Get services by category.
     *
     * @param int $categoryId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getServicesByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getPublishedServices([
            'category_id' => $categoryId
        ], $perPage);
    }

    /**
     * Search services.
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchServices(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->getPublishedServices([
            'search' => $query
        ], $perPage);
    }

    /**
     * Get service by slug.
     *
     * @param string $slug
     * @return Service|null
     */
    public function getServiceBySlug(string $slug): ?Service
    {
        return Service::where('slug', $slug)
            ->where('status', true)
            ->with(['category', 'metainfo'])
            ->first();
    }

    /**
     * Create a new service.
     *
     * @param array<string, mixed> $data
     * @return Service
     */
    public function createService(array $data): Service
    {
        return DB::transaction(function () use ($data) {
            // Generate slug if not provided
            $slug = $data['slug'] ?? Str::slug($data['title']);
            $slug = $this->ensureUniqueSlug($slug);

            $service = Service::create([
                'title' => SanitizeInput::esc_html($data['title']),
                'slug' => $slug,
                'description' => $data['description'],
                'category_id' => $data['category_id'] ?? null,
                'price_plan' => $data['price_plan'] ?? null,
                'image' => $data['image'] ?? null,
                'status' => $data['status'] ?? false,
                'meta_tag' => $data['meta_tag'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
            ]);

            // Create meta info if provided
            if (!empty($data['meta_title']) || !empty($data['meta_description'])) {
                $service->metainfo()->create([
                    'meta_title' => $data['meta_title'] ?? null,
                    'meta_description' => $data['meta_description'] ?? null,
                    'meta_tags' => $data['meta_tags'] ?? null,
                ]);
            }

            return $service->load(['category', 'metainfo']);
        });
    }

    /**
     * Update an existing service.
     *
     * @param Service $service
     * @param array<string, mixed> $data
     * @return Service
     */
    public function updateService(Service $service, array $data): Service
    {
        return DB::transaction(function () use ($service, $data) {
            // Generate slug if title changed
            if (isset($data['title']) && $data['title'] !== $service->title) {
                $slug = $data['slug'] ?? Str::slug($data['title']);
                $data['slug'] = $this->ensureUniqueSlug($slug, $service->id);
            }

            // Sanitize inputs
            if (isset($data['title'])) {
                $data['title'] = SanitizeInput::esc_html($data['title']);
            }

            $service->update($data);

            // Update meta info if provided
            if (isset($data['meta_title']) || isset($data['meta_description'])) {
                $metaData = [
                    'meta_title' => $data['meta_title'] ?? null,
                    'meta_description' => $data['meta_description'] ?? null,
                    'meta_tags' => $data['meta_tags'] ?? null,
                ];

                if ($service->metainfo) {
                    $service->metainfo->update($metaData);
                } else {
                    $service->metainfo()->create($metaData);
                }
            }

            return $service->load(['category', 'metainfo']);
        });
    }

    /**
     * Delete a service.
     *
     * @param Service $service
     * @return bool
     */
    public function deleteService(Service $service): bool
    {
        return DB::transaction(function () use ($service) {
            $service->metainfo()?->delete();
            return $service->delete();
        });
    }

    /**
     * Get related services (same category).
     *
     * @param Service $service
     * @param int $limit
     * @return Collection
     */
    public function getRelatedServices(Service $service, int $limit = 2): Collection
    {
        return Service::where('category_id', $service->category_id)
            ->where('id', '!=', $service->id)
            ->where('status', true)
            ->with(['category'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Ensure unique slug for service.
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
            $query = Service::where('slug', $slug);
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
