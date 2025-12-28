<?php

declare(strict_types=1);

namespace Modules\Blog\Services;

use App\Helpers\SanitizeInput;
use App\Models\MetaInfo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Blog\Entities\Blog;
use Modules\Blog\Entities\BlogCategory;
use Modules\Blog\Entities\BlogComment;

/**
 * Service class for managing blog posts.
 *
 * Handles blog CRUD operations, view tracking, and related content retrieval.
 */
final class BlogService
{
    /**
     * Get paginated list of blog posts with optional filters.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPosts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Blog::query()->with(['category', 'metainfo'])->withCount('comments');

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('blog_content', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
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

        // Featured filter
        if (isset($filters['featured'])) {
            $query->where('featured', (bool) $filters['featured']);
        }

        // Author filter (admin_id or user_id)
        if (!empty($filters['author_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('admin_id', $filters['author_id'])
                    ->orWhere('user_id', $filters['author_id']);
            });
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $allowedSorts = ['title', 'views', 'created_at', 'updated_at', 'status'];
        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get published blog posts for public display.
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPublishedPosts(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $filters['status'] = true;
        return $this->getPosts($filters, $perPage);
    }

    /**
     * Create a new blog post.
     *
     * @param array<string, mixed> $data
     * @return Blog
     */
    public function createPost(array $data): Blog
    {
        return DB::transaction(function () use ($data) {
            // Generate slug if not provided
            $slug = $data['slug'] ?? Str::slug($data['title']);
            $slug = $this->ensureUniqueSlug($slug);

            // Determine author
            $adminId = null;
            $userId = null;
            $createdBy = 'admin';

            if (Auth::guard('api_admin')->check()) {
                $adminId = Auth::guard('api_admin')->id();
                $createdBy = 'admin';
            } elseif (Auth::guard('api_user')->check()) {
                $userId = Auth::guard('api_user')->id();
                $createdBy = 'user';
            } elseif (Auth::guard('api_tenant_user')->check()) {
                $userId = Auth::guard('api_tenant_user')->id();
                $createdBy = 'user';
            }

            $blog = Blog::create([
                'title' => SanitizeInput::esc_html($data['title']),
                'slug' => $slug,
                'blog_content' => $data['blog_content'],
                'excerpt' => isset($data['excerpt']) ? SanitizeInput::esc_html($data['excerpt']) : null,
                'category_id' => $data['category_id'] ?? null,
                'image' => $data['image'] ?? null,
                'image_gallery' => $data['image_gallery'] ?? null,
                'video_url' => $data['video_url'] ?? null,
                'tags' => $data['tags'] ?? null,
                'status' => $data['status'] ?? false,
                'featured' => $data['featured'] ?? false,
                'visibility' => $data['visibility'] ?? 'public',
                'admin_id' => $adminId ?? 0,
                'user_id' => $userId ?? 0,
                'created_by' => $createdBy,
                'views' => 0,
            ]);

            // Create meta info if provided
            if (!empty($data['meta_title']) || !empty($data['meta_description']) || !empty($data['meta_keywords'])) {
                $blog->metainfo()->create([
                    'title' => $data['meta_title'] ?? null,
                    'description' => $data['meta_description'] ?? null,
                    'keywords' => $data['meta_keywords'] ?? null,
                ]);
            }

            return $blog->load(['category', 'metainfo']);
        });
    }

    /**
     * Update an existing blog post.
     *
     * @param Blog $blog
     * @param array<string, mixed> $data
     * @return Blog
     */
    public function updatePost(Blog $blog, array $data): Blog
    {
        return DB::transaction(function () use ($blog, $data) {
            $updateData = [];

            if (isset($data['title'])) {
                $updateData['title'] = SanitizeInput::esc_html($data['title']);
            }

            if (isset($data['slug'])) {
                $slug = $data['slug'];
                if ($slug !== $blog->slug) {
                    $slug = $this->ensureUniqueSlug($slug, $blog->id);
                }
                $updateData['slug'] = $slug;
            }

            if (isset($data['blog_content'])) {
                $updateData['blog_content'] = $data['blog_content'];
            }

            if (array_key_exists('excerpt', $data)) {
                $updateData['excerpt'] = $data['excerpt'] ? SanitizeInput::esc_html($data['excerpt']) : null;
            }

            if (array_key_exists('category_id', $data)) {
                $updateData['category_id'] = $data['category_id'];
            }

            if (array_key_exists('image', $data)) {
                $updateData['image'] = $data['image'];
            }

            if (array_key_exists('image_gallery', $data)) {
                $updateData['image_gallery'] = $data['image_gallery'];
            }

            if (array_key_exists('video_url', $data)) {
                $updateData['video_url'] = $data['video_url'];
            }

            if (array_key_exists('tags', $data)) {
                $updateData['tags'] = $data['tags'];
            }

            if (isset($data['status'])) {
                $updateData['status'] = $data['status'];
            }

            if (isset($data['featured'])) {
                $updateData['featured'] = $data['featured'];
            }

            if (array_key_exists('visibility', $data)) {
                $updateData['visibility'] = $data['visibility'];
            }

            $blog->update($updateData);

            // Update or create meta info
            if (isset($data['meta_title']) || isset($data['meta_description']) || isset($data['meta_keywords'])) {
                $metaData = [];
                if (isset($data['meta_title'])) {
                    $metaData['title'] = $data['meta_title'];
                }
                if (isset($data['meta_description'])) {
                    $metaData['description'] = $data['meta_description'];
                }
                if (isset($data['meta_keywords'])) {
                    $metaData['keywords'] = $data['meta_keywords'];
                }

                if ($blog->metainfo) {
                    $blog->metainfo->update($metaData);
                } else {
                    $blog->metainfo()->create($metaData);
                }
            }

            return $blog->fresh(['category', 'metainfo']);
        });
    }

    /**
     * Delete a blog post.
     *
     * @param Blog $blog
     * @return bool
     */
    public function deletePost(Blog $blog): bool
    {
        return DB::transaction(function () use ($blog) {
            // Delete meta info
            $blog->metainfo()->delete();

            // Delete comments
            $blog->comments()->delete();

            return $blog->delete();
        });
    }

    /**
     * Toggle blog post status.
     *
     * @param Blog $blog
     * @return Blog
     */
    public function toggleStatus(Blog $blog): Blog
    {
        $blog->update(['status' => !$blog->status]);
        return $blog->fresh();
    }

    /**
     * Increment view count for a blog post.
     *
     * @param Blog $blog
     * @return void
     */
    public function incrementViews(Blog $blog): void
    {
        $blog->increment('views');
    }

    /**
     * Get related blog posts based on category and tags.
     *
     * @param Blog $blog
     * @param int $limit
     * @return Collection
     */
    public function getRelatedPosts(Blog $blog, int $limit = 3): Collection
    {
        return Blog::query()
            ->where('id', '!=', $blog->id)
            ->where('status', true)
            ->where(function ($query) use ($blog) {
                $query->where('category_id', $blog->category_id);

                // Also match by tags if available
                if ($blog->tags) {
                    $tags = explode(',', $blog->tags);
                    foreach ($tags as $tag) {
                        $query->orWhere('tags', 'like', '%' . trim($tag) . '%');
                    }
                }
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'image', 'excerpt', 'created_at', 'category_id', 'views']);
    }

    /**
     * Get popular blog posts by view count.
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopularPosts(int $limit = 5): Collection
    {
        return Blog::query()
            ->where('status', true)
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'image', 'excerpt', 'created_at', 'category_id', 'views']);
    }

    /**
     * Get recent blog posts.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentPosts(int $limit = 5): Collection
    {
        return Blog::query()
            ->where('status', true)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'image', 'excerpt', 'created_at', 'category_id', 'views']);
    }

    /**
     * Get blog posts by category.
     *
     * @param int $categoryId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPostsByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return Blog::query()
            ->with(['category'])
            ->where('category_id', $categoryId)
            ->where('status', true)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get blog posts by tag.
     *
     * @param string $tag
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getPostsByTag(string $tag, int $perPage = 15): LengthAwarePaginator
    {
        return Blog::query()
            ->with(['category'])
            ->where('tags', 'like', '%' . $tag . '%')
            ->where('status', true)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Search blog posts.
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchPosts(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Blog::query()
            ->with(['category'])
            ->where('status', true)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', '%' . $query . '%')
                    ->orWhere('blog_content', 'like', '%' . $query . '%')
                    ->orWhere('excerpt', 'like', '%' . $query . '%')
                    ->orWhere('tags', 'like', '%' . $query . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Bulk action on blog posts.
     *
     * @param array<int> $ids
     * @param string $action
     * @return int Number of affected posts
     */
    public function bulkAction(array $ids, string $action): int
    {
        return match ($action) {
            'delete' => $this->bulkDelete($ids),
            'publish' => Blog::whereIn('id', $ids)->update(['status' => true]),
            'unpublish' => Blog::whereIn('id', $ids)->update(['status' => false]),
            default => 0,
        };
    }

    /**
     * Bulk delete blog posts.
     *
     * @param array<int> $ids
     * @return int
     */
    private function bulkDelete(array $ids): int
    {
        return DB::transaction(function () use ($ids) {
            // Delete meta info
            MetaInfo::where('metainfoable_type', Blog::class)
                ->whereIn('metainfoable_id', $ids)
                ->delete();

            // Delete comments
            BlogComment::whereIn('blog_id', $ids)->delete();

            // Delete blogs
            return Blog::whereIn('id', $ids)->delete();
        });
    }

    /**
     * Ensure the slug is unique.
     *
     * @param string $slug
     * @param int|null $excludeId
     * @return string
     */
    private function ensureUniqueSlug(string $slug, ?int $excludeId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while (true) {
            $query = Blog::where('slug', $slug);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Get blog post by slug for public display.
     *
     * @param string $slug
     * @return Blog|null
     */
    public function getPostBySlug(string $slug): ?Blog
    {
        return Blog::query()
            ->with(['category', 'metainfo'])
            ->withCount('comments')
            ->where('slug', $slug)
            ->where('status', true)
            ->first();
    }

    /**
     * Get comments for a blog post.
     *
     * @param int $blogId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getComments(int $blogId, int $perPage = 10): LengthAwarePaginator
    {
        return BlogComment::query()
            ->with(['user', 'comment_replay'])
            ->where('blog_id', $blogId)
            ->whereNull('parent_id')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a comment on a blog post.
     *
     * @param int $blogId
     * @param array<string, mixed> $data
     * @return BlogComment
     */
    public function createComment(int $blogId, array $data): BlogComment
    {
        $userId = Auth::guard('api_tenant_user')->id() ?? Auth::guard('api_user')->id();
        $commentedBy = Auth::guard('api_admin')->check() ? 'admin' : 'user';

        return BlogComment::create([
            'blog_id' => $blogId,
            'user_id' => $userId ?? 0,
            'parent_id' => $data['parent_id'] ?? null,
            'commented_by' => $commentedBy,
            'comment_content' => SanitizeInput::esc_html($data['comment_content']),
        ]);
    }
}
