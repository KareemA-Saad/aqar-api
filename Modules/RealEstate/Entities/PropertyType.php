<?php

declare(strict_types=1);

namespace Modules\RealEstate\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/**
 * PropertyType Model
 *
 * Represents types of properties (Apartment, Villa, Duplex, etc.)
 *
 * @package Modules\RealEstate\Entities
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $icon
 * @property string|null $description
 * @property int $order
 * @property bool $status
 */
class PropertyType extends Model
{
    use HasFactory, HasTranslations;

    /**
     * The table associated with the model.
     */
    protected $table = 're_property_types';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'order',
        'status',
    ];

    /**
     * Translatable attributes.
     */
    public array $translatable = ['name', 'description'];

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
     * Get properties of this type.
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'property_type_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope to only active types.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope ordered by position.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get properties count.
     */
    public function getPropertiesCountAttribute(): int
    {
        return $this->properties()->count();
    }
}
