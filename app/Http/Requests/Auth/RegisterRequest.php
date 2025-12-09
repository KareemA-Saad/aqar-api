<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Register Request DTO
 *
 * Validates user registration data.
 */
final class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
            'username' => ['nullable', 'string', 'max:191', 'unique:users,username', 'alpha_dash'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'company' => ['nullable', 'string', 'max:191'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
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
            'name.required' => 'Name is required.',
            'name.max' => 'Name must not exceed 191 characters.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'username.unique' => 'This username is already taken.',
            'username.alpha_dash' => 'Username may only contain letters, numbers, dashes and underscores.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{name: string, email: string, password: string, username: ?string, mobile: ?string, company: ?string, country: ?string, city: ?string}
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'username' => $data['username'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'company' => $data['company'] ?? null,
            'country' => $data['country'] ?? null,
            'city' => $data['city'] ?? null,
        ];
    }
}

