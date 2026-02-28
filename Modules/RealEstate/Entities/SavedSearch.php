<?php

declare(strict_types=1);

namespace Modules\RealEstate\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SavedSearch Model
 *
 * Represents user-saved property search criteria with optional alert notifications.
 *
 * @package Modules\RealEstate\Entities
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property array $criteria JSON search parameters (area_id, price_min, price_max, bedrooms, etc.)
 * @property bool $alerts_enabled
 * @property string $alert_frequency (daily, weekly)
 * @property string|null $last_alerted_at
 * @property string|null $last_matched_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read User $user
 */
class SavedSearch extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 're_saved_searches';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'criteria',
        'alerts_enabled',
        'alert_frequency',
        'last_alerted_at',
        'last_matched_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'criteria' => 'array',
        'alerts_enabled' => 'boolean',
        'last_alerted_at' => 'datetime',
        'last_matched_at' => 'datetime',
    ];

    /**
     * Get the user that owns the saved search.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Only active alerts (enabled and eligible for processing).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActiveAlerts($query)
    {
        return $query->where('alerts_enabled', true);
    }

    /**
     * Scope: Filter by alert frequency.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $frequency
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFrequency($query, string $frequency)
    {
        return $query->where('alert_frequency', $frequency);
    }

    /**
     * Check if this search is due for an alert based on frequency.
     *
     * @return bool
     */
    public function isDueForAlert(): bool
    {
        if (!$this->alerts_enabled) {
            return false;
        }

        // If never alerted, it's due
        if (!$this->last_alerted_at) {
            return true;
        }

        $now = now();
        
        return match ($this->alert_frequency) {
            'daily' => $this->last_alerted_at->diffInHours($now) >= 24,
            'weekly' => $this->last_alerted_at->diffInDays($now) >= 7,
            default => false,
        };
    }

    /**
     * Mark this search as alerted.
     *
     * @return void
     */
    public function markAsAlerted(): void
    {
        $this->update(['last_alerted_at' => now()]);
    }

    /**
     * Mark this search as matched (found results).
     *
     * @return void
     */
    public function markAsMatched(): void
    {
        $this->update(['last_matched_at' => now()]);
    }
}
