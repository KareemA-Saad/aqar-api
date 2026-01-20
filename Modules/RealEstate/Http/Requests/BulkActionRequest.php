<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BulkActionRequest',
    required: ['ids', 'action'],
    properties: [
        new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
        new OA\Property(property: 'action', type: 'string', enum: ['delete', 'publish', 'unpublish', 'feature', 'unfeature']),
    ]
)]
class BulkActionRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'action' => ['required', 'string', Rule::in(['delete', 'publish', 'unpublish', 'feature', 'unfeature'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Please select items to perform bulk action.',
            'ids.min' => 'Please select at least one item.',
            'action.required' => 'Please specify an action to perform.',
            'action.in' => 'Invalid action specified.',
        ];
    }
}
