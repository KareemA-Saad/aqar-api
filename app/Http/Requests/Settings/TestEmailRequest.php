<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Test Email Request DTO
 *
 * Validates test email request data.
 */
#[OA\Schema(
    schema: 'TestEmailRequest',
    title: 'Test Email Request',
    description: 'Request body for sending a test email',
    required: ['email'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'test@example.com'),
    ]
)]
final class TestEmailRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:191'],
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
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
        ];
    }

    /**
     * Get the validated email.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->validated()['email'];
    }
}
