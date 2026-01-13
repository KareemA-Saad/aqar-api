<?php

declare(strict_types=1);

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateWalletBalanceRequest',
    required: ['balance'],
    properties: [
        new OA\Property(property: 'balance', type: 'number', format: 'float', example: 100.50, description: 'New wallet balance'),
    ]
)]
class UpdateWalletBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'balance' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'balance.required' => 'Balance is required',
            'balance.numeric' => 'Balance must be a number',
            'balance.min' => 'Balance cannot be negative',
        ];
    }
}
