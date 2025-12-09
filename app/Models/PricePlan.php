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

