<?php

declare(strict_types=1);

namespace Modules\RealEstate\Entities;

use App\Models\MetaInfo;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/**
 * Property Model
 *
 * Represents individual real estate properties/units.
 *
 * @package Modules\RealEstate\Entities
 *
 * @property int $id
 * @property int $compound_id
 * @property int $property_type_id
 * @property int|null $agent_id
 * @property string $title
 * @property string $slug
 * @property string|null $description
 * @property float $price
 * @property string $currency
 * @property string $price_type (total, per_sqm)
 * @property string $listing_type (sale, rent)
 * @property string $payment_option (cash, installment, both)
 * @property array|null $installment_details
 * @property int|null $bedrooms
 * @property int|null $bathrooms
 * @property float|null $area
 * @property string $area_unit (sqm, sqft)
 * @property int|null $floor_number
 * @property int|null $total_floors
 * @property string|null $finishing
 * @property string|null $view
 * @property bool $is_available
 * @property string|null $delivery_date
 * @property string|null $reference_number
 * @property string|null $thumbnail
 * @property string|null $video_url
 * @property string|null $virtual_tour_url
 * @property array|null $floor_plan_images
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property bool $is_featured
 * @property bool $is_published
 * @property int $priority
 * @property int $views_count
 * @property int $inquiry_count
 * @property int $favorites_count
 *
 * @property-read Compound $compound
 * @property-read Area $area (through compound)
 * @property-read PropertyType $propertyType
 * @property int $favorites_count
 */
class Property extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 're_properties';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'compound_id',
        'property_type_id',
        'agent_id',
        'title',
        'slug',
        'description',
        'price',
        'currency',
        'price_type',
        'listing_type',
        'payment_option',
        'installment_details',
        'bedrooms',
        'bathrooms',
        'area',
        'area_unit',
        'floor_number',
        'total_floors',
        'finishing',
        'view',
        'is_available',
        'delivery_date',
        'reference_number',
        'thumbnail',
        'video_url',
        'virtual_tour_url',
        'floor_plan_images',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_featured',
        'is_published',
        'priority',
        'views_count',
        'inquiry_count',
        'favorites_count',
    ];

    /**
     * Translatable attributes.
     */
    public array $translatable = ['title', 'description', 'meta_title', 'meta_description'];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'price' => 'decimal:2',
        'area' => 'decimal:2',
        'installment_details' => 'array',
        'floor_plan_images' => 'array',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'delivery_date' => 'date',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'floor_number' => 'integer',
        'total_floors' => 'integer',
        'priority' => 'integer',
        'views_count' => 'integer',
        'inquiry_count' => 'integer',
        'favorites_count' => 'integer',
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
            if (empty($model->reference_number)) {
                $model->reference_number = 'PROP-' . date('Y') . '-' . str_pad((string) rand(1, 99999), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the compound this property belongs to.
     */
    public function compound(): BelongsTo
    {
        return $this->belongsTo(Compound::class, 'compound_id');
    }

    /**
     * Get the area through compound.
     */
    public function area(): HasOneThrough
    {
        return $this->hasOneThrough(
            Area::class,
            Compound::class,
            'id',           // Foreign key on compounds table
            'id',           // Foreign key on areas table
            'compound_id',  // Local key on properties table
            'area_id'       // Local key on compounds table
        );
    }

    /**
     * Get the property type.
     */
    public function propertyType(): BelongsTo
    {
        return $this->belongsTo(PropertyType::class, 'property_type_id');
    }

    /**
     * Get the agent.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Get property amenities.
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class, 're_property_amenities', 'property_id', 'amenity_id');
    }

    /**
     * Get property images.
     */
    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class, 'property_id')->orderBy('order');
    }

    /**
     * Get the primary image.
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(PropertyImage::class, 'property_id')->where('is_primary', true);
    }

    /**
     * Get property inquiries.
     */
    public function inquiries(): HasMany
    {
        return $this->hasMany(PropertyInquiry::class, 'property_id');
    }

    /**
     * Get users who saved this property.
     */
    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 're_saved_properties', 'property_id', 'user_id')
            ->withTimestamps();
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
     * Scope to only published properties.
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope to only available properties.
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope to active properties (published and available).
     */
    public function scopeActive($query)
    {
        return $query->where('is_published', true)->where('is_available', true);
    }

    /**
     * Scope to only featured properties.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->orderBy('priority', 'desc');
    }

    /**
     * Scope for sale properties.
     */
    public function scopeForSale($query)
    {
        return $query->where('listing_type', 'sale');
    }

    /**
     * Scope for rent properties.
     */
    public function scopeForRent($query)
    {
        return $query->where('listing_type', 'rent');
    }

    /**
     * Scope by listing type.
     */
    public function scopeListingType($query, string $type)
    {
        return $query->where('listing_type', $type);
    }

    /**
     * Scope by compound.
     */
    public function scopeInCompound($query, int $compoundId)
    {
        return $query->where('compound_id', $compoundId);
    }

    /**
     * Scope by property type.
     */
    public function scopeOfType($query, int $typeId)
    {
        return $query->where('property_type_id', $typeId);
    }

    /**
     * Scope by price range.
     */
    public function scopePriceRange($query, ?float $min = null, ?float $max = null)
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }
        return $query;
    }

    /**
     * Scope by area range.
     */
    public function scopeAreaRange($query, ?float $min = null, ?float $max = null)
    {
        if ($min !== null) {
            $query->where('area', '>=', $min);
        }
        if ($max !== null) {
            $query->where('area', '<=', $max);
        }
        return $query;
    }

    /**
     * Scope by bedrooms.
     */
    public function scopeBedrooms($query, int $count)
    {
        return $query->where('bedrooms', '>=', $count);
    }

    /**
     * Scope by bathrooms.
     */
    public function scopeBathrooms($query, int $count)
    {
        return $query->where('bathrooms', '>=', $count);
    }

    /**
     * Scope ordered by priority.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * Scope sorted by specific criteria.
     */
    public function scopeSortBy($query, string $sortBy)
    {
        return match ($sortBy) {
            'newest' => $query->orderBy('created_at', 'desc'),
            'price_low' => $query->orderBy('price', 'asc'),
            'price_high' => $query->orderBy('price', 'desc'),
            'area_low' => $query->orderBy('area', 'asc'),
            'area_high' => $query->orderBy('area', 'desc'),
            default => $query->orderBy('priority', 'desc')->orderBy('created_at', 'desc'),
        };
    }

    // ==================== ACCESSORS ====================

    /**
     * Get formatted price.
     */
    public function getPriceFormattedAttribute(): string
    {
        return number_format((float)($this->price), 0) . ' ' . $this->currency;
    }

    /**
     * Get thumbnail URL.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail) {
            // Return first image if no thumbnail
            $firstImage = $this->images()->first();
            return $firstImage?->image_url;
        }

        if (filter_var($this->thumbnail, FILTER_VALIDATE_URL)) {
            return $this->thumbnail;
        }

        return asset('storage/' . $this->thumbnail);
    }

    /**
     * Get SEO-friendly URL slug (id-slug pattern).
     */
    public function getIdSlugAttribute(): string
    {
        return "{$this->id}-{$this->slug}";
    }

    /**
     * Get area with unit.
     */
    public function getAreaFormattedAttribute(): ?string
    {
        if (!$this->area) {
            return null;
        }

        return number_format((float)($this->area), 0) . ' ' . $this->area_unit;
    }

    /**
     * Get property features as array.
     */
    public function getFeaturesAttribute(): array
    {
        return [
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'area' => $this->area,
            'area_unit' => $this->area_unit,
            'floor_number' => $this->floor_number,
            'finishing' => $this->finishing,
            'view' => $this->view,
        ];
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
     * Increment inquiry count.
     */
    public function incrementInquiries(): void
    {
        $this->increment('inquiry_count');
    }

    /**
     * Sync amenities.
     */
    public function syncAmenities(array $amenityIds): void
    {
        $this->amenities()->sync($amenityIds);
    }

    /**
     * Check if property is saved by user.
     */
    public function isSavedByUser(int $userId): bool
    {
        return $this->savedByUsers()->where('user_id', $userId)->exists();
    }

    /**
     * Generate unique reference number.
     */
    public static function generateReferenceNumber(): string
    {
        do {
            $reference = 'PROP-' . date('Y') . '-' . str_pad((string) rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (self::where('reference_number', $reference)->exists());

        return $reference;
    }
}
