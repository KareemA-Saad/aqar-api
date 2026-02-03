<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;

/**
 * Store Admin Request DTO
 *
 * Validates admin creation data.
 */
#[OA\Schema(
    schema: 'StoreAdminRequest',
    title: 'Store Admin Request',
    required: ['name', 'email', 'username', 'password', 'role'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe', maxLength: 191),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@aqar.com', maxLength: 191),
        new OA\Property(property: 'username', type: 'string', example: 'johndoe', maxLength: 191),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'SecurePass123!', minLength: 8),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'SecurePass123!'),
        new OA\Property(property: 'role', type: 'string', example: 'admin'),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890', nullable: true),
        new OA\Property(property: 'image', type: 'string', example: 'uploads/admins/avatar.jpg', nullable: true),
    ]
)]
final class StoreAdminRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:191', Rule::unique('admins', 'email')],
            'username' => ['required', 'string', 'max:191', Rule::unique('admins', 'username')],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'role' => ['required', 'string', 'max:191', 'exists:roles,name'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'image' => ['nullable', 'string', 'max:255'],
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
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'username.required' => 'Username is required.',
            'username.unique' => 'This username is already taken.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'role.required' => 'Role is required.',
            'role.exists' => 'Selected role does not exist.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{name: string, email: string, username: string, password: string, role: string, mobile: ?string, image: ?string}
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'username' => strtolower($data['username']),
            'password' => $data['password'],
            'role' => $data['role'],
            'mobile' => $data['mobile'] ?? null,
            'image' => $data['image'] ?? null,
        ];
    }
}
