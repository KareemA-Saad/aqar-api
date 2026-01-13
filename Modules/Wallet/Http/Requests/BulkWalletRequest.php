<?php

declare(strict_types=1);

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BulkWalletRequest',
    required: ['action', 'wallet_ids'],
    properties: [
        new OA\Property(
            property: 'action',
            type: 'string',
            enum: ['delete', 'activate', 'deactivate'],
            example: 'activate'
        ),
        new OA\Property(
            property: 'wallet_ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
    ]
)]
class BulkWalletRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:delete,activate,deactivate'],
            'wallet_ids' => ['required', 'array', 'min:1'],
            'wallet_ids.*' => ['integer', 'exists:wallets,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Action is required',
            'action.in' => 'Action must be: delete, activate, or deactivate',
            'wallet_ids.required' => 'Wallet IDs are required',
            'wallet_ids.array' => 'Wallet IDs must be an array',
            'wallet_ids.min' => 'At least one wallet ID is required',
            'wallet_ids.*.integer' => 'Each wallet ID must be an integer',
            'wallet_ids.*.exists' => 'One or more wallet IDs do not exist',
        ];
    }
}
