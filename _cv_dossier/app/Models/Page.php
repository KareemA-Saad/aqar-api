<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasFactory;
    use HasTranslations;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'page_content',
        'slug',
        'visibility',
        'page_builder',
        'status',
        'breadcrumb',
        'navbar_variant',
        'footer_variant',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public array $translatable = [
        'title',
        'page_content',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'visibility' => 'boolean',
        'page_builder' => 'boolean',
        'breadcrumb' => 'boolean',
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the meta info for this page.
     */
    public function metaInfo(): MorphOne
    {
        return $this->morphOne(MetaInfo::class, 'metainfoable');
    }

    /**
     * Scope to get only visible pages.
     */
    public function scopeVisible($query)
    {
        return $query->where('visibility', true)->where('status', true);
    }

    /**
     * Scope to get only active pages.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}

