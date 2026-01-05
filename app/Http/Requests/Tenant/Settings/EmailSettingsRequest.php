<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Tenant Email Settings Request
 *
 * Validates tenant email/SMTP settings update data.
 */
#[OA\Schema(
    schema: 'TenantEmailSettingsRequest',
    title: 'Tenant Email Settings Request',
    description: 'Request body for updating tenant email settings',
    properties: [
        new OA\Property(
            property: 'settings',
            type: 'object',
            properties: [
                new OA\Property(property: 'mail_driver', type: 'string', enum: ['smtp', 'sendmail', 'mailgun', 'ses', 'postmark'], example: 'smtp'),
                new OA\Property(property: 'mail_host', type: 'string', example: 'smtp.mailtrap.io'),
                new OA\Property(property: 'mail_port', type: 'string', example: '587'),
                new OA\Property(property: 'mail_username', type: 'string', example: 'your-username'),
                new OA\Property(property: 'mail_password', type: 'string', format: 'password', example: 'your-password'),
                new OA\Property(property: 'mail_encryption', type: 'string', enum: ['tls', 'ssl', ''], example: 'tls'),
                new OA\Property(property: 'mail_from_address', type: 'string', format: 'email', example: 'noreply@example.com'),
                new OA\Property(property: 'mail_from_name', type: 'string', example: 'My Store'),
            ]
        ),
    ]
)]
final class EmailSettingsRequest extends FormRequest
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
            'settings' => ['required', 'array', 'min:1'],
            'settings.mail_driver' => ['sometimes', 'nullable', 'string', 'in:smtp,sendmail,mailgun,ses,postmark'],
            'settings.mail_host' => ['sometimes', 'nullable', 'string', 'max:191'],
            'settings.mail_port' => ['sometimes', 'nullable', 'string', 'max:10'],
            'settings.mail_username' => ['sometimes', 'nullable', 'string', 'max:191'],
            'settings.mail_password' => ['sometimes', 'nullable', 'string', 'max:191'],
            'settings.mail_encryption' => ['sometimes', 'nullable', 'string', 'in:tls,ssl,'],
            'settings.mail_from_address' => ['sometimes', 'nullable', 'email', 'max:191'],
            'settings.mail_from_name' => ['sometimes', 'nullable', 'string', 'max:191'],
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
            'settings.required' => 'Settings data is required.',
            'settings.array' => 'Settings must be an array of key-value pairs.',
            'settings.min' => 'At least one setting must be provided.',
            'settings.mail_driver.in' => 'Mail driver must be one of: smtp, sendmail, mailgun, ses, postmark.',
            'settings.mail_encryption.in' => 'Mail encryption must be one of: tls, ssl, or empty.',
            'settings.mail_from_address.email' => 'Mail from address must be a valid email.',
        ];
    }

    /**
     * Get validated settings data.
     *
     * @return array<string, mixed>
     */
    public function validatedSettings(): array
    {
        return $this->validated()['settings'] ?? [];
    }
}
