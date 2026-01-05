<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\MediaUploader;
use Illuminate\Support\Facades\Cache;

/**
 * Media Helper
 *
 * Provides global helper functions for media operations.
 * These functions can be used throughout the application for
 * retrieving media URLs, information, and formatting.
 */
final class MediaHelper
{
    /**
     * Cache TTL for media queries in seconds.
     */
    private const CACHE_TTL = 300;

    /**
     * Get attachment image by ID with optional size.
     *
     * Returns an array with image details including URL, alt text, and path.
     *
     * @param int $id Media ID
     * @param string|null $size Size variant (thumb, grid, large, or null for original)
     * @param bool $default Return default image if not found
     * @return array{image_id?: int, path?: string, img_url: string, img_alt: string|null}
     */
    public static function getAttachmentImageById(int $id, ?string $size = null, bool $default = false): array
    {
        $cacheKey = "media_image_{$id}_{$size}";

        $imageDetails = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return MediaUploader::find($id);
        });

        $returnVal = [];
        $imageUrl = '';

        if (!empty($id) && !empty($imageDetails)) {
            $basePath = dirname($imageDetails->path);
            $fileName = basename($imageDetails->path);

            // Get the base URL
            $baseUrl = asset('storage/' . ltrim($basePath, '/'));
            $imageUrl = $baseUrl . '/' . $fileName;

            // Handle different sizes
            if ($size !== null && $imageDetails->is_image) {
                $sizePath = $basePath . '/' . $size . '/' . $size . '-' . $fileName;
                $fullSizePath = public_path('storage/' . ltrim($sizePath, '/'));

                if (file_exists($fullSizePath)) {
                    $imageUrl = asset('storage/' . ltrim($sizePath, '/'));
                }
            }

            $returnVal['image_id'] = $imageDetails->id;
            $returnVal['path'] = $imageDetails->path;
            $returnVal['img_url'] = $imageUrl;
            $returnVal['img_alt'] = $imageDetails->alt;
        } elseif (empty($imageDetails) && $default) {
            $returnVal['img_url'] = asset('no-image.jpeg');
            $returnVal['img_alt'] = '';
        }

        return $returnVal;
    }

    /**
     * Get media URL by ID and optional size.
     *
     * @param int $id Media ID
     * @param string|null $size Size variant (thumb, grid, large, or null for original)
     * @return string|null URL or null if not found
     */
    public static function getMediaUrl(int $id, ?string $size = null): ?string
    {
        $imageDetails = self::getAttachmentImageById($id, $size);

        return $imageDetails['img_url'] ?? null;
    }

    /**
     * Format file size to human-readable string.
     *
     * @param int $bytes File size in bytes
     * @param int $precision Decimal precision
     * @return string Formatted size (e.g., "1.5 MB")
     */
    public static function formatFileSize(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $index = min(floor($base), count($units) - 1);

        return round(1024 ** ($base - floor($base)), $precision) . ' ' . $units[$index];
    }

    /**
     * Get all size URLs for a media item.
     *
     * @param int $id Media ID
     * @return array<string, string|null>
     */
    public static function getAllSizeUrls(int $id): array
    {
        $media = MediaUploader::find($id);

        if (!$media) {
            return [];
        }

        $urls = [
            'original' => self::getMediaUrl($id),
        ];

        if ($media->is_image) {
            $sizes = array_keys(config('media.image_sizes', []));

            foreach ($sizes as $size) {
                $urls[$size] = self::getMediaUrl($id, $size);
            }
        }

        return $urls;
    }

    /**
     * Check if a media file exists.
     *
     * @param int $id Media ID
     * @param string|null $size Size variant
     * @return bool
     */
    public static function mediaExists(int $id, ?string $size = null): bool
    {
        $media = MediaUploader::find($id);

        if (!$media) {
            return false;
        }

        $path = $media->getFilePath($size);
        $fullPath = public_path('storage/' . ltrim($path, '/'));

        return file_exists($fullPath);
    }

    /**
     * Get media info by ID.
     *
     * @param int $id Media ID
     * @return array{id: int, title: string, alt: string|null, extension: string, is_image: bool, size: string, dimensions: string|null, urls: array}|null
     */
    public static function getMediaInfo(int $id): ?array
    {
        $media = MediaUploader::find($id);

        if (!$media) {
            return null;
        }

        return [
            'id' => $media->id,
            'title' => $media->title,
            'alt' => $media->alt,
            'extension' => $media->extension,
            'is_image' => $media->is_image,
            'size' => $media->human_size,
            'dimensions' => $media->dimensions,
            'urls' => self::getAllSizeUrls($id),
        ];
    }

    /**
     * Clear media cache for a specific ID.
     *
     * @param int $id Media ID
     */
    public static function clearCache(int $id): void
    {
        $sizes = array_merge([null], array_keys(config('media.image_sizes', [])));

        foreach ($sizes as $size) {
            $cacheKey = "media_image_{$id}_{$size}";
            Cache::forget($cacheKey);
        }
    }

    /**
     * Render image markup by attachment ID.
     *
     * @param int $id Media ID
     * @param string|null $size Size variant
     * @param string $class CSS classes
     * @param string|null $customAlt Custom alt text
     * @return string HTML img tag
     */
    public static function renderImageMarkup(
        int $id,
        ?string $size = null,
        string $class = '',
        ?string $customAlt = null
    ): string {
        $imageData = self::getAttachmentImageById($id, $size, true);

        if (empty($imageData['img_url'])) {
            return '';
        }

        $alt = $customAlt ?? $imageData['img_alt'] ?? '';
        $classAttr = $class ? ' class="' . htmlspecialchars($class) . '"' : '';

        return sprintf(
            '<img src="%s" alt="%s"%s>',
            htmlspecialchars($imageData['img_url']),
            htmlspecialchars($alt),
            $classAttr
        );
    }

    /**
     * Get default image URL.
     *
     * @return string
     */
    public static function getDefaultImageUrl(): string
    {
        return asset('no-image.jpeg');
    }
}
