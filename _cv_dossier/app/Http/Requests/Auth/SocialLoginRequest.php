<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Social Login Request DTO
 *
 * Validates social login request.
 * Supports Google and Facebook providers.
 */
final class SocialLoginRequest extends FormRequest
{
    /**
     * Supported social providers.
     */
    public const PROVIDERS = ['google', 'facebook'];

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
            'provider' => ['required', 'string', Rule::in(self::PROVIDERS)],
            'access_token' => ['required', 'string'],
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
            'provider.required' => 'Social provider is required.',
            'provider.in' => 'Provider must be one of: ' . implode(', ', self::PROVIDERS),
            'access_token.required' => 'Access token is required.',
        ];
    }

    /**
     * Get the provider name.
     */
    public function getProvider(): string
    {
        return $this->validated('provider');
    }

    /**
     * Get the access token.
     */
    public function getAccessToken(): string
    {
        return $this->validated('access_token');
    }
}

