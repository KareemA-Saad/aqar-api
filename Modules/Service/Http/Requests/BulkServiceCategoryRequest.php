<?php

declare(strict_types=1);

namespace Modules\Service\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BulkServiceCategoryRequest',
    required: ['ids', 'action'],
    properties: [
        new OA\Property(
            property: 'ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
        new OA\Property(
            property: 'action',
            type: 'string',
            enum: ['delete'],
            example: 'delete'
        ),
    ]
)]
class BulkServiceCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:service_categories,id'],
            'action' => ['required', 'string', 'in:delete'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Please select at least one category',
            'ids.*.exists' => 'One or more selected categories do not exist',
            'action.in' => 'Invalid action specified',
        ];
    }
}
