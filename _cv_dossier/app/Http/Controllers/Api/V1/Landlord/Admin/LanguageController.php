<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Language\ImportTranslationsRequest;
use App\Http\Requests\Language\StoreLanguageRequest;
use App\Http\Requests\Language\UpdateLanguageRequest;
use App\Http\Resources\LanguageCollection;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use App\Services\LanguageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Language Controller
 *
 * Handles language management for multi-language support.
 *
 * @package App\Http\Controllers\Api\V1\Landlord\Admin
 */
#[OA\Tag(
    name: 'Languages',
    description: 'Language management endpoints for multi-language support'
)]
final class LanguageController extends BaseApiController
{
    /**
     * Create a new controller instance.
     *
     * @param LanguageService $languageService The language service
     */
    public function __construct(
        private readonly LanguageService $languageService
    ) {
        $this->middleware('permission:language-list')->only(['index', 'show']);
        $this->middleware('permission:language-create')->only(['store']);
        $this->middleware('permission:language-edit')->only(['update', 'setDefault', 'toggleStatus', 'import']);
        $this->middleware('permission:language-delete')->only(['destroy']);
    }

    /**
     * Get all languages.
     *
     * Returns a list of all available languages with their status.
     */
    #[OA\Get(
        path: '/api/v1/admin/languages',
        summary: 'List all languages',
        description: 'Retrieves a list of all available languages with their status and configuration.',
        security: [['sanctum_admin' => []]],
        tags: ['Languages'],
        parameters: [
            new OA\Parameter(
                name: 'active_only',
                in: 'query',
                required: false,
                description: 'Filter to show only active languages',
                schema: new OA\Schema(type: 'boolean', default: false)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Number of items per page',
                schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Languages retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Languages retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/LanguageCollection'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Insufficient permissions'
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $activeOnly = $request->boolean('active_only', false);
        $languages = $this->languageService->getAvailableLanguages($activeOnly);

        return $this->successResponse(
            LanguageResource::collection($languages),
            'Languages retrieved successfully'
        );
    }

    /**
     * Create a new language.
     *
     * Creates a new language with the provided details.
     */
    #[OA\Post(
        path: '/api/v1/admin/languages',
        summary: 'Create a new language',
        description: 'Creates a new language with the provided name, code, direction, and status.',
        security: [['sanctum_admin' => []]],
        tags: ['Languages'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreLanguageRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Language created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Language created successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/LanguageResource'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Insufficient permissions'
            ),
        ]
    )]
    public function store(StoreLanguageRequest $request): JsonResponse
    {
        $language = $this->languageService->createLanguage($request->validated());

        return $this->successResponse(
            new LanguageResource($language),
            'Language created successfully',
            201
        );
    }

    /**
     * Get a specific language.
     *
     * Retrieves details for a specific language including translation stats.
     */
    #[OA\Get(
        path: '/api/v1/admin/languages/{code}',
        summary: 'Get language details',
        description: 'Retrieves detailed information for a specific language including translation statistics.',
        security: [['sanctum_admin' => []]],
        tags: ['Languages'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Language code (slug)',
                schema: new OA\Schema(type: 'string', example: 'en')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Language retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Language retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/LanguageResource'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Language not found'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
        ]
    )]
    public function show(string $code): JsonResponse
    {
        $language = $this->languageService->getLanguageByCode($code);

        if (!$language) {
            return $this->errorResponse('Language not found', 404);
        }

        $stats = $this->languageService->getTranslationStats($code);
        $resource = (new LanguageResource($language))->withTranslationStats($stats);

        return $this->successResponse(
            $resource,
            'Language retrieved successfully'
        );
    }

    /**
     * Update a language.
     *
     * Updates an existing language with the provided details.
     */
    #[OA\Put(
        path: '/api/v1/admin/languages/{code}',
        summary: 'Update a language',
        description: 'Updates an existing language with the provided details.',
        security: [['sanctum_admin' => []]],
        tags: ['Languages'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Language code (slug)',
                schema: new OA\Schema(type: 'string', example: 'en')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateLanguageRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Language updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Language updated successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/LanguageResource'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Language not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Insufficient permissions'
            ),
        ]
    )]
    public function update(UpdateLanguageRequest $request, string $code): JsonResponse
    {
        $language = $this->languageService->getLanguageByCode($code);

        if (!$language) {
            return $this->errorResponse('Language not found', 404);
        }

        $updatedLanguage = $this->languageService->updateLanguage($language, $request->validated());

        return $this->successResponse(
            new LanguageResource($updatedLanguage),
            'Language updated successfully'
        );
    }

    /**
     * Delete a language.
     *
     * Deletes a language (cannot delete the default language).
     */
    #[OA\Delete(
        path: '/api/v1/admin/languages/{code}',
        summary: 'Delete a language',
        description: 'Deletes a language. Cannot delete the default language.',
        security: [['sanctum_admin' => []]],
        tags: ['Languages'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Language code (slug)',
                schema: new OA\Schema(type: 'string', example: 'fr')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Language deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Language deleted successfully'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Language not found'
            ),
            new OA\Response(
                response: 400,
                description: 'Cannot delete the default language'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Insufficient permissions'
            ),
        ]
    )]
    public function destroy(string $code): JsonResponse
    {
        $language = $this->languageService->getLanguageByCode($code);

        if (!$language) {
            return $this->errorResponse('Language not found', 404);
        }

        try {
            $this->languageService->deleteLanguage($language);
            return $this->successResponse(null, 'Language deleted successfully');
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Set a language as default.
     *
     * Sets the specified language as the default language.
     */
    #[OA\Post(
        path: '/api/v1/admin/languages/{code}/set-default',
        summary: 'Set language as default',
        description: 'Sets the specified language as the default language for the application.',
        security: [['sanctum_admin' => []]],
        tags: ['Languages'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Language code (slug)',
                schema: new OA\Schema(type: 'string', example: 'en')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Default language set successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Default language set successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/LanguageResource'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Language not found'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Insufficient permissions'
            ),
        ]
    )]
    public function setDefault(string $code): JsonResponse
    {
        $language = $this->languageService->getLanguageByCode($code);

        if (!$language) {
            return $this->errorResponse('Language not found', 404);
        }

        $updatedLanguage = $this->languageService->setDefaultLanguage($language);

        return $this->successResponse(
            new LanguageResource($updatedLanguage),
            'Default language set successfully'
        );
    }

    /**
     * Toggle language status.
     *
     * Toggles the active/inactive status of a language.
     */
    #[OA\Patch(
        path: '/api/v1/admin/languages/{code}/toggle-status',
        summary: 'Toggle language status',
        description: 'Toggles the active/inactive status of a language. Cannot deactivate the default language.',
        security: [['sanctum_admin' => []]],
        tags: ['Languages'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Language code (slug)',
                schema: new OA\Schema(type: 'string', example: 'fr')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Language status toggled successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Language status toggled successfully'),
                        new OA\Property(
                            property: 'data',
                            ref: '#/components/schemas/LanguageResource'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Language not found'
            ),
            new OA\Response(
                response: 400,
                description: 'Cannot deactivate the default language'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Insufficient permissions'
            ),
        ]
    )]
    public function toggleStatus(string $code): JsonResponse
    {
        $language = $this->languageService->getLanguageByCode($code);

        if (!$language) {
            return $this->errorResponse('Language not found', 404);
        }

        try {
            $updatedLanguage = $this->languageService->toggleStatus($language);
            return $this->successResponse(
                new LanguageResource($updatedLanguage),
                'Language status toggled successfully'
            );
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Export translations.
     *
     * Exports all translations for a language as JSON.
     */
    #[OA\Get(
        path: '/api/v1/admin/languages/{code}/export',
        summary: 'Export translations',
        description: 'Exports all translations for a language as a JSON file.',
        security: [['sanctum_admin' => []]],
        tags: ['Languages'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Language code (slug)',
                schema: new OA\Schema(type: 'string', example: 'ar')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Translations exported successfully',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        type: 'object',
                        additionalProperties: new OA\AdditionalProperties(type: 'string'),
                        example: ['Hello' => 'مرحبا', 'Welcome' => 'أهلاً وسهلاً']
                    )
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Language not found'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
        ]
    )]
    public function export(string $code): JsonResponse
    {
        $language = $this->languageService->getLanguageByCode($code);

        if (!$language) {
            return $this->errorResponse('Language not found', 404);
        }

        $translations = $this->languageService->getTranslations($code);

        return response()->json($translations, 200, [
            'Content-Disposition' => 'attachment; filename="' . $code . '_translations.json"',
        ]);
    }

    /**
     * Import translations.
     *
     * Imports translations from a JSON file or object.
     */
    #[OA\Post(
        path: '/api/v1/admin/languages/{code}/import',
        summary: 'Import translations',
        description: 'Imports translations from a JSON object or file. Can merge with existing translations or replace all.',
        security: [['sanctum_admin' => []]],
        tags: ['Languages'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Language code (slug)',
                schema: new OA\Schema(type: 'string', example: 'ar')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: '#/components/schemas/ImportTranslationsRequest')
                ),
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(
                                property: 'file',
                                type: 'string',
                                format: 'binary',
                                description: 'JSON file containing translations'
                            ),
                            new OA\Property(
                                property: 'merge',
                                type: 'boolean',
                                default: true,
                                description: 'Whether to merge with existing translations'
                            ),
                        ]
                    )
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Translations imported successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Translations imported successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'imported_count', type: 'integer', example: 150),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Language not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Insufficient permissions'
            ),
        ]
    )]
    public function import(ImportTranslationsRequest $request, string $code): JsonResponse
    {
        $language = $this->languageService->getLanguageByCode($code);

        if (!$language) {
            return $this->errorResponse('Language not found', 404);
        }

        $translations = $request->getTranslations();
        $merge = $request->shouldMerge();

        $success = $this->languageService->updateTranslations($code, $translations, $merge);

        if (!$success) {
            return $this->errorResponse('Failed to import translations', 500);
        }

        return $this->successResponse(
            ['imported_count' => count($translations)],
            'Translations imported successfully'
        );
    }

    /**
     * Sync translations with default.
     *
     * Synchronizes translations with the default language file.
     */
    #[OA\Post(
        path: '/api/v1/admin/languages/{code}/sync',
        summary: 'Sync translations',
        description: 'Synchronizes translations with the default language file, adding any missing keys.',
        security: [['sanctum_admin' => []]],
        tags: ['Languages'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Language code (slug)',
                schema: new OA\Schema(type: 'string', example: 'ar')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Translations synchronized successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Translations synchronized successfully'),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Language not found'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Insufficient permissions'
            ),
        ]
    )]
    public function sync(string $code): JsonResponse
    {
        $language = $this->languageService->getLanguageByCode($code);

        if (!$language) {
            return $this->errorResponse('Language not found', 404);
        }

        $this->languageService->syncTranslationFiles($code);

        return $this->successResponse(null, 'Translations synchronized successfully');
    }
}
