<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * TrustedDevice Model
 *
 * Represents a device that has been trusted for 2FA bypass.
 * When a user completes 2FA login and opts to "Remember this device",
 * a record is created here to allow skipping 2FA on subsequent logins.
 *
 * @property int $id
 * @property int $user_id
 * @property string $device_token
 * @property string|null $device_name
 * @property string|null $user_agent
 * @property string|null $ip_address
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon $expires_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 */
class TrustedDevice extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'device_token',
        'device_name',
        'user_agent',
        'ip_address',
        'last_used_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns this trusted device.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the device is still valid (not expired).
     */
    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }

    /**
     * Scope a query to only include valid (non-expired) devices.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope a query to only include expired devices.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Update the last used timestamp.
     */
    public function touchLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }
}
