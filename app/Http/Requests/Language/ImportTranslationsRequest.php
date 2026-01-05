<?php

declare(strict_types=1);

namespace App\Http\Requests\Language;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Import Translations Request
 *
 * Validates data for importing translations.
 */
#[OA\Schema(
    schema: 'ImportTranslationsRequest',
    title: 'Import Translations Request',
    description: 'Request body for importing translations',
    required: ['translations'],
    properties: [
        new OA\Property(
            property: 'translations',
            type: 'object',
            description: 'Key-value pairs of translations',
            additionalProperties: new OA\AdditionalProperties(type: 'string'),
            example: ['Hello' => 'مرحبا', 'Goodbye' => 'مع السلامة']
        ),
        new OA\Property(
            property: 'merge',
            type: 'boolean',
            description: 'Whether to merge with existing translations (true) or replace all (false)',
            default: true,
            example: true
        ),
        new OA\Property(
            property: 'file',
            type: 'string',
            format: 'binary',
            description: 'JSON file containing translations (alternative to translations object)'
        ),
    ]
)]
final class ImportTranslationsRequest extends FormRequest
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
            'translations' => ['required_without:file', 'array'],
            'translations.*' => ['string'],
            'file' => ['required_without:translations', 'file', 'mimes:json', 'max:10240'], // 10MB max
            'merge' => ['sometimes', 'boolean'],
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
            'translations.required_without' => 'Either translations object or a JSON file is required.',
            'translations.array' => 'Translations must be a key-value object.',
            'file.required_without' => 'Either translations object or a JSON file is required.',
            'file.mimes' => 'The file must be a JSON file.',
            'file.max' => 'The file must not exceed 10MB.',
        ];
    }

    /**
     * Get the translations from either the array or file.
     *
     * @return array<string, string>
     */
    public function getTranslations(): array
    {
        if ($this->hasFile('file')) {
            $content = file_get_contents($this->file('file')->getRealPath());
            $decoded = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }
            
            return $decoded;
        }

        return $this->input('translations', []);
    }

    /**
     * Whether to merge with existing translations.
     */
    public function shouldMerge(): bool
    {
        return $this->boolean('merge', true);
    }
}
