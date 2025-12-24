<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Tenant\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Tenant\Settings\AppearanceSettingsRequest;
use App\Http\Requests\Tenant\Settings\EmailSettingsRequest;
use App\Http\Requests\Tenant\Settings\GeneralSettingsRequest;
use App\Http\Requests\Tenant\Settings\SeoSettingsRequest;
use App\Http\Requests\Tenant\Settings\SocialSettingsRequest;
use App\Http\Resources\Tenant\TenantSettingsResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin Settings Controller
 *
 * Handles tenant-specific settings management.
 *
 * @package App\Http\Controllers\Api\V1\Tenant\Admin
 */
#[OA\Tag(
    name: 'Tenant Admin Settings',
    description: 'Tenant admin settings management endpoints'
)]
final class SettingsController extends BaseApiController
{
    /**
     * Settings groups configuration for tenant.
     */
    private const SETTINGS_GROUPS = [
        'general' => [
            'site_title',
            'site_tag_line',
            'site_footer_text',
            'site_timezone',
            'site_date_format',
            'site_time_format',
            'site_currency',
            'site_currency_symbol',
            'site_currency_symbol_position',
            'maintenance_mode',
            'guest_checkout',
            'enable_preloader',
        ],
        'appearance' => [
            'site_logo',
            'site_logo_dark',
            'site_favicon',
            'primary_color',
            'secondary_color',
            'accent_color',
            'header_bg_color',
            'footer_bg_color',
            'body_font_family',
            'heading_font_family',
            'custom_css',
        ],
        'email' => [
            'mail_driver',
            'mail_host',
            'mail_port',
            'mail_username',
            'mail_password',
            'mail_encryption',
            'mail_from_address',
            'mail_from_name',
        ],
        'social' => [
            'social_facebook',
            'social_twitter',
            'social_instagram',
            'social_linkedin',
            'social_youtube',
            'social_pinterest',
            'social_tiktok',
            'social_whatsapp',
            'social_telegram',
        ],
        'seo' => [
            'site_meta_title',
            'site_meta_description',
            'site_meta_keywords',
            'site_og_title',
            'site_og_description',
            'site_og_image',
            'google_analytics_id',
            'google_tag_manager_id',
            'facebook_pixel_id',
            'robots_txt',
        ],
    ];

    /**
     * Get general settings.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/settings/general',
        summary: 'Get general settings',
        description: 'Get tenant general settings including site title, timezone, currency, etc.',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Settings'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'General settings retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'General settings retrieved successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/TenantSettingsResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function general(): JsonResponse
    {
        $settings = $this->getSettingsByGroup('general');

        return $this->success(
            new TenantSettingsResource('general', $settings),
            'General settings retrieved successfully'
        );
    }

    /**
     * Update general settings.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/settings/general',
        summary: 'Update general settings',
        description: 'Update tenant general settings',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Settings'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TenantGeneralSettingsRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'General settings updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'General settings updated successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/TenantSettingsResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateGeneral(GeneralSettingsRequest $request): JsonResponse
    {
        $this->updateSettings($request->validatedSettings());
        $settings = $this->getSettingsByGroup('general');

        return $this->success(
            new TenantSettingsResource('general', $settings),
            'General settings updated successfully'
        );
    }

    /**
     * Get appearance settings.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/settings/appearance',
        summary: 'Get appearance settings',
        description: 'Get tenant appearance settings including colors, logo, favicon',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Settings'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Appearance settings retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Appearance settings retrieved successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/TenantSettingsResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function appearance(): JsonResponse
    {
        $settings = $this->getSettingsByGroup('appearance');

        return $this->success(
            new TenantSettingsResource('appearance', $settings),
            'Appearance settings retrieved successfully'
        );
    }

    /**
     * Update appearance settings.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/settings/appearance',
        summary: 'Update appearance settings',
        description: 'Update tenant appearance settings',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Settings'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TenantAppearanceSettingsRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Appearance settings updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Appearance settings updated successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/TenantSettingsResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateAppearance(AppearanceSettingsRequest $request): JsonResponse
    {
        $this->updateSettings($request->validatedSettings());
        $settings = $this->getSettingsByGroup('appearance');

        return $this->success(
            new TenantSettingsResource('appearance', $settings),
            'Appearance settings updated successfully'
        );
    }

    /**
     * Get email settings.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/settings/email',
        summary: 'Get email settings',
        description: 'Get tenant email/SMTP settings',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Settings'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email settings retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Email settings retrieved successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/TenantSettingsResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function email(): JsonResponse
    {
        $settings = $this->getSettingsByGroup('email');
        
        // Mask sensitive data
        if (isset($settings['mail_password'])) {
            $settings['mail_password'] = $settings['mail_password'] ? '********' : null;
        }

        return $this->success(
            new TenantSettingsResource('email', $settings),
            'Email settings retrieved successfully'
        );
    }

    /**
     * Update email settings.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/settings/email',
        summary: 'Update email settings',
        description: 'Update tenant email/SMTP settings',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Settings'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TenantEmailSettingsRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email settings updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Email settings updated successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/TenantSettingsResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateEmail(EmailSettingsRequest $request): JsonResponse
    {
        $data = $request->validatedSettings();
        
        // Don't update password if it's the masked value
        if (isset($data['mail_password']) && $data['mail_password'] === '********') {
            unset($data['mail_password']);
        }

        $this->updateSettings($data);
        $settings = $this->getSettingsByGroup('email');
        
        // Mask sensitive data
        if (isset($settings['mail_password'])) {
            $settings['mail_password'] = $settings['mail_password'] ? '********' : null;
        }

        return $this->success(
            new TenantSettingsResource('email', $settings),
            'Email settings updated successfully'
        );
    }

    /**
     * Get social media settings.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/settings/social',
        summary: 'Get social media settings',
        description: 'Get tenant social media links',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Settings'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Social media settings retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Social media settings retrieved successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/TenantSettingsResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function social(): JsonResponse
    {
        $settings = $this->getSettingsByGroup('social');

        return $this->success(
            new TenantSettingsResource('social', $settings),
            'Social media settings retrieved successfully'
        );
    }

    /**
     * Update social media settings.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/settings/social',
        summary: 'Update social media settings',
        description: 'Update tenant social media links',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Settings'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TenantSocialSettingsRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Social media settings updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Social media settings updated successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/TenantSettingsResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateSocial(SocialSettingsRequest $request): JsonResponse
    {
        $this->updateSettings($request->validatedSettings());
        $settings = $this->getSettingsByGroup('social');

        return $this->success(
            new TenantSettingsResource('social', $settings),
            'Social media settings updated successfully'
        );
    }

    /**
     * Get SEO settings.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/settings/seo',
        summary: 'Get SEO settings',
        description: 'Get tenant SEO default settings',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Settings'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'SEO settings retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'SEO settings retrieved successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/TenantSettingsResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function seo(): JsonResponse
    {
        $settings = $this->getSettingsByGroup('seo');

        return $this->success(
            new TenantSettingsResource('seo', $settings),
            'SEO settings retrieved successfully'
        );
    }

    /**
     * Update SEO settings.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/settings/seo',
        summary: 'Update SEO settings',
        description: 'Update tenant SEO default settings',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Settings'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TenantSeoSettingsRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'SEO settings updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'SEO settings updated successfully'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/TenantSettingsResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateSeo(SeoSettingsRequest $request): JsonResponse
    {
        $this->updateSettings($request->validatedSettings());
        $settings = $this->getSettingsByGroup('seo');

        return $this->success(
            new TenantSettingsResource('seo', $settings),
            'SEO settings updated successfully'
        );
    }

    /**
     * Get all settings.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/settings',
        summary: 'Get all settings',
        description: 'Get all tenant settings grouped by category',
        security: [['sanctum_user' => []]],
        tags: ['Tenant Admin Settings'],
        parameters: [
            new OA\Parameter(
                name: 'tenant',
                in: 'path',
                required: true,
                description: 'Tenant ID',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'All settings retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Settings retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'groups', type: 'array', items: new OA\Items(type: 'string'), example: ['general', 'appearance', 'email', 'social', 'seo']),
                                new OA\Property(property: 'settings', type: 'object'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index(): JsonResponse
    {
        $allSettings = [];
        
        foreach (self::SETTINGS_GROUPS as $group => $keys) {
            $settings = $this->getSettingsByGroup($group);
            
            // Mask sensitive data
            if ($group === 'email' && isset($settings['mail_password'])) {
                $settings['mail_password'] = $settings['mail_password'] ? '********' : null;
            }
            
            $allSettings[$group] = $settings;
        }

        return $this->success([
            'groups' => array_keys(self::SETTINGS_GROUPS),
            'settings' => $allSettings,
        ], 'Settings retrieved successfully');
    }

    /**
     * Get settings by group from tenant database.
     *
     * @param string $group
     * @return array<string, mixed>
     */
    private function getSettingsByGroup(string $group): array
    {
        $keys = self::SETTINGS_GROUPS[$group] ?? [];
        
        if (empty($keys)) {
            return [];
        }

        // Check if static_options table exists
        if (!$this->tableExists('static_options')) {
            return array_fill_keys($keys, null);
        }

        $settings = DB::table('static_options')
            ->whereIn('option_name', $keys)
            ->pluck('option_value', 'option_name')
            ->toArray();

        // Fill missing keys with null
        foreach ($keys as $key) {
            if (!isset($settings[$key])) {
                $settings[$key] = null;
            }
        }

        return $settings;
    }

    /**
     * Update settings in tenant database.
     *
     * @param array<string, mixed> $settings
     * @return void
     */
    private function updateSettings(array $settings): void
    {
        if (!$this->tableExists('static_options')) {
            return;
        }

        foreach ($settings as $key => $value) {
            DB::table('static_options')->updateOrInsert(
                ['option_name' => $key],
                ['option_value' => $value]
            );
        }
    }

    /**
     * Check if a table exists.
     */
    private function tableExists(string $table): bool
    {
        return \Illuminate\Support\Facades\Schema::hasTable($table);
    }
}
