<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class RoomHold extends Model
{
    use HasFactory;

    protected $fillable = [
        'hold_token',
        'room_type_id',
        'user_id',
        'check_in_date',
        'check_out_date',
        'quantity',
        'expires_at',
    ];

    protected $casts = [
        'check_in_date' => 'date',
        'check_out_date' => 'date',
        'quantity' => 'integer',
        'expires_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (RoomHold $hold) {
            if (empty($hold->hold_token)) {
                $hold->hold_token = Str::random(64);
            }
            if (empty($hold->expires_at)) {
                $hold->expires_at = now()->addMinutes(15);
            }
        });
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the hold has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Extend the hold by additional minutes.
     */
    public function extend(int $minutes = 15): self
    {
        $this->expires_at = now()->addMinutes($minutes);
        $this->save();

        return $this;
    }

    /**
     * Get the number of nights for this hold.
     */
    public function getNightsAttribute(): int
    {
        return $this->check_in_date->diffInDays($this->check_out_date);
    }

    /**
     * Scope to get only active (non-expired) holds.
     */
    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to get expired holds.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope to filter by date range overlap.
     */
    public function scopeOverlappingDates($query, string $checkIn, string $checkOut)
    {
        return $query->where(function ($q) use ($checkIn, $checkOut) {
            $q->where('check_in_date', '<', $checkOut)
              ->where('check_out_date', '>', $checkIn);
        });
    }
}
