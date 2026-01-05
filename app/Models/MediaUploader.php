<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * MediaUploader Model
 *
 * Represents uploaded media files for both landlord and tenant contexts.
 *
 * @property int $id
 * @property string $title
 * @property string $path
 * @property string|null $alt
 * @property string|null $size
 * @property int $user_type 0 = admin, 1 = user
 * @property int $user_id
 * @property string|null $dimensions
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class MediaUploader extends Model
{
    use HasFactory;

    /**
     * User type constants.
     */
    public const USER_TYPE_ADMIN = 0;
    public const USER_TYPE_USER = 1;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'alt',
        'size',
        'path',
        'dimensions',
        'user_type',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_type' => 'integer',
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admin who uploaded the media.
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'user_id')
            ->when($this->user_type !== self::USER_TYPE_ADMIN, fn ($q) => $q->whereRaw('1=0'));
    }

    /**
     * Get the user who uploaded the media.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')
            ->when($this->user_type !== self::USER_TYPE_USER, fn ($q) => $q->whereRaw('1=0'));
    }

    /**
     * Scope to filter by user type.
     */
    public function scopeByUserType(Builder $query, int $userType): Builder
    {
        return $query->where('user_type', $userType);
    }

    /**
     * Scope to filter by admin uploads.
     */
    public function scopeAdminUploads(Builder $query): Builder
    {
        return $query->where('user_type', self::USER_TYPE_ADMIN);
    }

    /**
     * Scope to filter by user uploads.
     */
    public function scopeUserUploads(Builder $query): Builder
    {
        return $query->where('user_type', self::USER_TYPE_USER);
    }

    /**
     * Scope to filter by owner.
     */
    public function scopeOwnedBy(Builder $query, int $userId, int $userType): Builder
    {
        return $query->where('user_id', $userId)
            ->where('user_type', $userType);
    }

    /**
     * Scope to filter by file type (image, document, etc.).
     */
    public function scopeByFileType(Builder $query, string $type): Builder
    {
        $imageExtensions = config('media.image_extensions', []);

        return match ($type) {
            'image' => $query->where(function ($q) use ($imageExtensions) {
                foreach ($imageExtensions as $ext) {
                    $q->orWhere('path', 'LIKE', "%.$ext");
                }
            }),
            'document' => $query->where(function ($q) use ($imageExtensions) {
                foreach ($imageExtensions as $ext) {
                    $q->where('path', 'NOT LIKE', "%.$ext");
                }
            }),
            default => $query,
        };
    }

    /**
     * Scope to search by title or alt text.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'LIKE', "%{$term}%")
                ->orWhere('alt', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Get the file extension.
     */
    public function getExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
    }

    /**
     * Check if the file is an image.
     */
    public function getIsImageAttribute(): bool
    {
        $imageExtensions = config('media.image_extensions', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

        return in_array($this->extension, $imageExtensions);
    }

    /**
     * Get file size in human-readable format.
     */
    public function getHumanSizeAttribute(): string
    {
        if (empty($this->size) || !is_numeric($this->size)) {
            // Try to parse the size if stored as human-readable string
            return (string) $this->size;
        }

        $bytes = (int) $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2) . ' ' . $units[$index];
    }

    /**
     * Get the file's MIME type based on extension.
     */
    public function getMimeTypeAttribute(): string
    {
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'avif' => 'image/avif',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            'zip' => 'application/zip',
            'csv' => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        return $mimeTypes[$this->extension] ?? 'application/octet-stream';
    }

    /**
     * Check if the file exists on disk.
     */
    public function existsOnDisk(?string $size = null): bool
    {
        $path = $this->getFilePath($size);

        return Storage::disk(config('media.storage_disk', 'public'))->exists($path);
    }

    /**
     * Get the file path for a specific size.
     */
    public function getFilePath(?string $size = null): string
    {
        $basePath = dirname($this->path);

        if ($size === null || !$this->is_image) {
            return $this->path;
        }

        return $basePath . '/' . $size . '/' . $size . '-' . basename($this->path);
    }
}

