<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;

/**
 * Change Password Request DTO
 *
 * Validates password change data.
 */
#[OA\Schema(
    schema: 'ChangePasswordRequest',
    title: 'Change Password Request',
    required: ['old_password', 'password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'old_password', type: 'string', format: 'password', example: 'OldPassword123!'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewSecurePass123!', minLength: 8),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewSecurePass123!'),
    ]
)]
final class ChangePasswordRequest extends FormRequest
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
            'old_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
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
            'old_password.required' => 'Current password is required.',
            'password.required' => 'New password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{old_password: string, password: string}
     */
    public function validatedData(): array
    {
        return [
            'old_password' => $this->validated()['old_password'],
            'password' => $this->validated()['password'],
        ];
    }
}

