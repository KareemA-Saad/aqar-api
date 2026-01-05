<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CancellationPolicyTier extends Model
{
    use HasFactory;

    protected $fillable = [
        'cancellation_policy_id',
        'hours_before_checkin',
        'refund_percentage',
    ];

    protected $casts = [
        'hours_before_checkin' => 'integer',
        'refund_percentage' => 'integer',
    ];

    public function cancellationPolicy(): BelongsTo
    {
        return $this->belongsTo(CancellationPolicy::class);
    }

    /**
     * Get human-readable description of this tier.
     */
    public function getDescriptionAttribute(): string
    {
        $hours = $this->hours_before_checkin;
        $days = intdiv($hours, 24);
        $remainingHours = $hours % 24;

        $timeStr = '';
        if ($days > 0) {
            $timeStr .= $days . ' day' . ($days > 1 ? 's' : '');
        }
        if ($remainingHours > 0) {
            $timeStr .= ($days > 0 ? ' ' : '') . $remainingHours . ' hour' . ($remainingHours > 1 ? 's' : '');
        }

        return "Cancel at least {$timeStr} before check-in for {$this->refund_percentage}% refund";
    }
}
