<?php

declare(strict_types=1);

namespace Modules\RealEstate\Entities;

use App\Models\MetaInfo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/**
 * Developer Model
 *
 * Represents real estate developers/companies.
 *
 * @package Modules\RealEstate\Entities
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $logo
 * @property string|null $website
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $address
 * @property bool $is_featured
 * @property bool $status
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property int $compounds_count
 * @property int $properties_count
 */
class Developer extends Model
{
    use HasFactory, HasTranslations, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 're_developers';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'website',
        'phone',
        'email',
        'address',
        'is_featured',
        'status',
        'meta_title',
        'meta_description',
        'compounds_count',
        'properties_count',
    ];

    /**
     * Translatable attributes.
     */
    public array $translatable = ['name', 'description', 'address', 'meta_title', 'meta_description'];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_featured' => 'boolean',
        'status' => 'boolean',
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
     * Get compounds by this developer.
     */
    public function compounds(): HasMany
    {
        return $this->hasMany(Compound::class, 'developer_id');
    }

    /**
     * Get all properties by this developer (through compounds).
     */
    public function properties(): HasManyThrough
    {
        return $this->hasManyThrough(
            Property::class,
            Compound::class,
            'developer_id', // FK on compounds table
            'compound_id',  // FK on properties table
            'id',           // Local key on developers table
            'id'            // Local key on compounds table
        );
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
     * Scope to only active developers.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope to only featured developers.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope ordered by name.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        if (filter_var($this->logo, FILTER_VALIDATE_URL)) {
            return $this->logo;
        }

        return asset('storage/' . $this->logo);
    }

    // ==================== METHODS ====================

    /**
     * Update cached counts.
     */
    public function updateCounts(): void
    {
        $compoundsCount = $this->compounds()->count();
        $propertiesCount = Property::whereIn('compound_id', $this->compounds()->pluck('id'))->count();

        $this->update([
            'compounds_count' => $compoundsCount,
            'properties_count' => $propertiesCount,
        ]);
    }
}
