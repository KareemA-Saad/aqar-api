<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;

/**
 * Update Password Request DTO
 *
 * Validates password update data.
 */
#[OA\Schema(
    schema: 'UpdatePasswordRequest',
    title: 'Update Password Request',
    required: ['password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'NewSecurePass123!', minLength: 8),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'NewSecurePass123!'),
    ]
)]
final class UpdatePasswordRequest extends FormRequest
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
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{password: string}
     */
    public function validatedData(): array
    {
        return [
            'password' => $this->validated()['password'],
        ];
    }
}
