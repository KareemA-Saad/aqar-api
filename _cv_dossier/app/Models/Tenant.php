<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;
    use HasDomains;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'central';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'instruction_status' => 'boolean',
        'data' => 'array',
        'suspended_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Subscription status constants.
     */
    public const STATUS_ACTIVE = 'active';
    public const STATUS_TRIAL = 'trial';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUSPENDED = 'suspended';

    /**
     * Custom columns to store in the tenants table.
     *
     * @return array<int, string>
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'user_id',
            'instruction_status',
            'theme_slug',
            'theme_code',
            'subscription_status',
            'suspended_at',
            'suspension_reason',
        ];
    }

    /**
     * Get the user who owns this tenant.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the latest payment log for this tenant.
     */
    public function paymentLog(): HasOne
    {
        return $this->hasOne(PaymentLog::class, 'tenant_id', 'id')->latestOfMany();
    }

    /**
     * Get all payment logs for this tenant.
     */
    public function paymentLogs(): HasMany
    {
        return $this->hasMany(PaymentLog::class, 'tenant_id', 'id')->orderByDesc('id');
    }

    /**
     * Check if tenant subscription is active.
     */
    public function isSubscriptionActive(): bool
    {
        return $this->subscription_status === self::STATUS_ACTIVE;
    }

    /**
     * Check if tenant is in trial period.
     */
    public function isInTrial(): bool
    {
        return $this->subscription_status === self::STATUS_TRIAL;
    }

    /**
     * Check if tenant subscription is expired.
     */
    public function isSubscriptionExpired(): bool
    {
        return $this->subscription_status === self::STATUS_EXPIRED;
    }

    /**
     * Check if tenant is suspended.
     */
    public function isSuspended(): bool
    {
        return $this->subscription_status === self::STATUS_SUSPENDED;
    }

    /**
     * Check if tenant can operate (active or trial).
     */
    public function canOperate(): bool
    {
        return in_array($this->subscription_status, [self::STATUS_ACTIVE, self::STATUS_TRIAL], true);
    }

    /**
     * Get the primary domain for this tenant.
     */
    public function primaryDomain(): HasOne
    {
        return $this->hasOne(Domain::class, 'tenant_id', 'id');
    }
}

