<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin \App\Models\PaymentLog
 */
#[OA\Schema(
    schema: 'SubscriptionResource',
    title: 'Subscription Resource',
    description: 'User subscription resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(
            property: 'plan',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 1),
                new OA\Property(property: 'name', type: 'string', example: 'Premium Plan'),
                new OA\Property(property: 'price', type: 'number', format: 'float', example: 99.99),
                new OA\Property(property: 'type', type: 'integer', example: 0),
                new OA\Property(property: 'type_label', type: 'string', example: 'Monthly'),
            ],
            type: 'object'
        ),
        new OA\Property(
            property: 'tenant',
            properties: [
                new OA\Property(property: 'id', type: 'string', example: 'mystore'),
                new OA\Property(property: 'domain', type: 'string', example: 'mystore.example.com'),
                new OA\Property(property: 'theme', type: 'string', example: 'default'),
            ],
            type: 'object',
            nullable: true
        ),
        new OA\Property(property: 'price_paid', type: 'number', format: 'float', example: 89.99),
        new OA\Property(property: 'payment_gateway', type: 'string', example: 'stripe', nullable: true),
        new OA\Property(property: 'transaction_id', type: 'string', example: 'txn_123456789', nullable: true),
        new OA\Property(property: 'track', type: 'string', example: 'track_abc123', nullable: true),
        new OA\Property(
            property: 'status',
            type: 'string',
            enum: ['pending', 'trial', 'complete', 'cancelled'],
            example: 'complete'
        ),
        new OA\Property(
            property: 'payment_status',
            type: 'integer',
            description: '0 = Pending, 1 = Paid',
            example: 1
        ),
        new OA\Property(property: 'payment_status_label', type: 'string', example: 'Paid'),
        new OA\Property(property: 'start_date', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z', nullable: true),
        new OA\Property(property: 'expire_date', type: 'string', format: 'date-time', example: '2024-12-31T23:59:59.000000Z', nullable: true),
        new OA\Property(property: 'trial_expire_date', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'days_remaining', type: 'integer', example: 30, nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'is_expired', type: 'boolean', example: false),
        new OA\Property(property: 'is_trial', type: 'boolean', example: false),
        new OA\Property(property: 'is_renew', type: 'boolean', example: false),
        new OA\Property(property: 'can_renew', type: 'boolean', example: true, description: 'Whether subscription can be renewed'),
        new OA\Property(property: 'can_upgrade', type: 'boolean', example: true, description: 'Whether subscription can be upgraded'),
        new OA\Property(property: 'coupon_discount', type: 'number', format: 'float', example: 10.00, nullable: true),
        new OA\Property(property: 'theme', type: 'string', example: 'default', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class SubscriptionResource extends JsonResource
{
    /**
     * Payment status labels.
     */
    private const PAYMENT_STATUS_LABELS = [
        0 => 'Pending',
        1 => 'Paid',
    ];

    /**
     * Plan type labels.
     */
    private const TYPE_LABELS = [
        0 => 'Monthly',
        1 => 'Yearly',
        2 => 'Lifetime',
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $package = $this->whenLoaded('package', fn () => $this->package);
        $tenant = $this->whenLoaded('tenant', fn () => $this->tenant);

        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'plan' => $this->when($package instanceof \App\Models\PricePlan, fn () => [
                'id' => $package->id,
                'name' => $package->title,
                'price' => (float) $package->price,
                'type' => $package->type,
                'type_label' => self::TYPE_LABELS[$package->type] ?? 'Unknown',
            ]),
            'tenant' => $this->when($tenant instanceof \App\Models\Tenant, fn () => [
                'id' => $tenant->id,
                'domain' => $tenant->primaryDomain?->domain ?? $tenant->id,
                'theme' => $tenant->theme ?? $this->theme,
            ]),
            'price_paid' => (float) $this->package_price,
            'payment_gateway' => $this->package_gateway,
            'transaction_id' => $this->transaction_id,
            'track' => $this->track,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'payment_status_label' => self::PAYMENT_STATUS_LABELS[$this->payment_status] ?? 'Unknown',
            'start_date' => $this->start_date?->toISOString(),
            'expire_date' => $this->expire_date?->toISOString(),
            'trial_expire_date' => $this->trial_expire_date?->toISOString(),
            'days_remaining' => $this->calculateDaysRemaining(),
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_trial' => $this->status === 'trial',
            'is_renew' => $this->is_renew,
            'can_renew' => $this->canRenew(),
            'can_upgrade' => $this->canUpgrade(),
            'coupon_discount' => $this->coupon_discount,
            'theme' => $this->theme,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Calculate remaining days until expiration.
     */
    private function calculateDaysRemaining(): ?int
    {
        if ($this->expire_date === null) {
            return null; // Lifetime plan
        }

        if ($this->expire_date->isPast()) {
            return 0;
        }

        return (int) now()->diffInDays($this->expire_date, false);
    }

    /**
     * Check if subscription can be renewed.
     */
    private function canRenew(): bool
    {
        // Can renew if:
        // - Payment is complete
        // - Has an expiration date (not lifetime)
        // - Expires within 30 days OR already expired but within grace period
        if ($this->payment_status !== 1 || $this->expire_date === null) {
            return false;
        }

        $daysRemaining = $this->calculateDaysRemaining();

        return $daysRemaining !== null && $daysRemaining <= 30;
    }

    /**
     * Check if subscription can be upgraded.
     */
    private function canUpgrade(): bool
    {
        // Can upgrade if:
        // - Payment is complete
        // - Currently active
        return $this->payment_status === 1 && $this->isActive();
    }
}
