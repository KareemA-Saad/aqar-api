<?php

declare(strict_types=1);

namespace Modules\RealEstate\Entities;

use App\Models\MetaInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/**
 * Compound Model
 *
 * Represents a real estate compound/project containing multiple properties.
 *
 * @package Modules\RealEstate\Entities
 *
 * @property int $id
 * @property int $area_id
 * @property int|null $developer_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property string|null $address
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $thumbnail
 * @property string|null $video_url
 * @property string|null $virtual_tour_url
 * @property int|null $launch_year
 * @property int|null $delivery_year
 * @property float|null $total_area
 * @property int|null $units_count
 * @property string $construction_status
 * @property bool $is_featured
 * @property bool $is_published
 * @property int $priority
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property int $properties_count
 * @property int $available_properties_count
 * @property int $views_count
 * @property float|null $min_price
 * @property float|null $max_price
 * @property string $price_currency
 */
class Compound extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 're_compounds';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'area_id',
        'developer_id',
        'title',
        'slug',
        'description',
        'address',
        'latitude',
        'longitude',
        'thumbnail',
        'video_url',
        'virtual_tour_url',
        'launch_year',
        'delivery_year',
        'total_area',
        'units_count',
        'construction_status',
        'is_featured',
        'is_published',
        'priority',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'properties_count',
        'available_properties_count',
        'views_count',
        'min_price',
        'max_price',
        'price_currency',
    ];

    /**
     * Translatable attributes.
     */
    public array $translatable = ['title', 'description', 'address', 'meta_title', 'meta_description'];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'total_area' => 'decimal:2',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'priority' => 'integer',
        'units_count' => 'integer',
        'properties_count' => 'integer',
        'available_properties_count' => 'integer',
        'views_count' => 'integer',
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->title);
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the area this compound belongs to.
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    /**
     * Get the developer.
     */
    public function developer(): BelongsTo
    {
        return $this->belongsTo(Developer::class, 'developer_id');
    }

    /**
     * Get properties in this compound.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'compound_id');
    }

    /**
     * Get compound amenities.
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 're_compound_amenities', 'compound_id', 'amenity_id');
    }

    /**
     * Get compound images.
     */
    public function images(): HasMany
    {
        return $this->hasMany(CompoundImage::class, 'compound_id')->orderBy('order');
    }

    /**
     * Get the primary image.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(CompoundImage::class, 'compound_id')->orderBy('order')->limit(1);
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
     * Scope to only published compounds.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to active compounds (published).
     */
    public function scopeActive($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to only featured compounds.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->orderBy('priority', 'desc');
    }

    /**
     * Scope by construction status.
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('construction_status', $status);
    }

    /**
     * Scope by area.
     */
    public function scopeInArea($query, int $areaId)
    {
        return $query->where('area_id', $areaId);
    }

    /**
     * Scope by developer.
     */
    public function scopeByDeveloper($query, int $developerId)
    {
        return $query->where('developer_id', $developerId);
    }

    /**
     * Scope ordered by priority.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * Scope by price range (for properties within compound).
     */
    public function scopePriceRange($query, ?float $min = null, ?float $max = null)
    {
        if ($min !== null) {
            $query->where('min_price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('max_price', '<=', $max);
        }
        return $query;
    }

    /**
     * Scope by date range (created_at).
     */
    public function scopeDateRange($query, ?string $startDate = null, ?string $endDate = null)
    {
        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }
        return $query;
    }

    // ==================== ACCESSORS ====================

    /**
     * Get thumbnail URL.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail) {
            return null;
        }

        if (filter_var($this->thumbnail, FILTER_VALIDATE_URL)) {
            return $this->thumbnail;
        }

        return asset('storage/' . $this->thumbnail);
    }

    /**
     * Get formatted price range.
     */
    public function getPriceRangeFormattedAttribute(): ?string
    {
        if (!$this->min_price && !$this->max_price) {
            return null;
        }

        $min = number_format((float)($this->min_price ?? 0));
        $max = number_format((float)($this->max_price ?? 0));

        return "{$this->price_currency} {$min} - {$max}";
    }

    /**
     * Get SEO-friendly URL slug (id-slug pattern).
     */
    public function getIdSlugAttribute(): string
    {
        return "{$this->id}-{$this->slug}";
    }

    /**
     * Check if compound has available properties.
     */
    public function getHasAvailablePropertiesAttribute(): bool
    {
        return $this->available_properties_count > 0;
    }

    // ==================== METHODS ====================

    /**
     * Increment view count.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Update cached counts and prices.
     */
    public function updateStats(): void
    {
        $properties = $this->properties();

        $this->update([
            'properties_count' => $properties->count(),
            'available_properties_count' => $properties->where('is_available', true)->count(),
            'min_price' => $properties->min('price'),
            'max_price' => $properties->max('price'),
        ]);
    }

    /**
     * Sync amenities.
     */
    public function syncAmenities(array $amenityIds): void
    {
        $this->amenities()->sync($amenityIds);
    }
}
