<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Update Admin Request DTO
 *
 * Validates admin update data.
 */
#[OA\Schema(
    schema: 'UpdateAdminRequest',
    title: 'Update Admin Request',
    required: ['name', 'email'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe', maxLength: 191),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@aqar.com', maxLength: 191),
        new OA\Property(property: 'role', type: 'string', example: 'admin', nullable: true),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890', nullable: true),
        new OA\Property(property: 'image', type: 'string', example: 'uploads/admins/avatar.jpg', nullable: true),
    ]
)]
final class UpdateAdminRequest extends FormRequest
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
        $adminId = $this->route('admin');

        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('admins', 'email')->ignore($adminId)],
            'role' => ['nullable', 'string', 'max:191', 'exists:roles,name'],
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
            'role.exists' => 'Selected role does not exist.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{name: string, email: string, role: ?string, mobile: ?string, image: ?string}
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'role' => $data['role'] ?? null,
            'mobile' => $data['mobile'] ?? null,
            'image' => $data['image'] ?? null,
        ];
    }
}
