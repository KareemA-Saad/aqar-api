<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * General Settings Request DTO
 *
 * Validates general settings update data.
 */
#[OA\Schema(
    schema: 'GeneralSettingsRequest',
    title: 'General Settings Request',
    description: 'Request body for updating general settings',
    properties: [
        new OA\Property(
            property: 'settings',
            type: 'object',
            description: 'Key-value pairs of settings to update',
            additionalProperties: new OA\AdditionalProperties(type: 'string'),
            example: ['site_title' => 'My Site', 'timezone' => 'UTC']
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
            'settings.site_logo' => ['sometimes', 'nullable', 'integer'],
            'settings.site_white_logo' => ['sometimes', 'nullable', 'integer'],
            'settings.site_favicon' => ['sometimes', 'nullable', 'integer'],
            'settings.breadcrumb_left_image' => ['sometimes', 'nullable'],
            'settings.breadcrumb_right_image' => ['sometimes', 'nullable'],
            'settings.site_breadcrumb_image' => ['sometimes', 'nullable'],
            'settings.timezone' => ['sometimes', 'nullable', 'string', 'max:100'],
            'settings.date_display_style' => ['sometimes', 'nullable', 'string', 'max:50'],
            'settings.maintenance_mode' => ['sometimes', 'nullable', 'string', 'in:on,off'],
            'settings.dark_mode_for_admin_panel' => ['sometimes', 'nullable', 'string', 'in:on,off'],
            'settings.backend_preloader' => ['sometimes', 'nullable', 'string', 'in:on,off'],
            'settings.language_selector_status' => ['sometimes', 'nullable', 'string', 'in:on,off'],
            'settings.mouse_cursor_effect_status' => ['sometimes', 'nullable', 'string', 'in:on,off'],
            'settings.section_title_extra_design_status' => ['sometimes', 'nullable', 'string', 'in:on,off'],
            'settings.site_force_ssl_redirection' => ['sometimes', 'nullable', 'string', 'in:on,off'],
            'settings.table_list_data_orderable_status' => ['sometimes', 'nullable', 'string', 'in:on,off'],
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
