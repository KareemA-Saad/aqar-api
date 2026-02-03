<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create Tenant Request DTO
 *
 * Validates tenant creation data.
 */
final class CreateTenantRequest extends FormRequest
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
            'subdomain' => [
                'required',
                'string',
                'min:3',
                'max:63',
                'regex:/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/',
                Rule::unique('tenants', 'id'),
            ],
            'plan_id' => ['required', 'integer', 'exists:price_plans,id'],
            'theme' => ['nullable', 'string', 'max:50'],
            'theme_code' => ['nullable', 'string', 'max:50'],
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
            'subdomain.required' => 'Subdomain is required.',
            'subdomain.min' => 'Subdomain must be at least 3 characters.',
            'subdomain.max' => 'Subdomain must not exceed 63 characters.',
            'subdomain.regex' => 'Subdomain can only contain lowercase letters, numbers, and hyphens. It must start and end with a letter or number.',
            'subdomain.unique' => 'This subdomain is already taken.',
            'plan_id.required' => 'Price plan is required.',
            'plan_id.exists' => 'Selected price plan does not exist.',
        ];
    }

    /**
     * Get validated data as typed array.
     *
     * @return array{subdomain: string, plan_id: int, theme: ?string, theme_code: ?string}
     */
    public function validatedData(): array
    {
        $data = $this->validated();

        return [
            'subdomain' => strtolower($data['subdomain']),
            'plan_id' => (int) $data['plan_id'],
            'theme' => $data['theme'] ?? null,
            'theme_code' => $data['theme_code'] ?? null,
        ];
    }
}

