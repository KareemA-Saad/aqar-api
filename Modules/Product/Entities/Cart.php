<?php

declare(strict_types=1);

namespace Modules\Product\Entities;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\ShippingModule\Entities\ShippingMethod;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'cart_token',
        'coupon_code',
        'discount_amount',
        'discount_type',
        'shipping_method_id',
        'shipping_cost',
        'shipping_address',
        'billing_address',
        'notes',
        'expires_at',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'expires_at' => 'datetime',
    ];

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cart items relationship
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Shipping method relationship
     */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    /**
     * Check if cart belongs to a guest
     */
    public function isGuest(): bool
    {
        return is_null($this->user_id) && !is_null($this->cart_token);
    }

    /**
     * Calculate subtotal
     */
    public function getSubtotalAttribute(): float
    {
        return $this->items->sum('total_price');
    }

    /**
     * Calculate total
     */
    public function getTotalAttribute(): float
    {
        return max(0, $this->subtotal - $this->discount_amount + $this->shipping_cost);
    }

    /**
     * Get items count
     */
    public function getItemsCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Check if cart is expired (for guests)
     */
    public function isExpired(): bool
    {
        if (!$this->expires_at) {
            return false;
        }

        return $this->expires_at->isPast();
    }

    /**
     * Scope to find by user or token
     */
    public function scopeByIdentifier($query, ?int $userId, ?string $cartToken)
    {
        if ($userId) {
            return $query->where('user_id', $userId);
        }

        if ($cartToken) {
            return $query->where('cart_token', $cartToken);
        }

        return $query->whereRaw('1 = 0'); // Return empty
    }
}
