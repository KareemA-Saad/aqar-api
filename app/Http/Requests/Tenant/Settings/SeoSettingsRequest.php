<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Tenant SEO Settings Request
 *
 * Validates tenant SEO settings update data.
 */
#[OA\Schema(
    schema: 'TenantSeoSettingsRequest',
    title: 'Tenant SEO Settings Request',
    description: 'Request body for updating tenant SEO settings',
    properties: [
        new OA\Property(
            property: 'settings',
            type: 'object',
            properties: [
                new OA\Property(property: 'site_meta_title', type: 'string', example: 'My Store - Best Products Online'),
                new OA\Property(property: 'site_meta_description', type: 'string', example: 'Find the best products at affordable prices'),
                new OA\Property(property: 'site_meta_keywords', type: 'string', example: 'store, ecommerce, products, online shopping'),
                new OA\Property(property: 'site_og_title', type: 'string', example: 'My Store'),
                new OA\Property(property: 'site_og_description', type: 'string', example: 'Your one-stop shop for everything'),
                new OA\Property(property: 'site_og_image', type: 'integer', example: 1),
                new OA\Property(property: 'google_analytics_id', type: 'string', example: 'G-XXXXXXXXXX'),
                new OA\Property(property: 'google_tag_manager_id', type: 'string', example: 'GTM-XXXXXXX'),
                new OA\Property(property: 'facebook_pixel_id', type: 'string', example: '1234567890'),
                new OA\Property(property: 'robots_txt', type: 'string', example: 'User-agent: *\nAllow: /'),
            ]
        ),
    ]
)]
final class SeoSettingsRequest extends FormRequest
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
            'settings.site_meta_title' => ['sometimes', 'nullable', 'string', 'max:191'],
            'settings.site_meta_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'settings.site_meta_keywords' => ['sometimes', 'nullable', 'string', 'max:500'],
            'settings.site_og_title' => ['sometimes', 'nullable', 'string', 'max:191'],
            'settings.site_og_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'settings.site_og_image' => ['sometimes', 'nullable', 'integer'],
            'settings.google_analytics_id' => ['sometimes', 'nullable', 'string', 'max:50'],
            'settings.google_tag_manager_id' => ['sometimes', 'nullable', 'string', 'max:50'],
            'settings.facebook_pixel_id' => ['sometimes', 'nullable', 'string', 'max:50'],
            'settings.robots_txt' => ['sometimes', 'nullable', 'string', 'max:5000'],
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
            'settings.site_meta_title.max' => 'Meta title must not exceed 191 characters.',
            'settings.site_meta_description.max' => 'Meta description must not exceed 500 characters.',
            'settings.robots_txt.max' => 'Robots.txt content must not exceed 5000 characters.',
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
