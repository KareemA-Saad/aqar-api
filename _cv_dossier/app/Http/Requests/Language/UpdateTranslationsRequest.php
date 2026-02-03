<?php

declare(strict_types=1);

namespace App\Http\Requests\Language;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Update Translations Request
 *
 * Validates data for bulk updating translations.
 */
#[OA\Schema(
    schema: 'UpdateTranslationsRequest',
    title: 'Update Translations Request',
    description: 'Request body for bulk updating translations',
    required: ['translations'],
    properties: [
        new OA\Property(
            property: 'translations',
            type: 'object',
            description: 'Key-value pairs of translations to update',
            additionalProperties: new OA\AdditionalProperties(type: 'string'),
            example: ['Welcome' => 'أهلاً وسهلاً', 'Login' => 'تسجيل الدخول']
        ),
    ]
)]
final class UpdateTranslationsRequest extends FormRequest
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
            'translations' => ['required', 'array', 'min:1'],
            'translations.*' => ['string'],
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
            'translations.required' => 'Translations data is required.',
            'translations.array' => 'Translations must be a key-value object.',
            'translations.min' => 'At least one translation must be provided.',
        ];
    }

    /**
     * Get the validated translations.
     *
     * @return array<string, string>
     */
    public function getTranslations(): array
    {
        return $this->input('translations', []);
    }
}
