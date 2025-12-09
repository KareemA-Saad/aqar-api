<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Tenant
 */
class TenantResource extends JsonResource
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
            'user_id' => $this->user_id,
            'instruction_status' => $this->instruction_status,
            'theme' => $this->theme ?? null,
            'theme_code' => $this->theme_code ?? null,
            'user' => new UserResource($this->whenLoaded('user')),
            'domains' => DomainResource::collection($this->whenLoaded('domains')),
            'latest_payment' => new PaymentLogResource($this->whenLoaded('paymentLog')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

