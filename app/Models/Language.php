<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'direction',
        'status',
        'default',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'direction' => 'integer', // 0 = LTR, 1 = RTL
        'status' => 'boolean',
        'default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Check if language is RTL.
     */
    public function isRtl(): bool
    {
        return $this->direction === 1;
    }

    /**
     * Scope to get only active languages.
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope to get the default language.
     */
    public function scopeDefault($query)
    {
        return $query->where('default', true);
    }
}

