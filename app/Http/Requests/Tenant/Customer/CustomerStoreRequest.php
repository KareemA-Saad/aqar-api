<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;

/**
 * Form Request for creating a new customer.
 */
#[OA\Schema(
    schema: 'CustomerStoreRequest',
    title: 'Customer Store Request',
    description: 'Request body for creating a new customer',
    required: ['name', 'email', 'password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe', minLength: 2, maxLength: 191),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'username', type: 'string', example: 'johndoe', nullable: true, maxLength: 191),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890', nullable: true, maxLength: 20),
        new OA\Property(property: 'address', type: 'string', example: '123 Main St', nullable: true, maxLength: 500),
        new OA\Property(property: 'city', type: 'string', example: 'New York', nullable: true, maxLength: 100),
        new OA\Property(property: 'state', type: 'string', example: 'NY', nullable: true, maxLength: 100),
        new OA\Property(property: 'country', type: 'string', example: 'USA', nullable: true, maxLength: 100),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'SecurePass123!', minLength: 8),
        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'SecurePass123!'),
        new OA\Property(property: 'email_verified', type: 'boolean', example: false),
    ]
)]
final class CustomerStoreRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['nullable', 'string', 'max:191', 'unique:users,username'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
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
            'name.required' => 'Customer name is required.',
            'name.min' => 'Customer name must be at least 2 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'username.unique' => 'This username is already taken.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'customer name',
            'email' => 'email address',
            'password_confirmation' => 'password confirmation',
        ];
    }
}
