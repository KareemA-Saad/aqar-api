<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Admin Update User Request DTO
 *
 * Validates user update data when admin is updating a user.
 */
#[OA\Schema(
    schema: 'AdminUpdateUserRequest',
    title: 'Admin Update User Request',
    required: ['name', 'email'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe', maxLength: 191),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com', maxLength: 191),
        new OA\Property(property: 'username', type: 'string', example: 'johndoe', maxLength: 191, nullable: true),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890', nullable: true),
        new OA\Property(property: 'company', type: 'string', example: 'Acme Corp', nullable: true),
        new OA\Property(property: 'address', type: 'string', example: '123 Main St', nullable: true),
        new OA\Property(property: 'city', type: 'string', example: 'New York', nullable: true),
        new OA\Property(property: 'state', type: 'string', example: 'NY', nullable: true),
        new OA\Property(property: 'country', type: 'string', example: 'USA', nullable: true),
        new OA\Property(property: 'image', type: 'string', example: 'uploads/users/avatar.jpg', nullable: true),
        new OA\Property(property: 'email_verified', type: 'boolean', example: true, nullable: true),
    ]
)]
final class AdminUpdateUserRequest extends FormRequest
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
        $userId = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($userId)],
            'username' => ['nullable', 'string', 'max:191', Rule::unique('users', 'username')->ignore($userId)],
            'mobile' => ['nullable', 'string', 'max:20'],
            'company' => ['nullable', 'string', 'max:191'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:191'],
            'state' => ['nullable', 'string', 'max:191'],
            'country' => ['nullable', 'string', 'max:191'],
            'image' => ['nullable', 'string', 'max:255'],
            'email_verified' => ['nullable', 'boolean'],
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
            'username.unique' => 'This username is already taken.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array<string, mixed>
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'username' => isset($data['username']) ? strtolower($data['username']) : null,
            'mobile' => $data['mobile'] ?? null,
            'company' => $data['company'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'image' => $data['image'] ?? null,
            'email_verified' => $data['email_verified'] ?? null,
        ];
    }
}

