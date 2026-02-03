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
    schema: 'PaymentLogResource',
    title: 'Payment Log Resource',
    description: 'Payment transaction log resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'package_name', type: 'string', example: 'Premium Plan'),
        new OA\Property(property: 'package_price', type: 'number', format: 'float', example: 99.99),
        new OA\Property(property: 'package_gateway', type: 'string', example: 'stripe', nullable: true),
        new OA\Property(property: 'transaction_id', type: 'string', example: 'txn_123456789', nullable: true),
        new OA\Property(property: 'track', type: 'string', example: 'track_abc123', nullable: true),
        new OA\Property(property: 'status', type: 'string', example: 'completed'),
        new OA\Property(property: 'payment_status', type: 'string', example: 'paid'),
        new OA\Property(property: 'start_date', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z', nullable: true),
        new OA\Property(property: 'expire_date', type: 'string', format: 'date-time', example: '2024-12-31T23:59:59.000000Z', nullable: true),
        new OA\Property(property: 'trial_expire_date', type: 'string', format: 'date-time', example: '2024-02-01T00:00:00.000000Z', nullable: true),
        new OA\Property(property: 'is_renew', type: 'boolean', example: false),
        new OA\Property(property: 'renew_status', type: 'string', example: 'inactive', nullable: true),
        new OA\Property(property: 'theme', type: 'string', example: 'default', nullable: true),
        new OA\Property(property: 'coupon_discount', type: 'number', format: 'float', example: 10.00, nullable: true),
        new OA\Property(property: 'is_active', type: 'boolean', example: true, description: 'Whether payment is currently active'),
        new OA\Property(property: 'is_expired', type: 'boolean', example: false, description: 'Whether payment has expired'),
        new OA\Property(property: 'user', type: 'object', nullable: true, description: 'User details (when loaded)'),
        new OA\Property(property: 'tenant', type: 'object', nullable: true, description: 'Tenant details (when loaded)'),
        new OA\Property(property: 'package', type: 'object', nullable: true, description: 'Package details (when loaded)'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z'),
    ]
)]
class PaymentLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'package_name' => $this->package_name,
            'package_price' => $this->package_price,
            'package_gateway' => $this->package_gateway,
            'transaction_id' => $this->transaction_id,
            'track' => $this->track,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'start_date' => $this->start_date?->toISOString(),
            'expire_date' => $this->expire_date?->toISOString(),
            'trial_expire_date' => $this->trial_expire_date?->toISOString(),
            'is_renew' => $this->is_renew,
            'renew_status' => $this->renew_status,
            'theme' => $this->theme,
            'coupon_discount' => $this->coupon_discount,
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'user' => new UserResource($this->whenLoaded('user')),
            'tenant' => new TenantResource($this->whenLoaded('tenant')),
            'package' => new PricePlanResource($this->whenLoaded('package')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

