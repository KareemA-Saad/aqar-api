<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Tenant Appearance Settings Request
 *
 * Validates tenant appearance settings update data.
 */
#[OA\Schema(
    schema: 'TenantAppearanceSettingsRequest',
    title: 'Tenant Appearance Settings Request',
    description: 'Request body for updating tenant appearance settings',
    properties: [
        new OA\Property(
            property: 'settings',
            type: 'object',
            properties: [
                new OA\Property(property: 'site_logo', type: 'integer', example: 1),
                new OA\Property(property: 'site_logo_dark', type: 'integer', example: 2),
                new OA\Property(property: 'site_favicon', type: 'integer', example: 3),
                new OA\Property(property: 'primary_color', type: 'string', example: '#007bff'),
                new OA\Property(property: 'secondary_color', type: 'string', example: '#6c757d'),
                new OA\Property(property: 'accent_color', type: 'string', example: '#28a745'),
                new OA\Property(property: 'header_bg_color', type: 'string', example: '#ffffff'),
                new OA\Property(property: 'footer_bg_color', type: 'string', example: '#343a40'),
                new OA\Property(property: 'body_font_family', type: 'string', example: 'Inter'),
                new OA\Property(property: 'heading_font_family', type: 'string', example: 'Poppins'),
                new OA\Property(property: 'custom_css', type: 'string', example: '/* Custom CSS */'),
            ]
        ),
    ]
)]
final class AppearanceSettingsRequest extends FormRequest
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
            'settings' => ['required', 'array', 'min:1'],
            'settings.site_logo' => ['sometimes', 'nullable', 'integer'],
            'settings.site_logo_dark' => ['sometimes', 'nullable', 'integer'],
            'settings.site_favicon' => ['sometimes', 'nullable', 'integer'],
            'settings.primary_color' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'settings.secondary_color' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'settings.accent_color' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'settings.header_bg_color' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'settings.footer_bg_color' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'settings.body_font_family' => ['sometimes', 'nullable', 'string', 'max:100'],
            'settings.heading_font_family' => ['sometimes', 'nullable', 'string', 'max:100'],
            'settings.custom_css' => ['sometimes', 'nullable', 'string', 'max:50000'],
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
            'settings.required' => 'Settings data is required.',
            'settings.array' => 'Settings must be an array of key-value pairs.',
            'settings.min' => 'At least one setting must be provided.',
            'settings.primary_color.regex' => 'Primary color must be a valid hex color code.',
            'settings.secondary_color.regex' => 'Secondary color must be a valid hex color code.',
            'settings.accent_color.regex' => 'Accent color must be a valid hex color code.',
            'settings.header_bg_color.regex' => 'Header background color must be a valid hex color code.',
            'settings.footer_bg_color.regex' => 'Footer background color must be a valid hex color code.',
            'settings.custom_css.max' => 'Custom CSS must not exceed 50000 characters.',
        ];
    }

    /**
     * Get validated settings data.
     *
     * @return array<string, mixed>
     */
    public function validatedSettings(): array
    {
        return $this->validated()['settings'] ?? [];
    }
}
