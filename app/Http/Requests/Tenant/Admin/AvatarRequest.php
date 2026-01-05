<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Admin;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin Avatar Request
 *
 * Validates avatar update data.
 */
#[OA\Schema(
    schema: 'TenantAdminAvatarRequest',
    title: 'Tenant Admin Avatar Request',
    description: 'Request body for updating admin avatar',
    required: ['avatar_id'],
    properties: [
        new OA\Property(property: 'avatar_id', type: 'integer', example: 123, description: 'Media ID of the avatar image'),
    ]
)]
final class AvatarRequest extends FormRequest
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
            'avatar_id' => ['required', 'integer'],
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
            'avatar_id.required' => 'Avatar image ID is required.',
            'avatar_id.integer' => 'Avatar image ID must be an integer.',
        ];
    }
}
