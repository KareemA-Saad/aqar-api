<?php

declare(strict_types=1);

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateWalletSettingsRequest',
    properties: [
        new OA\Property(property: 'renew_package', type: 'boolean', example: true, description: 'Auto-renew package setting'),
        new OA\Property(property: 'wallet_alert', type: 'boolean', example: true, description: 'Enable low balance alerts'),
        new OA\Property(property: 'minimum_amount', type: 'number', format: 'float', example: 10.00, description: 'Minimum balance threshold'),
    ]
)]
class UpdateWalletSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'renew_package' => ['nullable', 'boolean'],
            'wallet_alert' => ['nullable', 'boolean'],
            'minimum_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'renew_package.boolean' => 'Renew package must be true or false',
            'wallet_alert.boolean' => 'Wallet alert must be true or false',
            'minimum_amount.numeric' => 'Minimum amount must be a number',
            'minimum_amount.min' => 'Minimum amount cannot be negative',
        ];
    }
}
