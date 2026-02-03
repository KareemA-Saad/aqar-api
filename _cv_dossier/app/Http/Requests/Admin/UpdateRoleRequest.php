<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Update Role Request DTO
 *
 * Validates role update data.
 */
#[OA\Schema(
    schema: 'UpdateRoleRequest',
    title: 'Update Role Request',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'editor', maxLength: 191),
        new OA\Property(
            property: 'permissions',
            type: 'array',
            items: new OA\Items(type: 'string', example: 'page-list'),
            nullable: true
        ),
    ]
)]
final class UpdateRoleRequest extends FormRequest
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
        $roleId = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:191', Rule::unique('roles', 'name')->ignore($roleId)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
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
            'name.required' => 'Role name is required.',
            'name.unique' => 'This role name already exists.',
            'permissions.array' => 'Permissions must be an array.',
            'permissions.*.exists' => 'One or more selected permissions do not exist.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{name: string, permissions: array<string>}
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'permissions' => $data['permissions'] ?? [],
        ];
    }
}
