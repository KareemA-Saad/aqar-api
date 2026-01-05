<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelBookingPaymentLog extends Model
{
    use HasFactory;

    /**
     * Payment log types.
     */
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_REFUND = 'refund';

    /**
     * Payment log statuses.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'reservation_id',
        'booking_information_id',
        'booking_id', // Alias for booking_information_id
        'type',
        'name',
        'email',
        'phone',
        'booking_date',
        'booking_ex',
        'booking_expiry_date',
        'coupon_type',
        'coupon_code',
        'coupon_discount',
        'tax_amount',
        'subtotal',
        'total_amount',
        'amount',
        'currency',
        'payment_gateway',
        'payment_method',
        'status',
        'payment_status',
        'transaction_id',
        'gateway_response',
        'refund_reference',
        'notes',
        'processed_by',
        'processed_at',
        'manual_payment_attachment',
    ];

    protected $casts = [
        'gateway_response' => 'array',
        'processed_at' => 'datetime',
        'amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    protected static function newFactory()
    {
        return \Modules\HotelBooking\Database\factories\HotelBookingPaymentLogFactory::new();
    }

    /**
     * Get the booking associated with this payment log.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(BookingInformation::class, 'booking_id');
    }

    /**
     * Get the booking information (alias).
     */
    public function bookingInformation(): BelongsTo
    {
        return $this->belongsTo(BookingInformation::class, 'booking_information_id');
    }

    /**
     * Get the user who processed this log.
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'processed_by');
    }

    /**
     * Scope for payments only.
     */
    public function scopePayments($query)
    {
        return $query->where('type', self::TYPE_PAYMENT);
    }

    /**
     * Scope for refunds only.
     */
    public function scopeRefunds($query)
    {
        return $query->where('type', self::TYPE_REFUND);
    }

    /**
     * Scope for pending logs.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for completed logs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Check if this is a payment log.
     */
    public function isPayment(): bool
    {
        return $this->type === self::TYPE_PAYMENT;
    }

    /**
     * Check if this is a refund log.
     */
    public function isRefund(): bool
    {
        return $this->type === self::TYPE_REFUND;
    }

    /**
     * Check if log is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
