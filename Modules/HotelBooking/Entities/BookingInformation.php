<?php

declare(strict_types=1);

namespace Modules\HotelBooking\Entities;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\CountryManage\Entities\Country;
use Modules\CountryManage\Entities\State;
use Spatie\Translatable\HasTranslations;

class BookingInformation extends Model
{
    use HasFactory, HasTranslations;

    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_CHECKED_OUT = 'checked_out';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_PARTIAL = 'partial';
    public const PAYMENT_REFUNDED = 'refunded';
    public const PAYMENT_FAILED = 'failed';

    public const REFUND_NOT_APPLICABLE = 'not_applicable';
    public const REFUND_PENDING = 'pending';
    public const REFUND_PROCESSING = 'processing';
    public const REFUND_COMPLETED = 'completed';
    public const REFUND_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'room_type_id',
        'hotel_id',
        'state_id',
        'reservation_id',
        'country_id',
        'city',
        'email',
        'mobile',
        'street',
        'post_code',
        'notes',
        'booking_date',
        'booking_expiry_date',
        'booking_status',
        'payment_status',
        'payment_gateway',
        'payment_track',
        'transaction_id',
        'order_details',
        'payment_meta',
        'amount',
        'payment_type',
        'cancellation_policy_id',
        'refund_status',
        'refund_amount',
        'refund_transaction_id',
        'refund_processed_at',
        'cancelled_at',
        'cancellation_reason',
        'check_in_time',
        'check_out_time',
        'checked_in_at',
        'checked_out_at',
    ];

    protected $table = 'booking_informations';

    protected $translatable = ['street', 'notes', 'city'];

    protected $casts = [
        'booking_date' => 'date',
        'booking_expiry_date' => 'date',
        'amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'refund_processed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'order_details' => 'array',
        'payment_meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id', 'id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id', 'id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id', 'id');
    }

    public function bookingPaymentLog(): HasOne
    {
        return $this->hasOne(HotelBookingPaymentLog::class, 'booking_information_id', 'id');
    }

    public function cancellationPolicy(): BelongsTo
    {
        return $this->belongsTo(CancellationPolicy::class);
    }

    /**
     * Multi-room booking support: room types with quantities.
     */
    public function bookingRoomTypes(): HasMany
    {
        return $this->hasMany(BookingRoomType::class, 'booking_information_id');
    }

    /**
     * Get the number of nights for this booking.
     */
    public function getNightsAttribute(): int
    {
        return $this->booking_date->diffInDays($this->booking_expiry_date);
    }

    /**
     * Check if booking can be cancelled based on policy.
     */
    public function canBeCancelled(): bool
    {
        if ($this->booking_status === self::STATUS_CANCELLED) {
            return false;
        }

        if ($this->booking_status === self::STATUS_CHECKED_IN) {
            return false;
        }

        return true;
    }

    /**
     * Calculate refund amount based on cancellation policy.
     */
    public function calculateRefundAmount(): float
    {
        if (!$this->cancellationPolicy) {
            return 0.0;
        }

        $checkInDateTime = Carbon::parse($this->booking_date->format('Y-m-d') . ' ' . ($this->check_in_time ?? '15:00:00'));
        $hoursUntilCheckin = (int) now()->diffInHours($checkInDateTime, false);

        if ($hoursUntilCheckin < 0) {
            return 0.0;
        }

        $refundPercentage = $this->cancellationPolicy->getRefundPercentage($hoursUntilCheckin);

        return round(($this->amount * $refundPercentage) / 100, 2);
    }

    /**
     * Check if booking is eligible for check-in.
     * Check-in is allowed from 3 PM (15:00) on booking date.
     */
    public function canCheckIn(): bool
    {
        if ($this->booking_status !== self::STATUS_CONFIRMED) {
            return false;
        }

        $checkInTime = $this->check_in_time ?? '15:00:00';
        $checkInDateTime = Carbon::parse($this->booking_date->format('Y-m-d') . ' ' . $checkInTime);

        return now()->gte($checkInDateTime);
    }

    /**
     * Check if booking is eligible for check-out.
     * Check-out must be by 11 AM (11:00) on expiry date.
     */
    public function canCheckOut(): bool
    {
        if ($this->booking_status !== self::STATUS_CHECKED_IN) {
            return false;
        }

        return true;
    }

    /**
     * Check if check-out is overdue.
     */
    public function isCheckOutOverdue(): bool
    {
        if ($this->booking_status !== self::STATUS_CHECKED_IN) {
            return false;
        }

        $checkOutTime = $this->check_out_time ?? '11:00:00';
        $checkOutDateTime = Carbon::parse($this->booking_expiry_date->format('Y-m-d') . ' ' . $checkOutTime);

        return now()->gt($checkOutDateTime);
    }

    /**
     * Scope for pending bookings.
     */
    public function scopePending($query)
    {
        return $query->where('booking_status', self::STATUS_PENDING);
    }

    /**
     * Scope for confirmed bookings.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('booking_status', self::STATUS_CONFIRMED);
    }

    /**
     * Scope for today's check-ins.
     */
    public function scopeTodayCheckIns($query)
    {
        return $query->whereDate('booking_date', today())
            ->where('booking_status', self::STATUS_CONFIRMED);
    }

    /**
     * Scope for today's check-outs.
     */
    public function scopeTodayCheckOuts($query)
    {
        return $query->whereDate('booking_expiry_date', today())
            ->where('booking_status', self::STATUS_CHECKED_IN);
    }

    // Legacy aliases for backward compatibility
    public function statee(): BelongsTo
    {
        return $this->state();
    }

    public function room_type(): BelongsTo
    {
        return $this->roomType();
    }

    public function booking_payment_log(): HasOne
    {
        return $this->bookingPaymentLog();
    }
}
