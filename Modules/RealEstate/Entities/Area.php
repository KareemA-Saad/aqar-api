<?php

declare(strict_types=1);

namespace Modules\RealEstate\Entities;

use App\Models\MetaInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/**
 * Area Model (Locations/Super Areas)
 *
 * Represents geographical regions in a hierarchical structure:
 * Super Area (e.g., New Cairo) -> Area (e.g., 6th Settlement) -> Sub Area
 *
 * @package Modules\RealEstate\Entities
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $type (super_area, area, sub_area)
 * @property int $order
 * @property bool $status
 * @property bool $is_featured
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $image
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $meta_keywords
 * @property int $compounds_count
 * @property int $properties_count
 */
class Area extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 're_areas';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'type',
        'order',
        'status',
        'is_featured',
        'latitude',
        'longitude',
        'image',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'compounds_count',
        'properties_count',
    ];

    /**
     * Translatable attributes.
     */
    public array $translatable = ['name', 'description', 'meta_title', 'meta_description'];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'status' => 'boolean',
        'is_featured' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'order' => 'integer',
        'compounds_count' => 'integer',
        'properties_count' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the parent area.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'parent_id');
    }

    /**
     * Get child areas.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Area::class, 'parent_id')->orderBy('order');
    }

    /**
     * Get all descendants (recursive).
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get compounds in this area.
     */
    public function compounds(): HasMany
    {
        return $this->hasMany(Compound::class, 'area_id');
    }

    /**
     * Get all properties in this area (through compounds).
     */
    public function properties(): HasManyThrough
    {
        return $this->hasManyThrough(Property::class, Compound::class, 'area_id', 'compound_id');
    }

    /**
     * Polymorphic relation for meta info.
     */
    public function metainfo()
    {
        return $this->morphOne(MetaInfo::class, 'metainfoable');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to only active areas.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope to only featured areas.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to get only root/super areas.
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope to get areas of a specific type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to order by position.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get full hierarchical path.
     */
    public function getFullPathAttribute(): string
    {
        $path = collect([$this->name]);
        $parent = $this->parent;

        while ($parent) {
            $path->prepend($parent->name);
            $parent = $parent->parent;
        }

        return $path->implode(' / ');
    }

    /**
     * Check if this is a super area.
     */
    public function getIsSuperAreaAttribute(): bool
    {
        return $this->type === 'super_area';
    }

    /**
     * Check if this area has children.
     */
    public function getHasChildrenAttribute(): bool
    {
        return $this->children()->exists();
    }

    // ==================== METHODS ====================

    /**
     * Update cached counts.
     */
    public function updateCounts(): void
    {
        $this->update([
            'compounds_count' => $this->compounds()->count(),
            'properties_count' => $this->properties()->count(),
        ]);
    }

    /**
     * Get all ancestor IDs.
     */
    public function getAncestorIds(): array
    {
        $ids = [];
        $parent = $this->parent;

        while ($parent) {
            $ids[] = $parent->id;
            $parent = $parent->parent;
        }

        return $ids;
    }
}
