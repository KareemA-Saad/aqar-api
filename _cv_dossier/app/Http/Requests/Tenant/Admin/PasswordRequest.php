<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Admin;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin Password Request
 *
 * Validates password change data.
 */
#[OA\Schema(
    schema: 'TenantAdminPasswordRequest',
    title: 'Tenant Admin Password Request',
    description: 'Request body for changing admin password',
    required: ['current_password', 'password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'current_password', type: 'string', format: 'password', example: 'OldPassword123!'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewPassword123!'),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewPassword123!'),
    ]
)]
final class PasswordRequest extends FormRequest
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
        // For password change (has current_password)
        if ($this->has('current_password')) {
            return [
                'current_password' => ['required', 'string'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ];
        }

        // For 2FA disable (just password confirmation)
        return [
            'password' => ['required', 'string'],
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
            'current_password.required' => 'Current password is required.',
            'password.required' => 'New password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
