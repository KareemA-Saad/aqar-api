<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Admin;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin Profile Request
 *
 * Validates admin profile update data.
 */
#[OA\Schema(
    schema: 'TenantAdminProfileRequest',
    title: 'Tenant Admin Profile Request',
    description: 'Request body for updating admin profile',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890'),
        new OA\Property(property: 'company', type: 'string', example: 'My Company'),
        new OA\Property(property: 'city', type: 'string', example: 'New York'),
        new OA\Property(property: 'state', type: 'string', example: 'NY'),
        new OA\Property(property: 'address', type: 'string', example: '123 Main St'),
        new OA\Property(property: 'country', type: 'string', example: 'USA'),
    ]
)]
final class ProfileRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:191'],
            'email' => ['sometimes', 'required', 'email', 'max:191'],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:50'],
            'company' => ['sometimes', 'nullable', 'string', 'max:191'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
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
            'mobile.max' => 'Mobile number must not exceed 50 characters.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array<string, mixed>
     */
    public function validatedData(): array
    {
        return $this->validated();
    }
}
