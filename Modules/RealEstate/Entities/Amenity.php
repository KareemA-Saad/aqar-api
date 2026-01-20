<?php

declare(strict_types=1);

namespace Modules\RealEstate\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/**
 * Amenity Model
 *
 * Represents features/amenities for properties and compounds.
 * Examples: Swimming Pool, Gym, Security, Parking, etc.
 *
 * @package Modules\RealEstate\Entities
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property string $category (compound, property, both)
 * @property int $order
 * @property bool $status
 */
class Amenity extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The table associated with the model.
     */
    protected $table = 're_amenities';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'category',
        'order',
        'status',
    ];

    /**
     * Translatable attributes.
     */
    public array $translatable = ['name'];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'status' => 'boolean',
        'order' => 'integer',
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
     * Get properties with this amenity.
     */
    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 're_property_amenities', 'amenity_id', 'property_id');
    }

    /**
     * Get compounds with this amenity.
     */
    public function compounds(): BelongsToMany
    {
        return $this->belongsToMany(Compound::class, 're_compound_amenities', 'amenity_id', 'compound_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to only active amenities.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope for property amenities.
     */
    public function scopeForProperties($query)
    {
        return $query->whereIn('category', ['property', 'both']);
    }

    /**
     * Scope for compound amenities.
     */
    public function scopeForCompounds($query)
    {
        return $query->whereIn('category', ['compound', 'both']);
    }

    /**
     * Scope of specific category.
     */
    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope ordered by position.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }
}
