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
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'instruction_status' => 'boolean',
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
     * Get the primary domain for this tenant.
     */
    public function primaryDomain(): HasOne
    {
        return $this->hasOne(Domain::class, 'tenant_id', 'id');
    }
}

