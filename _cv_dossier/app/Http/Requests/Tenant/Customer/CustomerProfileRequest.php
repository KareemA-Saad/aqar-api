<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Customer;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Form Request for updating customer profile (self-service).
 */
#[OA\Schema(
    schema: 'CustomerProfileRequest',
    title: 'Customer Profile Request',
    description: 'Request body for customer profile update (self-service)',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe', minLength: 2, maxLength: 191),
        new OA\Property(property: 'username', type: 'string', example: 'johndoe', nullable: true, maxLength: 191),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890', nullable: true, maxLength: 20),
        new OA\Property(property: 'address', type: 'string', example: '123 Main St', nullable: true, maxLength: 500),
        new OA\Property(property: 'city', type: 'string', example: 'New York', nullable: true, maxLength: 100),
        new OA\Property(property: 'state', type: 'string', example: 'NY', nullable: true, maxLength: 100),
        new OA\Property(property: 'country', type: 'string', example: 'USA', nullable: true, maxLength: 100),
        new OA\Property(property: 'image', type: 'string', example: 'avatar.jpg', nullable: true, description: 'Profile image URL'),
    ]
)]
final class CustomerProfileRequest extends FormRequest
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
        $userId = auth('api_tenant_user')->id();

        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:191'],
            'username' => [
                'nullable',
                'string',
                'max:191',
                "unique:users,username,{$userId}",
            ],
            'mobile' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'image' => ['nullable', 'string', 'max:500'],
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
            'name.min' => 'Name must be at least 2 characters.',
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
            'name' => 'full name',
            'mobile' => 'phone number',
        ];
    }
}
