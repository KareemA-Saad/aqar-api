<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Update Single Setting Request DTO
 *
 * Validates single setting update data.
 */
#[OA\Schema(
    schema: 'UpdateSingleSettingRequest',
    title: 'Update Single Setting Request',
    description: 'Request body for updating a single setting',
    required: ['value'],
    properties: [
        new OA\Property(property: 'value', description: 'The setting value', example: 'My Site Title'),
    ]
)]
final class UpdateSingleSettingRequest extends FormRequest
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
            'value' => ['present'], // Value can be anything including null
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
            'value.present' => 'The value field is required (can be null).',
        ];
    }

    /**
     * Get the validated value.
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->validated()['value'];
    }
}
