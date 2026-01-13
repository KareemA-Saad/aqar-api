<?php

declare(strict_types=1);

namespace Modules\Service\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BulkServiceRequest',
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
            enum: ['delete', 'activate', 'deactivate'],
            example: 'delete'
        ),
    ]
)]
class BulkServiceRequest extends FormRequest
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
            'ids.*' => ['required', 'integer', 'exists:services,id'],
            'action' => ['required', 'string', 'in:delete,activate,deactivate'],
        ];
    }
}
