<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Translatable\HasTranslations;

class PricePlan extends Model
{
    use HasFactory;
    use HasTranslations;

    /**
     * Plan type constants.
     */
    public const TYPE_MONTHLY = 0;
    public const TYPE_YEARLY = 1;
    public const TYPE_LIFETIME = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'subtitle',
        'features',
        'type',
        'status',
        'price',
        'free_trial',
        'has_trial',
        'trial_days',
        'page_permission_feature',
        'blog_permission_feature',
        'product_permission_feature',
        'faq',
        'portfolio_permission_feature',
        'zero_price',
        'storage_permission_feature',
        'appointment_permission_feature',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array<int, string>
     */
    public array $translatable = [
        'title',
        'subtitle',
        'features',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type' => 'integer',
        'status' => 'boolean',
        'price' => 'decimal:2',
        'free_trial' => 'integer',
        'has_trial' => 'boolean',
        'trial_days' => 'integer',
        'zero_price' => 'boolean',
        'page_permission_feature' => 'integer',
        'blog_permission_feature' => 'integer',
        'product_permission_feature' => 'integer',
        'portfolio_permission_feature' => 'integer',
        'storage_permission_feature' => 'integer',
        'appointment_permission_feature' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Check if the plan has a trial period.
     */
    public function hasTrial(): bool
    {
        return (bool) ($this->has_trial ?? ($this->free_trial > 0));
    }

    /**
     * Get the trial duration in days.
     */
    public function getTrialDays(): int
    {
        return (int) ($this->trial_days ?? $this->free_trial ?? 0);
    }

    /**
     * Get type label.
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_MONTHLY => 'Monthly',
            self::TYPE_YEARLY => 'Yearly',
            self::TYPE_LIFETIME => 'Lifetime',
            default => 'Unknown',
        };
    }

    /**
     * Get all features for this plan.
     */
    public function planFeatures(): HasMany
    {
        return $this->hasMany(PlanFeature::class, 'plan_id', 'id');
    }

    /**
     * Get the latest feature for this plan.
     */
    public function latestFeature(): HasOne
    {
        return $this->hasOne(PlanFeature::class, 'plan_id', 'id')->latestOfMany();
    }

    /**
     * Get all payment logs using this plan.
     */
    public function paymentLogs(): HasMany
    {
        return $this->hasMany(PaymentLog::class, 'package_id', 'id');
    }
}

