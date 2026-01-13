<?php

declare(strict_types=1);

namespace Modules\Wallet\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WalletSettingsResource',
    title: 'Wallet Settings Resource',
    description: 'Wallet settings resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'renew_package', type: 'boolean', example: false),
        new OA\Property(property: 'wallet_alert', type: 'boolean', example: true),
        new OA\Property(property: 'minimum_amount', type: 'number', format: 'float', example: 10.00),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ]
)]
class WalletSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'renew_package' => (bool) $this->renew_package,
            'wallet_alert' => (bool) $this->wallet_alert,
            'minimum_amount' => (float) $this->minimum_amount,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
