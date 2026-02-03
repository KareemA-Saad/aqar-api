<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    /**
     * Discount type constants.
     */
    public const DISCOUNT_TYPE_PERCENTAGE = 'percentage';
    public const DISCOUNT_TYPE_AMOUNT = 'amount';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'code',
        'discount_amount',
        'discount_type',
        'status',
        'max_use_qty',
        'expire_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'discount_amount' => 'decimal:2',
        'status' => 'boolean',
        'max_use_qty' => 'integer',
        'expire_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all logs for this coupon.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(CouponLog::class, 'coupon_id', 'id');
    }

    /**
     * Check if coupon is valid (active and not expired).
     */
    public function isValid(): bool
    {
        return $this->status
            && ($this->expire_date === null || $this->expire_date->isFuture());
    }

    /**
     * Check if user has reached max usage limit.
     */
    public function hasReachedLimitForUser(int $userId): bool
    {
        if ($this->max_use_qty === null || $this->max_use_qty === 0) {
            return false;
        }

        $usageCount = $this->logs()->where('user_id', $userId)->count();

        return $usageCount >= $this->max_use_qty;
    }

    /**
     * Calculate discount for given price.
     */
    public function calculateDiscount(float $price): float
    {
        if ($this->discount_type === self::DISCOUNT_TYPE_PERCENTAGE) {
            return round($price * ($this->discount_amount / 100), 2);
        }

        return min((float) $this->discount_amount, $price);
    }
}
