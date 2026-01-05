<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;
use OpenApi\Attributes as OA;

/**
 * Tenant Social Settings Request
 *
 * Validates tenant social media settings update data.
 */
#[OA\Schema(
    schema: 'TenantSocialSettingsRequest',
    title: 'Tenant Social Settings Request',
    description: 'Request body for updating tenant social media settings',
    properties: [
        new OA\Property(
            property: 'settings',
            type: 'object',
            properties: [
                new OA\Property(property: 'social_facebook', type: 'string', format: 'url', example: 'https://facebook.com/mystore'),
                new OA\Property(property: 'social_twitter', type: 'string', format: 'url', example: 'https://twitter.com/mystore'),
                new OA\Property(property: 'social_instagram', type: 'string', format: 'url', example: 'https://instagram.com/mystore'),
                new OA\Property(property: 'social_linkedin', type: 'string', format: 'url', example: 'https://linkedin.com/company/mystore'),
                new OA\Property(property: 'social_youtube', type: 'string', format: 'url', example: 'https://youtube.com/mystore'),
                new OA\Property(property: 'social_pinterest', type: 'string', format: 'url', example: 'https://pinterest.com/mystore'),
                new OA\Property(property: 'social_tiktok', type: 'string', format: 'url', example: 'https://tiktok.com/@mystore'),
                new OA\Property(property: 'social_whatsapp', type: 'string', example: '+1234567890'),
                new OA\Property(property: 'social_telegram', type: 'string', example: '@mystore'),
            ]
        ),
    ]
)]
final class SocialSettingsRequest extends FormRequest
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
            'settings.social_facebook' => ['sometimes', 'nullable', 'string', 'max:500', 'url'],
            'settings.social_twitter' => ['sometimes', 'nullable', 'string', 'max:500', 'url'],
            'settings.social_instagram' => ['sometimes', 'nullable', 'string', 'max:500', 'url'],
            'settings.social_linkedin' => ['sometimes', 'nullable', 'string', 'max:500', 'url'],
            'settings.social_youtube' => ['sometimes', 'nullable', 'string', 'max:500', 'url'],
            'settings.social_pinterest' => ['sometimes', 'nullable', 'string', 'max:500', 'url'],
            'settings.social_tiktok' => ['sometimes', 'nullable', 'string', 'max:500', 'url'],
            'settings.social_whatsapp' => ['sometimes', 'nullable', 'string', 'max:50'],
            'settings.social_telegram' => ['sometimes', 'nullable', 'string', 'max:100'],
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
            'settings.social_facebook.url' => 'Facebook link must be a valid URL.',
            'settings.social_twitter.url' => 'Twitter link must be a valid URL.',
            'settings.social_instagram.url' => 'Instagram link must be a valid URL.',
            'settings.social_linkedin.url' => 'LinkedIn link must be a valid URL.',
            'settings.social_youtube.url' => 'YouTube link must be a valid URL.',
            'settings.social_pinterest.url' => 'Pinterest link must be a valid URL.',
            'settings.social_tiktok.url' => 'TikTok link must be a valid URL.',
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
