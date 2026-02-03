<?php

declare(strict_types=1);

namespace App\Http\Requests\TwoFactor;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Verify 2FA Request
 *
 * Validates the OTP code and token during login.
 */
final class Verify2FARequest extends FormRequest
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
            'two_factor_token' => ['required', 'string'],
            'code' => ['required', 'string', 'digits:6'],
            'remember_device' => ['sometimes', 'boolean'],
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
            'two_factor_token.required' => 'The two-factor token is required.',
            'code.required' => 'The verification code is required.',
            'code.digits' => 'The verification code must be 6 digits.',
        ];
    }

    /**
     * Get the two-factor token.
     */
    public function getTwoFactorToken(): string
    {
        return $this->validated('two_factor_token');
    }

    /**
     * Get the verification code.
     */
    public function getCode(): string
    {
        return $this->validated('code');
    }

    /**
     * Check if device should be remembered.
     */
    public function shouldRememberDevice(): bool
    {
        return (bool) $this->validated('remember_device', false);
    }
}
