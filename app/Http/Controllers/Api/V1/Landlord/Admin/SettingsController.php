<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Settings\GeneralSettingsRequest;
use App\Http\Requests\Settings\UpdateSingleSettingRequest;
use App\Http\Resources\SettingResource;
use App\Http\Resources\SettingsGroupResource;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Settings Controller
 *
 * Handles application settings management.
 *
 * @package App\Http\Controllers\Api\V1\Landlord\Admin
 */
#[OA\Tag(
    name: 'Settings',
    description: 'Application settings management endpoints'
)]
final class SettingsController extends BaseApiController
{
    /**
     * Create a new controller instance.
     *
     * @param SettingsService $settingsService The settings service
     */
    public function __construct(
        private readonly SettingsService $settingsService
    ) {
        $this->middleware('permission:general-settings-site-identity|general-settings-basic-settings|general-settings-application-settings')
            ->only(['index', 'show', 'getByKey']);
        $this->middleware('permission:general-settings-site-identity|general-settings-basic-settings|general-settings-application-settings')
            ->only(['update', 'updateByKey']);
    }

    /**
     * Get settings by group.
     *
     * Retrieves all settings for a specific group.
     */
    #[OA\Get(
        path: '/api/v1/admin/settings/{group}',
        summary: 'Get settings by group',
        description: 'Retrieves all settings for a specific group (general, email, seo, payment, tenant, appearance, typography, third_party, gdpr, pages)',
        security: [['sanctum_admin' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'group',
                in: 'path',
                required: true,
                description: 'Settings group name',
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['general', 'email', 'seo', 'payment', 'tenant', 'appearance', 'typography', 'third_party', 'gdpr', 'pages']
                )
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Settings retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Settings retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/SettingsGroupResource'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Settings group not found',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: false),
                        new OA\Property(property: 'message', type: 'string', example: 'Settings group not found'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function index(string $group): JsonResponse
    {
        $groups = $this->settingsService->getGroups();

        if (!isset($groups[$group])) {
            return $this->errorResponse('Settings group not found', 404);
        }

        $settings = $this->settingsService->getByGroup($group);
        $keys = $groups[$group];

        $resource = new SettingsGroupResource($group, $settings, $keys);

        return $this->successResponse(
            $resource->toArray(request()),
            'Settings retrieved successfully'
        );
    }

    /**
     * Update settings for a group.
     *
     * Updates multiple settings at once for a specific group.
     */
    #[OA\Put(
        path: '/api/v1/admin/settings/{group}',
        summary: 'Update settings by group',
        description: 'Updates multiple settings at once for a specific group',
        security: [['sanctum_admin' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'group',
                in: 'path',
                required: true,
                description: 'Settings group name',
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['general', 'email', 'seo', 'payment', 'tenant', 'appearance', 'typography', 'third_party', 'gdpr', 'pages']
                )
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/GeneralSettingsRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Settings updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Settings updated successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/SettingsGroupResource'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: 'Invalid settings keys for group'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Settings group not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(GeneralSettingsRequest $request, string $group): JsonResponse
    {
        $groups = $this->settingsService->getGroups();

        if (!isset($groups[$group])) {
            return $this->errorResponse('Settings group not found', 404);
        }

        $settings = $request->validatedSettings();

        // Validate that all keys belong to this group
        $validation = $this->settingsService->validateKeysForGroup($group, array_keys($settings));

        if (!empty($validation['invalid'])) {
            return $this->errorResponse(
                'Invalid settings keys for this group: ' . implode(', ', $validation['invalid']),
                400
            );
        }

        $success = $this->settingsService->setByGroup($group, $settings);

        if (!$success) {
            return $this->errorResponse('Failed to update settings', 500);
        }

        // Return updated settings
        $updatedSettings = $this->settingsService->getByGroup($group);
        $keys = $groups[$group];

        $resource = new SettingsGroupResource($group, $updatedSettings, $keys);

        return $this->successResponse(
            $resource->toArray(request()),
            'Settings updated successfully'
        );
    }

    /**
     * Get a single setting by key.
     *
     * Retrieves a single setting value by its key.
     */
    #[OA\Get(
        path: '/api/v1/admin/settings/key/{key}',
        summary: 'Get single setting by key',
        description: 'Retrieves a single setting value by its key',
        security: [['sanctum_admin' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'key',
                in: 'path',
                required: true,
                description: 'Setting key',
                schema: new OA\Schema(type: 'string'),
                example: 'site_title'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Setting retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Setting retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/SettingResource'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function getByKey(string $key): JsonResponse
    {
        $value = $this->settingsService->get($key);

        // Mask sensitive values
        if ($this->settingsService->isSensitiveKey($key) && $value !== null) {
            $value = '********';
        }

        $group = $this->settingsService->getGroupForKey($key);
        $resource = SettingResource::fromKeyValue($key, $value, $group);

        return $this->successResponse(
            $resource->toArray(request()),
            'Setting retrieved successfully'
        );
    }

    /**
     * Update a single setting by key.
     *
     * Updates a single setting value by its key.
     */
    #[OA\Put(
        path: '/api/v1/admin/settings/key/{key}',
        summary: 'Update single setting by key',
        description: 'Updates a single setting value by its key',
        security: [['sanctum_admin' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'key',
                in: 'path',
                required: true,
                description: 'Setting key',
                schema: new OA\Schema(type: 'string'),
                example: 'site_title'
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateSingleSettingRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Setting updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Setting updated successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/SettingResource'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function updateByKey(UpdateSingleSettingRequest $request, string $key): JsonResponse
    {
        $value = $request->getValue();

        $success = $this->settingsService->set($key, $value);

        if (!$success) {
            return $this->errorResponse('Failed to update setting', 500);
        }

        // Return updated setting (masked if sensitive)
        $displayValue = $this->settingsService->isSensitiveKey($key) && $value !== null
            ? '********'
            : $value;

        $group = $this->settingsService->getGroupForKey($key);
        $resource = SettingResource::fromKeyValue($key, $displayValue, $group);

        return $this->successResponse(
            $resource->toArray(request()),
            'Setting updated successfully'
        );
    }

    /**
     * Get all available settings groups.
     *
     * Returns a list of all available settings groups and their keys.
     */
    #[OA\Get(
        path: '/api/v1/admin/settings',
        summary: 'Get all settings groups',
        description: 'Returns a list of all available settings groups and their keys',
        security: [['sanctum_admin' => []]],
        tags: ['Settings'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Groups retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Settings groups retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'groups',
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                    example: ['general', 'email', 'seo', 'payment']
                                ),
                                new OA\Property(
                                    property: 'keys_by_group',
                                    type: 'object',
                                    additionalProperties: new OA\AdditionalProperties(
                                        type: 'array',
                                        items: new OA\Items(type: 'string')
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function groups(): JsonResponse
    {
        $groups = $this->settingsService->getGroups();

        return $this->successResponse([
            'groups' => array_keys($groups),
            'keys_by_group' => $groups,
        ], 'Settings groups retrieved successfully');
    }

    /**
     * Get all settings.
     *
     * Returns all settings organized by groups.
     */
    #[OA\Get(
        path: '/api/v1/admin/settings/all',
        summary: 'Get all settings',
        description: 'Returns all settings organized by groups',
        security: [['sanctum_admin' => []]],
        tags: ['Settings'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'All settings retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'All settings retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                type: 'object',
                                additionalProperties: new OA\AdditionalProperties()
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function all(): JsonResponse
    {
        $allSettings = $this->settingsService->getAllGrouped();

        return $this->successResponse($allSettings, 'All settings retrieved successfully');
    }

    /**
     * Clear settings cache.
     *
     * Clears all cached settings values.
     */
    #[OA\Post(
        path: '/api/v1/admin/settings/clear-cache',
        summary: 'Clear settings cache',
        description: 'Clears all cached settings values',
        security: [['sanctum_admin' => []]],
        tags: ['Settings'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cache cleared successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Settings cache cleared successfully'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function clearCache(): JsonResponse
    {
        $this->settingsService->clearCache();

        return $this->successResponse(null, 'Settings cache cleared successfully');
    }

    /**
     * Search settings.
     *
     * Search settings by key pattern.
     */
    #[OA\Get(
        path: '/api/v1/admin/settings/search',
        summary: 'Search settings',
        description: 'Search settings by key pattern',
        security: [['sanctum_admin' => []]],
        tags: ['Settings'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                in: 'query',
                required: true,
                description: 'Search query',
                schema: new OA\Schema(type: 'string'),
                example: 'smtp'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Search completed'),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/SettingResource')
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (empty($query)) {
            return $this->errorResponse('Search query is required', 400);
        }

        $results = $this->settingsService->search($query);

        $data = $results->map(function ($option) {
            $value = $option->option_value;

            // Mask sensitive values
            if ($this->settingsService->isSensitiveKey($option->option_name) && $value !== null) {
                $value = '********';
            }

            return [
                'key' => $option->option_name,
                'value' => $value,
                'group' => $this->settingsService->getGroupForKey($option->option_name),
            ];
        });

        return $this->successResponse($data, 'Search completed');
    }
}
