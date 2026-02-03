<?php

declare(strict_types=1);

namespace App\Http\Requests\TwoFactor;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Enable 2FA Request
 *
 * Validates the OTP code to enable two-factor authentication.
 */
final class Enable2FARequest extends FormRequest
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
            'code' => ['required', 'string', 'digits:6'],
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
            'code.required' => 'The verification code is required.',
            'code.digits' => 'The verification code must be 6 digits.',
        ];
    }

    /**
     * Get the verification code.
     */
    public function getCode(): string
    {
        return $this->validated('code');
    }
}
