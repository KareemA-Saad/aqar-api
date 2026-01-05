<?php

declare(strict_types=1);

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'product_id',
        'variant_id',
        'quantity',
        'unit_price',
        'total_price',
        'options',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'options' => 'array',
    ];

    /**
     * Cart relationship
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Product relationship
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Product variant relationship
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductInventoryDetail::class, 'variant_id');
    }

    /**
     * Calculate total price before save
     */
    public static function boot(): void
    {
        parent::boot();

        static::saving(function (CartItem $item) {
            $item->total_price = $item->unit_price * $item->quantity;
        });
    }

    /**
     * Get formatted price
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format($this->unit_price, 2);
    }

    /**
     * Get formatted total
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return number_format($this->total_price, 2);
    }

    /**
     * Get variant name (color/size combination)
     */
    public function getVariantNameAttribute(): ?string
    {
        if (!$this->variant) {
            return null;
        }

        $parts = [];

        if ($this->variant->color) {
            $parts[] = $this->variant->color->name;
        }

        if ($this->variant->size) {
            $parts[] = $this->variant->size->name;
        }

        return implode(' / ', $parts) ?: null;
    }
}
