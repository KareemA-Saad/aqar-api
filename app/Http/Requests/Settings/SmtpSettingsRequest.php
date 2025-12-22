<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * SMTP Settings Request DTO
 *
 * Validates SMTP/Email settings update data.
 */
#[OA\Schema(
    schema: 'SmtpSettingsRequest',
    title: 'SMTP Settings Request',
    description: 'Request body for updating SMTP configuration',
    required: ['host', 'port', 'username', 'encryption', 'from_email'],
    properties: [
        new OA\Property(property: 'driver', type: 'string', example: 'smtp', enum: ['smtp', 'sendmail', 'mailgun', 'ses', 'postmark']),
        new OA\Property(property: 'host', type: 'string', example: 'smtp.gmail.com'),
        new OA\Property(property: 'port', type: 'integer', example: 587),
        new OA\Property(property: 'username', type: 'string', example: 'your-email@gmail.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'your-app-password'),
        new OA\Property(property: 'encryption', type: 'string', example: 'tls', enum: ['tls', 'ssl', 'none']),
        new OA\Property(property: 'from_email', type: 'string', format: 'email', example: 'noreply@example.com'),
        new OA\Property(property: 'from_name', type: 'string', example: 'My Application'),
    ]
)]
final class SmtpSettingsRequest extends FormRequest
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
            'driver' => ['sometimes', 'string', 'in:smtp,sendmail,mailgun,ses,postmark'],
            'host' => ['required', 'string', 'max:191', 'regex:/^\S*$/u'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:191'],
            'password' => ['sometimes', 'nullable', 'string', 'max:191'],
            'encryption' => ['required', 'string', 'in:tls,ssl,none'],
            'from_email' => ['required', 'email', 'max:191'],
            'from_name' => ['sometimes', 'nullable', 'string', 'max:191'],
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
            'host.required' => 'SMTP host is required.',
            'host.regex' => 'SMTP host cannot contain spaces.',
            'port.required' => 'SMTP port is required.',
            'port.integer' => 'SMTP port must be a number.',
            'port.min' => 'SMTP port must be at least 1.',
            'port.max' => 'SMTP port must not exceed 65535.',
            'username.required' => 'SMTP username is required.',
            'encryption.required' => 'Encryption type is required.',
            'encryption.in' => 'Encryption type must be tls, ssl, or none.',
            'from_email.required' => 'From email address is required.',
            'from_email.email' => 'Please provide a valid email address.',
        ];
    }

    /**
     * Get validated SMTP config data.
     *
     * @return array<string, mixed>
     */
    public function validatedConfig(): array
    {
        $data = $this->validated();

        return [
            'driver' => $data['driver'] ?? 'smtp',
            'host' => $data['host'],
            'port' => (string) $data['port'],
            'username' => $data['username'],
            'password' => $data['password'] ?? null,
            'encryption' => $data['encryption'] === 'none' ? null : $data['encryption'],
            'from_email' => $data['from_email'],
            'from_name' => $data['from_name'] ?? null,
        ];
    }
}
