<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use LogsActivity;
    use Notifiable;

    /**
     * The guard name for authentication.
     */
    protected string $guard_name = 'api_user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'has_subdomain',
        'username',
        'email_verified',
        'email_verify_token',
        'mobile',
        'company',
        'address',
        'city',
        'state',
        'country',
        'image',
        'facebook_id',
        'google_id',
        'google2fa_secret',
        'two_factor_enabled',
        'two_factor_confirmed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verify_token',
        'api_token_plan_text',
        'temp_password',
        'facebook_id',
        'google_id',
        'google2fa_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'email_verified' => 'boolean',
        'has_subdomain' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'two_factor_confirmed_at' => 'datetime',
        'google2fa_secret' => 'encrypted',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Activity log events to record.
     *
     * @var array<int, string>
     */
    protected static array $recordEvents = ['created', 'updated', 'deleted'];

    /**
     * Configure activity logging options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'username', 'company'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the tenant info associated with this user (single).
     */
    public function tenantInfo(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'id', 'user_id');
    }

    /**
     * Get all tenants owned by this user.
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'user_id', 'id')->orderByDesc('id');
    }

    /**
     * Get all payment logs for this user.
     */
    public function paymentLogs(): HasMany
    {
        return $this->hasMany(PaymentLog::class, 'user_id', 'id')->orderByDesc('id');
    }

    /**
     * Get the latest payment log.
     */
    public function latestPaymentLog(): HasOne
    {
        return $this->hasOne(PaymentLog::class, 'user_id', 'id')->latestOfMany();
    }

    /**
     * Get support tickets created by this user.
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'user_id', 'id');
    }

    /**
     * Get the country relationship.
     */
    public function countryRelation(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country', 'id');
    }

    /**
     * Get the trusted devices for this user (2FA bypass).
     */
    public function trustedDevices(): HasMany
    {
        return $this->hasMany(TrustedDevice::class);
    }

    /**
     * Check if the user has 2FA enabled and confirmed.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled
            && $this->two_factor_confirmed_at !== null
            && $this->google2fa_secret !== null;
    }

    /**
     * Enable two-factor authentication for the user.
     *
     * @param string $secret The encrypted 2FA secret
     */
    public function enableTwoFactor(string $secret): void
    {
        $this->update([
            'google2fa_secret' => $secret,
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Disable two-factor authentication for the user.
     */
    public function disableTwoFactor(): void
    {
        $this->update([
            'google2fa_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
        ]);

        // Revoke all trusted devices
        $this->trustedDevices()->delete();
    }
}
