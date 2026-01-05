<?php

declare(strict_types=1);

namespace App\Http\Requests\Language;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

/**
 * Update Language Request
 *
 * Validates data for updating an existing language.
 */
#[OA\Schema(
    schema: 'UpdateLanguageRequest',
    title: 'Update Language Request',
    description: 'Request body for updating an existing language',
    properties: [
        new OA\Property(
            property: 'name',
            type: 'string',
            description: 'Display name of the language',
            maxLength: 191,
            example: 'English (US)'
        ),
        new OA\Property(
            property: 'slug',
            type: 'string',
            description: 'Language code (ISO 639-1 or locale code)',
            maxLength: 10,
            example: 'en_US'
        ),
        new OA\Property(
            property: 'direction',
            type: 'integer',
            description: 'Text direction (0 = LTR, 1 = RTL)',
            enum: [0, 1],
            example: 0
        ),
        new OA\Property(
            property: 'status',
            type: 'boolean',
            description: 'Whether the language is active',
            example: true
        ),
    ]
)]
final class UpdateLanguageRequest extends FormRequest
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
        $languageCode = $this->route('code');
        
        return [
            'name' => ['sometimes', 'string', 'max:191'],
            'slug' => [
                'sometimes',
                'string',
                'max:10',
                'regex:/^[a-z]{2}(_[A-Z]{2})?$/',
                Rule::unique('languages', 'slug')->ignore($languageCode, 'slug'),
            ],
            'direction' => ['sometimes', 'integer', 'in:0,1'],
            'status' => ['sometimes', 'boolean'],
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
            'name.max' => 'Language name must not exceed 191 characters.',
            'slug.max' => 'Language code must not exceed 10 characters.',
            'slug.regex' => 'Language code must be a valid locale format (e.g., en, en_US, ar, fr_FR).',
            'slug.unique' => 'This language code already exists.',
            'direction.in' => 'Direction must be 0 (LTR) or 1 (RTL).',
        ];
    }
}
