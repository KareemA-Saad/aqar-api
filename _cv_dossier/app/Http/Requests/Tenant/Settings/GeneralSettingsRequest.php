<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Tenant General Settings Request
 *
 * Validates tenant general settings update data.
 */
#[OA\Schema(
    schema: 'TenantGeneralSettingsRequest',
    title: 'Tenant General Settings Request',
    description: 'Request body for updating tenant general settings',
    properties: [
        new OA\Property(
            property: 'settings',
            type: 'object',
            properties: [
                new OA\Property(property: 'site_title', type: 'string', example: 'My Store'),
                new OA\Property(property: 'site_tag_line', type: 'string', example: 'Best products online'),
                new OA\Property(property: 'site_footer_text', type: 'string', example: '© 2025 My Store'),
                new OA\Property(property: 'site_timezone', type: 'string', example: 'UTC'),
                new OA\Property(property: 'site_date_format', type: 'string', example: 'Y-m-d'),
                new OA\Property(property: 'site_time_format', type: 'string', example: 'H:i:s'),
                new OA\Property(property: 'site_currency', type: 'string', example: 'USD'),
                new OA\Property(property: 'site_currency_symbol', type: 'string', example: '$'),
                new OA\Property(property: 'site_currency_symbol_position', type: 'string', enum: ['left', 'right'], example: 'left'),
                new OA\Property(property: 'maintenance_mode', type: 'string', enum: ['on', 'off'], example: 'off'),
                new OA\Property(property: 'guest_checkout', type: 'string', enum: ['on', 'off'], example: 'on'),
                new OA\Property(property: 'enable_preloader', type: 'string', enum: ['on', 'off'], example: 'on'),
            ]
        ),
    ]
)]
final class GeneralSettingsRequest extends FormRequest
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
            'settings.site_title' => ['sometimes', 'nullable', 'string', 'max:191'],
            'settings.site_tag_line' => ['sometimes', 'nullable', 'string', 'max:500'],
            'settings.site_footer_text' => ['sometimes', 'nullable', 'string', 'max:500'],
            'settings.site_timezone' => ['sometimes', 'nullable', 'string', 'max:100'],
            'settings.site_date_format' => ['sometimes', 'nullable', 'string', 'max:50'],
            'settings.site_time_format' => ['sometimes', 'nullable', 'string', 'max:50'],
            'settings.site_currency' => ['sometimes', 'nullable', 'string', 'max:10'],
            'settings.site_currency_symbol' => ['sometimes', 'nullable', 'string', 'max:10'],
            'settings.site_currency_symbol_position' => ['sometimes', 'nullable', 'string', 'in:left,right'],
            'settings.maintenance_mode' => ['sometimes', 'nullable', 'string', 'in:on,off'],
            'settings.guest_checkout' => ['sometimes', 'nullable', 'string', 'in:on,off'],
            'settings.enable_preloader' => ['sometimes', 'nullable', 'string', 'in:on,off'],
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
            'settings.site_title.max' => 'Site title must not exceed 191 characters.',
            'settings.site_currency_symbol_position.in' => 'Currency symbol position must be either "left" or "right".',
            'settings.maintenance_mode.in' => 'Maintenance mode must be either "on" or "off".',
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
