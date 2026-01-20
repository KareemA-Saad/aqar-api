<?php

declare(strict_types=1);

namespace Modules\RealEstate\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PropertyImage Model
 *
 * Represents images for properties.
 *
 * @package Modules\RealEstate\Entities
 *
 * @property int $id
 * @property int $property_id
 * @property string $image_path
 * @property string|null $title
 * @property string|null $alt_text
 * @property int $order
 * @property bool $is_primary
 */
class PropertyImage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 're_property_images';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'property_id',
        'image_path',
        'title',
        'alt_text',
        'order',
        'is_primary',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'order' => 'integer',
        'is_primary' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the property this image belongs to.
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to get primary image.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope ordered by position.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get image URL.
     */
    public function getImageUrlAttribute(): string
    {
        if (filter_var($this->image_path, FILTER_VALIDATE_URL)) {
            return $this->image_path;
        }

        return asset('storage/' . $this->image_path);
    }

    /**
     * Get thumbnail URL.
     */
    public function getThumbnailUrlAttribute(): string
    {
        // Try to get thumbnail version
        $path = $this->image_path;
        $thumbnailPath = str_replace('.', '_thumb.', $path);
        
        $thumbnailFullPath = storage_path('app/public/' . $thumbnailPath);
        
        if (file_exists($thumbnailFullPath)) {
            return asset('storage/' . $thumbnailPath);
        }

        return $this->image_url;
    }
}
