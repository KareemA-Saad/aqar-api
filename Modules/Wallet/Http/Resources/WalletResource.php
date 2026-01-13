<?php

declare(strict_types=1);

namespace Modules\Wallet\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WalletResource',
    title: 'Wallet Resource',
    description: 'Wallet resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'balance', type: 'number', format: 'float', example: 150.50),
        new OA\Property(property: 'status', type: 'integer', example: 1),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(
            property: 'user',
            ref: '#/components/schemas/UserResource',
            description: 'User information (when included)'
        ),
        new OA\Property(
            property: 'wallet_settings',
            ref: '#/components/schemas/WalletSettingsResource',
            description: 'Wallet settings (when included)'
        ),
    ]
)]
class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'balance' => (float) $this->balance,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'wallet_settings' => $this->whenLoaded('walletSettings', function () {
                return new WalletSettingsResource($this->walletSettings);
            }),
        ];
    }
}
