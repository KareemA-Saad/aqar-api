<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StaticOption extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'option_name',
        'option_value',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get a static option value by name.
     */
    public static function getOption(string $name, mixed $default = null): mixed
    {
        $option = static::where('option_name', $name)->first();

        return $option?->option_value ?? $default;
    }

    /**
     * Set a static option value.
     */
    public static function setOption(string $name, mixed $value): static
    {
        return static::updateOrCreate(
            ['option_name' => $name],
            ['option_value' => $value]
        );
    }

    /**
     * Get a cached static option value.
     */
    public static function getCached(string $name, mixed $default = null): mixed
    {
        return Cache::remember(
            "static_option_{$name}",
            now()->addHours(24),
            fn () => static::getOption($name, $default)
        );
    }

    /**
     * Clear cache for a specific option.
     */
    public static function clearCache(string $name): void
    {
        Cache::forget("static_option_{$name}");
    }
}

