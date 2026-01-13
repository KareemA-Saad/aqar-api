<?php

declare(strict_types=1);

namespace Modules\EmailTemplate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UpdateEmailTemplateRequest',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Welcome Email Updated'),
        new OA\Property(property: 'type', type: 'string', example: 'user_registration'),
        new OA\Property(property: 'subject', type: 'string', example: 'Welcome to {{site_name}}'),
        new OA\Property(property: 'body', type: 'string', example: 'Hello {{user_name}}, welcome!'),
        new OA\Property(property: 'variables', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'integer', enum: [0, 1]),
    ]
)]
class UpdateEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'max:100'],
            'subject' => ['sometimes', 'string', 'max:500'],
            'body' => ['sometimes', 'string'],
            'variables' => ['nullable', 'string'],
            'status' => ['nullable', 'integer', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status must be 0 or 1',
        ];
    }
}
