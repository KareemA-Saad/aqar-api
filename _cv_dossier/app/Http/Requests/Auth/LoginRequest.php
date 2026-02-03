<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Login Request DTO
 *
 * Validates login credentials for all user types.
 * Supports both email and username as credential.
 */
final class LoginRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'credential' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
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
            'credential.required' => 'Email or username is required.',
            'credential.max' => 'Credential must not exceed 255 characters.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
        ];
    }

    /**
     * Get the credential (email or username).
     */
    public function getCredential(): string
    {
        return $this->validated('credential');
    }

    /**
     * Get the password.
     */
    public function getPassword(): string
    {
        return $this->validated('password');
    }
}

