<?php

declare(strict_types=1);

namespace Modules\RealEstate\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CompoundImage Model
 *
 * Represents images for compounds (gallery, master plan, unit plans, etc.)
 *
 * @package Modules\RealEstate\Entities
 *
 * @property int $id
 * @property int $compound_id
 * @property string $image_path
 * @property string|null $title
 * @property string|null $alt_text
 * @property string $type (gallery, master_plan, unit_plan, location_map)
 * @property int $order
 */
class CompoundImage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 're_compound_images';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'compound_id',
        'image_path',
        'title',
        'alt_text',
        'type',
        'order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'order' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the compound this image belongs to.
     */
    public function compound(): BelongsTo
    {
        return $this->belongsTo(Compound::class, 'compound_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope by image type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get gallery images.
     */
    public function scopeGallery($query)
    {
        return $query->where('type', 'gallery');
    }

    /**
     * Scope to get master plan images.
     */
    public function scopeMasterPlan($query)
    {
        return $query->where('type', 'master_plan');
    }

    /**
     * Scope to get unit plan images.
     */
    public function scopeUnitPlan($query)
    {
        return $query->where('type', 'unit_plan');
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
