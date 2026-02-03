<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentLog extends Model
{
    use LogsActivity;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'central';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payment_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'name',
        'package_name',
        'package_price',
        'package_gateway',
        'package_id',
        'user_id',
        'tenant_id',
        'attachments',
        'custom_fields',
        'status',
        'track',
        'transaction_id',
        'payment_status',
        'start_date',
        'expire_date',
        'renew_status',
        'is_renew',
        'trial_expire_date',
        'manual_payment_attachment',
        'theme',
        'unique_key',
        'coupon_id',
        'coupon_discount',
        'assign_status',
        'theme_code',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'attachments',
        'manual_payment_attachment',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'package_price' => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'status' => 'integer',
        'payment_status' => 'integer',
        'renew_status' => 'boolean',
        'is_renew' => 'boolean',
        'assign_status' => 'boolean',
        'start_date' => 'datetime',
        'expire_date' => 'datetime',
        'trial_expire_date' => 'datetime',
        'custom_fields' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Activity log events to record.
     *
     * @var array<int, string>
     */
    protected static array $recordEvents = ['created', 'deleted'];

    /**
     * Configure activity logging options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'package_name', 'package_price', 'user_id', 'payment_status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the package (price plan) for this payment.
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(PricePlan::class, 'package_id', 'id');
    }

    /**
     * Get the user who made this payment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the tenant associated with this payment.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * Check if payment is active.
     */
    public function isActive(): bool
    {
        return $this->payment_status === 1
            && ($this->expire_date === null || $this->expire_date->isFuture());
    }

    /**
     * Check if payment is expired.
     */
    public function isExpired(): bool
    {
        return $this->expire_date !== null && $this->expire_date->isPast();
    }
}

