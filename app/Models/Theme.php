<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * Theme Model
 *
 * Represents website themes available for tenants to choose from.
 *
 * @property int $id
 * @property string|null $title
 * @property string $slug
 * @property string|null $description
 * @property bool $status Active/inactive status
 * @property bool $is_available Available for selection
 * @property string|null $image Theme preview image
 * @property string|null $url Demo URL
 * @property string|null $theme_code Theme identifier code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Theme extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'status',
        'is_available',
        'image',
        'url',
        'theme_code',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'is_available' => 'boolean',
    ];

    /**
     * Scope to get only active themes.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', true);
    }

    /**
     * Scope to get only available themes.
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    /**
     * Scope to get themes by status.
     *
     * @param Builder $query
     * @param bool $status
     * @return Builder
     */
    public function scopeByStatus(Builder $query, bool $status): Builder
    {
        return $query->where('status', $status);
    }
}
