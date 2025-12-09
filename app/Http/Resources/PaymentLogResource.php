<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\PaymentLog
 */
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

