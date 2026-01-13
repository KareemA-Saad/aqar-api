<?php

declare(strict_types=1);

namespace Modules\EmailTemplate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'BulkEmailTemplateRequest',
    required: ['action', 'template_ids'],
    properties: [
        new OA\Property(
            property: 'action',
            type: 'string',
            enum: ['delete', 'activate', 'deactivate'],
            example: 'activate'
        ),
        new OA\Property(
            property: 'template_ids',
            type: 'array',
            items: new OA\Items(type: 'integer'),
            example: [1, 2, 3]
        ),
    ]
)]
class BulkEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:delete,activate,deactivate'],
            'template_ids' => ['required', 'array', 'min:1'],
            'template_ids.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Action is required',
            'action.in' => 'Action must be: delete, activate, or deactivate',
            'template_ids.required' => 'Template IDs are required',
            'template_ids.array' => 'Template IDs must be an array',
            'template_ids.min' => 'At least one template ID is required',
            'template_ids.*.integer' => 'Each template ID must be an integer',
        ];
    }
}
