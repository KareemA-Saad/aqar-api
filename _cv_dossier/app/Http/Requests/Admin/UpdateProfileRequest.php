<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Admin;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Update Profile Request DTO
 *
 * Validates own profile update data.
 */
#[OA\Schema(
    schema: 'UpdateProfileRequest',
    title: 'Update Profile Request',
    required: ['name', 'email'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'John Doe', maxLength: 191),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@aqar.com', maxLength: 191),
        new OA\Property(property: 'mobile', type: 'string', example: '+1234567890', nullable: true),
        new OA\Property(property: 'image', type: 'string', example: 'uploads/admins/avatar.jpg', nullable: true),
    ]
)]
final class UpdateProfileRequest extends FormRequest
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
        /** @var Admin|null $admin */
        $admin = auth('api_admin')->user();
        $adminId = $admin?->id;

        return [
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191', Rule::unique('admins', 'email')->ignore($adminId)],
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
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{name: string, email: string, mobile: ?string, image: ?string}
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'mobile' => $data['mobile'] ?? null,
            'image' => $data['image'] ?? null,
        ];
    }
}
