<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Tenant Request DTO
 *
 * Validates tenant update data.
 */
final class UpdateTenantRequest extends FormRequest
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
            'theme' => ['nullable', 'string', 'max:50'],
            'theme_code' => ['nullable', 'string', 'max:50'],
            'instruction_status' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{theme?: string, theme_code?: string, instruction_status?: bool}
     */
    public function validatedData(): array
    {
        return array_filter($this->validated(), fn ($value) => $value !== null);
    }
}

