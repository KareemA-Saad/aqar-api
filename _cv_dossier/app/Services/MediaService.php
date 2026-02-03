<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MediaUploader;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Stancl\Tenancy\Tenancy;

/**
 * Media Service
 *
 * Handles file uploads, thumbnail generation, URL management,
 * and storage validation for the media system.
 */
final class MediaService
{
    /**
     * Cache TTL for media queries in seconds.
     */
    private const CACHE_TTL = 300;

    public function __construct(
        private readonly Tenancy $tenancy,
    ) {}

    /**
     * Upload a single file.
     *
     * @param UploadedFile $file
     * @param int $userId
     * @param int $userType
     * @param string|null $folder
     * @param string|null $altText
     * @return MediaUploader
     * @throws \Exception
     */
    public function upload(
        UploadedFile $file,
        int $userId,
        int $userType,
        ?string $folder = null,
        ?string $altText = null
    ): MediaUploader {
        return DB::transaction(function () use ($file, $userId, $userType, $folder, $altText) {
            // Validate storage limit for tenants
            if ($this->isInTenantContext()) {
                $fileSize = $file->getSize();
                if (!$this->validateStorageLimit($fileSize)) {
                    throw new \Exception('Storage limit exceeded. Please upgrade your plan or delete some files.');
                }
            }

            $extension = strtolower($file->getClientOriginalExtension());
            $originalName = $file->getClientOriginalName();
            $fileName = $this->generateFileName($originalName, $extension);
            $storagePath = $this->getStoragePath($folder);

            // Create storage directories
            $this->ensureDirectoryExists($storagePath);

            // Upload the original file
            $file->move($this->getFullStoragePath($storagePath), $fileName);

            // Prepare media data
            $mediaData = [
                'title' => $originalName,
                'alt' => $altText,
                'path' => $storagePath . '/' . $fileName,
                'user_type' => $userType,
                'user_id' => $userId,
                'size' => null,
                'dimensions' => null,
            ];

            // Handle image-specific processing
            if ($this->isImageFile($extension)) {
                $fullPath = $this->getFullStoragePath($storagePath . '/' . $fileName);
                $imageInfo = $this->getImageInfo($fullPath);
                $mediaData['dimensions'] = $imageInfo['dimensions'];
                $mediaData['size'] = $this->formatFileSize($imageInfo['size']);

                // Create media record first
                $media = MediaUploader::create($mediaData);

                // Generate thumbnails
                $this->generateThumbnails($media, $fullPath, $storagePath, $fileName);

                return $media;
            }

            // Non-image files
            $fullPath = $this->getFullStoragePath($storagePath . '/' . $fileName);
            $mediaData['size'] = $this->formatFileSize(filesize($fullPath) ?: 0);

            return MediaUploader::create($mediaData);
        });
    }

    /**
     * Upload multiple files.
     *
     * @param array<UploadedFile> $files
     * @param int $userId
     * @param int $userType
     * @param string|null $folder
     * @param string|null $altText
     * @return array<MediaUploader>
     */
    public function uploadMultiple(
        array $files,
        int $userId,
        int $userType,
        ?string $folder = null,
        ?string $altText = null
    ): array {
        $uploadedMedia = [];

        foreach ($files as $file) {
            try {
                $uploadedMedia[] = $this->upload($file, $userId, $userType, $folder, $altText);
            } catch (\Exception $e) {
                Log::error('Media upload failed', [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ]);
                // Continue with other files
            }
        }

        return $uploadedMedia;
    }

    /**
     * Generate thumbnails for an image.
     *
     * @param MediaUploader $media
     * @param string $sourcePath
     * @param string $storagePath
     * @param string $fileName
     */
    public function generateThumbnails(
        MediaUploader $media,
        string $sourcePath,
        string $storagePath,
        string $fileName
    ): void {
        $imageSizes = config('media.image_sizes', []);

        // Get image dimensions to check if thumbnails are needed
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return;
        }

        $imageWidth = $imageInfo[0];

        // Create ImageManager with GD driver
        $manager = new ImageManager(new Driver());

        foreach ($imageSizes as $sizeName => $dimensions) {
            $sizeDir = $this->getFullStoragePath($storagePath . '/' . $sizeName);
            $this->ensureDirectoryExists($storagePath . '/' . $sizeName);

            $sizeFileName = $sizeName . '-' . $fileName;
            $targetPath = $sizeDir . '/' . $sizeFileName;

            try {
                [$width, $height] = $dimensions;

                // Only create thumbnail if image is larger than target size
                if ($width !== null && $imageWidth > $width) {
                    $image = $manager->read($sourcePath);

                    if ($height !== null) {
                        // Fixed dimensions with cover (e.g., thumb)
                        $image->cover($width, $height);
                    } else {
                        // Proportional scale (e.g., grid, large)
                        $image->scale(width: $width);
                    }

                    $image->toJpeg(config('media.quality.jpeg', 85))->save($targetPath);
                }
            } catch (\Exception $e) {
                Log::error('Thumbnail generation failed', [
                    'media_id' => $media->id,
                    'size' => $sizeName,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Get media URL by ID and optional size.
     *
     * @param int $id
     * @param string|null $size
     * @return string|null
     */
    public function getMediaUrl(int $id, ?string $size = null): ?string
    {
        $media = MediaUploader::find($id);

        if (!$media) {
            return null;
        }

        return $this->getUrlForMedia($media, $size);
    }

    /**
     * Get URL for a media object.
     *
     * @param MediaUploader $media
     * @param string|null $size
     * @return string
     */
    public function getUrlForMedia(MediaUploader $media, ?string $size = null): string
    {
        $basePath = dirname($media->path);
        $fileName = basename($media->path);

        // For non-images or original size, return the original URL
        if (!$media->is_image || $size === null) {
            return $this->buildUrl($media->path);
        }

        // Check if the sized version exists
        $sizePath = $basePath . '/' . $size . '/' . $size . '-' . $fileName;
        $fullSizePath = $this->getFullStoragePath($sizePath);

        if (file_exists($fullSizePath)) {
            return $this->buildUrl($sizePath);
        }

        // Fall back to original
        return $this->buildUrl($media->path);
    }

    /**
     * Delete media and its thumbnails.
     *
     * @param MediaUploader $media
     * @return bool
     */
    public function deleteMedia(MediaUploader $media): bool
    {
        return DB::transaction(function () use ($media) {
            // Delete files from storage
            $this->deleteMediaFiles($media);

            // Delete database record
            return $media->delete();
        });
    }

    /**
     * Bulk delete media files.
     *
     * @param array<int> $ids
     * @return array{deleted: int, failed: int}
     */
    public function bulkDelete(array $ids): array
    {
        $deleted = 0;
        $failed = 0;

        foreach ($ids as $id) {
            $media = MediaUploader::find($id);

            if ($media) {
                try {
                    $this->deleteMedia($media);
                    $deleted++;
                } catch (\Exception $e) {
                    Log::error('Bulk delete failed', [
                        'media_id' => $id,
                        'error' => $e->getMessage(),
                    ]);
                    $failed++;
                }
            } else {
                $failed++;
            }
        }

        return ['deleted' => $deleted, 'failed' => $failed];
    }

    /**
     * Get the storage path based on context.
     *
     * @param string|null $customFolder
     * @return string
     */
    public function getStoragePath(?string $customFolder = null): string
    {
        if ($this->isInTenantContext()) {
            $tenantId = $this->getCurrentTenantId();
            $basePath = str_replace('{tenant_id}', $tenantId, config('media.paths.tenant'));
        } else {
            $basePath = config('media.paths.landlord');
        }

        if ($customFolder) {
            $basePath .= '/' . trim($customFolder, '/');
        }

        return $basePath;
    }

    /**
     * Validate storage limit for tenant.
     *
     * @param int $fileSize Size in bytes
     * @return bool
     */
    public function validateStorageLimit(int $fileSize): bool
    {
        if (!$this->isInTenantContext()) {
            return true;
        }

        $tenant = $this->getCurrentTenant();

        if (!$tenant) {
            return true;
        }

        // Get storage limit from tenant's package
        $storageLimit = $this->getTenantStorageLimit($tenant);

        // -1 means unlimited
        if ($storageLimit === -1) {
            return true;
        }

        // Convert limit from MB to bytes
        $storageLimitBytes = $storageLimit * 1024 * 1024;

        // Get current storage usage
        $currentUsage = $this->getTenantStorageUsage($tenant);

        return ($currentUsage + $fileSize) <= $storageLimitBytes;
    }

    /**
     * Get tenant's current storage usage in bytes.
     *
     * @param Tenant $tenant
     * @return int
     */
    public function getTenantStorageUsage(Tenant $tenant): int
    {
        $tenantPath = str_replace('{tenant_id}', $tenant->id, config('media.paths.tenant'));
        $disk = config('media.storage_disk', 'public');

        $totalSize = 0;

        try {
            $files = Storage::disk($disk)->allFiles($tenantPath);

            foreach ($files as $file) {
                if (basename($file) === '.DS_Store') {
                    continue;
                }

                $totalSize += Storage::disk($disk)->size($file);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to calculate storage usage', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $totalSize;
    }

    /**
     * Get tenant's storage limit in MB.
     *
     * @param Tenant $tenant
     * @return int
     */
    private function getTenantStorageLimit(Tenant $tenant): int
    {
        $paymentLog = $tenant->paymentLog;

        if (!$paymentLog || !$paymentLog->package) {
            return 100; // Default 100 MB
        }

        return (int) ($paymentLog->package->storage_permission_feature ?? 100);
    }

    /**
     * Update media metadata.
     *
     * @param MediaUploader $media
     * @param array<string, mixed> $data
     * @return MediaUploader
     */
    public function updateMedia(MediaUploader $media, array $data): MediaUploader
    {
        $media->update(array_filter([
            'title' => $data['title'] ?? null,
            'alt' => $data['alt'] ?? null,
        ], fn ($value) => $value !== null));

        return $media->fresh();
    }

    /**
     * Check if currently in tenant context.
     */
    private function isInTenantContext(): bool
    {
        return $this->tenancy->initialized;
    }

    /**
     * Get current tenant.
     */
    private function getCurrentTenant(): ?Tenant
    {
        if (!$this->isInTenantContext()) {
            return null;
        }

        $tenant = $this->tenancy->tenant;

        return $tenant instanceof Tenant ? $tenant : null;
    }

    /**
     * Get current tenant ID.
     */
    private function getCurrentTenantId(): string
    {
        return $this->getCurrentTenant()?->id ?? '';
    }

    /**
     * Generate unique filename.
     */
    private function generateFileName(string $originalName, string $extension): string
    {
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $slug = Str::slug($name);

        return $slug . '-' . time() . '.' . $extension;
    }

    /**
     * Check if file is an image.
     */
    private function isImageFile(string $extension): bool
    {
        return in_array(
            strtolower($extension),
            config('media.image_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp'])
        );
    }

    /**
     * Get image information.
     *
     * @param string $path
     * @return array{dimensions: string, size: int}
     */
    private function getImageInfo(string $path): array
    {
        $imageInfo = getimagesize($path);
        $fileSize = filesize($path) ?: 0;

        $dimensions = '';
        if ($imageInfo) {
            $dimensions = $imageInfo[0] . ' x ' . $imageInfo[1] . ' pixels';
        }

        return [
            'dimensions' => $dimensions,
            'size' => $fileSize,
        ];
    }

    /**
     * Format file size to human-readable format.
     */
    public function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }

    /**
     * Get full storage path (with public_path prefix).
     */
    private function getFullStoragePath(string $path): string
    {
        return public_path('storage/' . ltrim($path, '/'));
    }

    /**
     * Ensure directory exists.
     */
    private function ensureDirectoryExists(string $path): void
    {
        $fullPath = $this->getFullStoragePath($path);

        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
    }

    /**
     * Build URL from path.
     */
    private function buildUrl(string $path): string
    {
        return asset('storage/' . ltrim($path, '/'));
    }

    /**
     * Delete media files from storage.
     */
    private function deleteMediaFiles(MediaUploader $media): void
    {
        $basePath = dirname($media->path);
        $fileName = basename($media->path);

        // Delete original file
        $originalPath = $this->getFullStoragePath($media->path);
        if (file_exists($originalPath)) {
            unlink($originalPath);
        }

        // Delete thumbnails if it's an image
        if ($media->is_image) {
            $sizes = array_keys(config('media.image_sizes', []));

            foreach ($sizes as $size) {
                $sizePath = $this->getFullStoragePath(
                    $basePath . '/' . $size . '/' . $size . '-' . $fileName
                );

                if (file_exists($sizePath)) {
                    unlink($sizePath);
                }
            }
        }
    }
}
