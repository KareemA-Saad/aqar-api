<?php

declare(strict_types=1);

namespace Modules\EmailTemplate\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StoreEmailTemplateRequest',
    required: ['name', 'type', 'subject', 'body'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Welcome Email'),
        new OA\Property(property: 'type', type: 'string', example: 'user_registration'),
        new OA\Property(property: 'subject', type: 'string', example: 'Welcome to {{site_name}}'),
        new OA\Property(property: 'body', type: 'string', example: 'Hello {{user_name}}, welcome to our platform!'),
        new OA\Property(property: 'variables', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'integer', enum: [0, 1], example: 1),
    ]
)]
class StoreEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string'],
            'variables' => ['nullable', 'string'],
            'status' => ['nullable', 'integer', 'in:0,1'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Template name is required',
            'type.required' => 'Template type is required',
            'subject.required' => 'Email subject is required',
            'body.required' => 'Email body is required',
            'status.in' => 'Status must be 0 or 1',
        ];
    }
}
