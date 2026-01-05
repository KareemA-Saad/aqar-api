<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class CancellationPolicy extends Model
{
    use HasFactory, HasTranslations;

    protected $fillable = [
        'hotel_id',
        'room_type_id',
        'name',
        'description',
        'is_refundable',
        'is_default',
        'status',
    ];

    protected $casts = [
        'is_refundable' => 'boolean',
        'is_default' => 'boolean',
        'status' => 'boolean',
    ];

    protected $translatable = ['name', 'description'];

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(CancellationPolicyTier::class)->orderBy('hours_before_checkin', 'desc');
    }

    /**
     * Calculate refund percentage based on hours until check-in.
     */
    public function getRefundPercentage(int $hoursUntilCheckin): int
    {
        if (!$this->is_refundable) {
            return 0;
        }

        $tier = $this->tiers()
            ->where('hours_before_checkin', '<=', $hoursUntilCheckin)
            ->orderBy('hours_before_checkin', 'desc')
            ->first();

        return $tier?->refund_percentage ?? 0;
    }

    /**
     * Check if cancellation is allowed based on hours until check-in.
     */
    public function isCancellationAllowed(int $hoursUntilCheckin): bool
    {
        if (!$this->is_refundable) {
            return false;
        }

        return $this->tiers()
            ->where('hours_before_checkin', '<=', $hoursUntilCheckin)
            ->exists();
    }
}
