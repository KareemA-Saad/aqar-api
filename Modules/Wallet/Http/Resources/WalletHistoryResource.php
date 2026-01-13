<?php

declare(strict_types=1);

namespace Modules\Wallet\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WalletHistoryResource',
    title: 'Wallet History Resource',
    description: 'Wallet history/transaction resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'payment_gateway', type: 'string', example: 'paypal', nullable: true),
        new OA\Property(property: 'payment_status', type: 'string', example: 'completed'),
        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 50.00),
        new OA\Property(property: 'transaction_id', type: 'string', example: 'TXN123456', nullable: true),
        new OA\Property(property: 'manual_payment_image', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'integer', example: 1),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(
            property: 'user',
            ref: '#/components/schemas/UserResource',
            description: 'User information (when included)'
        ),
    ]
)]
class WalletHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'payment_gateway' => $this->payment_gateway,
            'payment_status' => $this->payment_status,
            'amount' => (float) $this->amount,
            'transaction_id' => $this->transaction_id,
            'manual_payment_image' => $this->manual_payment_image,
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
        ];
    }
}
