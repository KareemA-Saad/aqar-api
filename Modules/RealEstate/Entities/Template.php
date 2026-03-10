<?php

declare(strict_types=1);

namespace Modules\RealEstate\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Template Model — F2.4 Canned Response Templates
 *
 * Admin-managed bilingual message templates with variable placeholders.
 *
 * Supported variables: {{user.name}}, {{property.title}}, {{property.price}},
 *                      {{compound.name}}, {{agent.name}}
 *
 * @property int         $id
 * @property string      $name
 * @property string|null $category
 * @property string      $body_en
 * @property string      $body_ar
 * @property array|null  $variables
 * @property bool        $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Template extends Model
{
    use HasFactory;

    protected $table = 're_templates';

    protected $fillable = [
        'name',
        'category',
        'body_en',
        'body_ar',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /** Only active templates. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Filter by category. */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Parse the body and return distinct variable tokens found, e.g. ["user.name"].
     */
    public function detectVariables(): array
    {
        $pattern = '/\{\{([a-z_.]+)\}\}/i';
        $combined = $this->body_en . ' ' . $this->body_ar;
        preg_match_all($pattern, $combined, $matches);

        return array_values(array_unique($matches[1]));
    }
}
