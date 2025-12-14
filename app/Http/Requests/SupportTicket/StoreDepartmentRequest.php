<?php

declare(strict_types=1);

namespace App\Http\Requests\SupportTicket;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Store Department Request DTO
 *
 * Validates support department creation/update data.
 */
#[OA\Schema(
    schema: 'StoreDepartmentRequest',
    title: 'Store Department Request',
    required: ['name', 'status'],
    properties: [
        new OA\Property(
            property: 'name',
            type: 'string',
            example: 'Technical Support',
            maxLength: 191
        ),
        new OA\Property(
            property: 'status',
            type: 'boolean',
            example: true,
            description: 'Whether the department is active'
        ),
    ]
)]
final class StoreDepartmentRequest extends FormRequest
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
        $departmentId = $this->route('department');

        return [
            'name' => [
                'required',
                'string',
                'max:191',
                Rule::unique('support_departments', 'name')->ignore($departmentId),
            ],
            'status' => ['required', 'boolean'],
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
            'name.required' => 'Department name is required.',
            'name.unique' => 'A department with this name already exists.',
            'status.required' => 'Status is required.',
            'status.boolean' => 'Status must be true or false.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{name: string, status: bool}
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'name' => $data['name'],
            'status' => (bool) $data['status'],
        ];
    }
}
