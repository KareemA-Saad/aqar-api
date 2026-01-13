<?php

declare(strict_types=1);

namespace Modules\Wallet\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BulkWalletHistoryRequest',
    required: ['action', 'history_ids'],
    properties: [
        new OA\Property(
            property: 'action',
            type: 'string',
            enum: ['delete'],
            example: 'delete'
        ),
        new OA\Property(
            property: 'history_ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
    ]
)]
class BulkWalletHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:delete'],
            'history_ids' => ['required', 'array', 'min:1'],
            'history_ids.*' => ['integer', 'exists:wallet_histories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Action is required',
            'action.in' => 'Action must be: delete',
            'history_ids.required' => 'History IDs are required',
            'history_ids.array' => 'History IDs must be an array',
            'history_ids.min' => 'At least one history ID is required',
            'history_ids.*.integer' => 'Each history ID must be an integer',
            'history_ids.*.exists' => 'One or more history IDs do not exist',
        ];
    }
}
