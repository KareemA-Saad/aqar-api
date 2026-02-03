<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Form Request for updating an existing customer.
 */
#[OA\Schema(
    schema: 'CustomerUpdateRequest',
    title: 'Customer Update Request',
    description: 'Request body for updating an existing customer',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe', minLength: 2, maxLength: 191),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'username', type: 'string', example: 'johndoe', nullable: true, maxLength: 191),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890', nullable: true, maxLength: 20),
        new OA\Property(property: 'address', type: 'string', example: '123 Main St', nullable: true, maxLength: 500),
        new OA\Property(property: 'city', type: 'string', example: 'New York', nullable: true, maxLength: 100),
        new OA\Property(property: 'state', type: 'string', example: 'NY', nullable: true, maxLength: 100),
        new OA\Property(property: 'country', type: 'string', example: 'USA', nullable: true, maxLength: 100),
        new OA\Property(property: 'email_verified', type: 'boolean', example: true),
    ]
)]
final class CustomerUpdateRequest extends FormRequest
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
        $customerId = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:191'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($customerId),
            ],
            'username' => [
                'nullable',
                'string',
                'max:191',
                Rule::unique('users', 'username')->ignore($customerId),
            ],
            'mobile' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
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
            'name.min' => 'Customer name must be at least 2 characters.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'username.unique' => 'This username is already taken.',
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
        ];
    }
}
