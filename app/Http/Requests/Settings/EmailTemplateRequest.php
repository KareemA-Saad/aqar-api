<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Email Template Request DTO
 *
 * Validates email template update data.
 */
#[OA\Schema(
    schema: 'EmailTemplateRequest',
    title: 'Email Template Request',
    description: 'Request body for updating email template',
    required: ['subject', 'body'],
    properties: [
        new OA\Property(property: 'subject', type: 'string', example: 'Welcome to {{site_name}}', maxLength: 191),
        new OA\Property(property: 'body', type: 'string', example: '<h1>Hello {{user_name}}</h1><p>Welcome to our platform!</p>'),
        new OA\Property(property: 'enabled', type: 'boolean', example: true),
    ]
)]
final class EmailTemplateRequest extends FormRequest
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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:191'],
            'body' => ['required', 'string'],
            'enabled' => ['sometimes', 'boolean'],
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
            'subject.required' => 'Email subject is required.',
            'subject.max' => 'Email subject must not exceed 191 characters.',
            'body.required' => 'Email body is required.',
        ];
    }

    /**
     * Get validated template data.
     *
     * @return array{subject: string, body: string, enabled: bool}
     */
    public function validatedTemplate(): array
    {
        $data = $this->validated();

        return [
            'subject' => $data['subject'],
            'body' => $data['body'],
            'enabled' => $data['enabled'] ?? true,
        ];
    }
}
