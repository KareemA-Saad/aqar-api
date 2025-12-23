<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Language\UpdateTranslationsRequest;
use App\Http\Resources\TranslationResource;
use App\Services\LanguageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Translation Controller
 *
 * Handles translation management for languages.
 *
 * @package App\Http\Controllers\Api\V1\Landlord\Admin
 */
#[OA\Tag(
    name: 'Translations',
    description: 'Translation management endpoints for language localization'
)]
final class TranslationController extends BaseApiController
{
    /**
     * Create a new controller instance.
     *
     * @param LanguageService $languageService The language service
     */
    public function __construct(
        private readonly LanguageService $languageService
    ) {
        $this->middleware('permission:language-list')->only(['index', 'search', 'missingKeys']);
        $this->middleware('permission:language-edit')->only(['update']);
    }

    /**
     * Get translations for a language.
     *
     * Returns translations optionally filtered by group.
     */
    #[OA\Get(
        path: '/api/v1/admin/languages/{code}/translations',
        summary: 'Get translations',
        description: 'Retrieves all translations for a language, optionally filtered by group.',
        security: [['sanctum_admin' => []]],
        tags: ['Translations'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Language code (slug)',
                schema: new OA\Schema(type: 'string', example: 'ar')
            ),
            new OA\Parameter(
                name: 'group',
                in: 'query',
                required: false,
                description: 'Filter translations by group/namespace',
                schema: new OA\Schema(type: 'string', example: 'admin')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number for pagination',
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Number of items per page',
                schema: new OA\Schema(type: 'integer', default: 50, maximum: 200)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Translations retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Translations retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'language_code', type: 'string', example: 'ar'),
                                new OA\Property(property: 'group', type: 'string', nullable: true, example: null),
                                new OA\Property(property: 'total_keys', type: 'integer', example: 150),
                                new OA\Property(property: 'translated_keys', type: 'integer', example: 145),
                                new OA\Property(property: 'missing_keys', type: 'integer', example: 5),
                                new OA\Property(property: 'completion_percentage', type: 'number', example: 96.67),
                                new OA\Property(
                                    property: 'translations',
                                    type: 'object',
                                    additionalProperties: new OA\AdditionalProperties(type: 'string')
                                ),
                                new OA\Property(
                                    property: 'pagination',
                                    properties: [
                                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                        new OA\Property(property: 'per_page', type: 'integer', example: 50),
                                        new OA\Property(property: 'total', type: 'integer', example: 150),
                                        new OA\Property(property: 'total_pages', type: 'integer', example: 3),
                                    ],
                                    type: 'object'
                                ),
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
                response: 401,
                description: 'Unauthenticated'
            ),
        ]
    )]
    public function index(Request $request, string $code): JsonResponse
    {
        $language = $this->languageService->getLanguageByCode($code);

        if (!$language) {
            return $this->errorResponse('Language not found', 404);
        }

        $group = $request->input('group');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(200, max(1, (int) $request->input('per_page', 50)));

        $translations = $this->languageService->getTranslations($code, $group);
        $defaultTranslations = $this->languageService->getDefaultTranslations();

        // Calculate stats
        $totalKeys = count($defaultTranslations);
        $translatedKeys = 0;
        foreach ($defaultTranslations as $key => $value) {
            if (isset($translations[$key]) && $translations[$key] !== '' && $translations[$key] !== $value) {
                $translatedKeys++;
            }
        }

        // Paginate translations
        $allKeys = array_keys($translations);
        $totalItems = count($allKeys);
        $totalPages = (int) ceil($totalItems / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedKeys = array_slice($allKeys, $offset, $perPage);
        
        $paginatedTranslations = [];
        foreach ($paginatedKeys as $key) {
            $paginatedTranslations[$key] = $translations[$key];
        }

        return $this->successResponse([
            'language_code' => $code,
            'group' => $group,
            'total_keys' => $totalKeys,
            'translated_keys' => $translatedKeys,
            'missing_keys' => $totalKeys - $translatedKeys,
            'completion_percentage' => $totalKeys > 0 ? round(($translatedKeys / $totalKeys) * 100, 2) : 0,
            'translations' => $paginatedTranslations,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalItems,
                'total_pages' => $totalPages,
            ],
        ], 'Translations retrieved successfully');
    }

    /**
     * Bulk update translations.
     *
     * Updates multiple translations at once.
     */
    #[OA\Put(
        path: '/api/v1/admin/languages/{code}/translations',
        summary: 'Bulk update translations',
        description: 'Updates multiple translations at once for a specific language.',
        security: [['sanctum_admin' => []]],
        tags: ['Translations'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Language code (slug)',
                schema: new OA\Schema(type: 'string', example: 'ar')
            ),
            new OA\Parameter(
                name: 'group',
                in: 'query',
                required: false,
                description: 'Translation group/namespace (for prefixing keys)',
                schema: new OA\Schema(type: 'string', example: 'admin')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateTranslationsRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Translations updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Translations updated successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'updated_count', type: 'integer', example: 10),
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
    public function update(UpdateTranslationsRequest $request, string $code): JsonResponse
    {
        $language = $this->languageService->getLanguageByCode($code);

        if (!$language) {
            return $this->errorResponse('Language not found', 404);
        }

        $translations = $request->getTranslations();
        $group = $request->input('group');

        // If group is specified, prefix keys
        if ($group) {
            $prefixedTranslations = [];
            foreach ($translations as $key => $value) {
                $prefixedTranslations[$group . '.' . $key] = $value;
            }
            $translations = $prefixedTranslations;
        }

        $success = $this->languageService->updateTranslations($code, $translations, true);

        if (!$success) {
            return $this->errorResponse('Failed to update translations', 500);
        }

        return $this->successResponse(
            ['updated_count' => count($translations)],
            'Translations updated successfully'
        );
    }

    /**
     * Search translations.
     *
     * Searches translations by key or value.
     */
    #[OA\Get(
        path: '/api/v1/admin/languages/{code}/translations/search',
        summary: 'Search translations',
        description: 'Searches translations by key or value for a specific language.',
        security: [['sanctum_admin' => []]],
        tags: ['Translations'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Language code (slug)',
                schema: new OA\Schema(type: 'string', example: 'ar')
            ),
            new OA\Parameter(
                name: 'q',
                in: 'query',
                required: true,
                description: 'Search query string',
                schema: new OA\Schema(type: 'string', minLength: 2, example: 'login')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Search results retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Search results retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'query', type: 'string', example: 'login'),
                                new OA\Property(property: 'results_count', type: 'integer', example: 5),
                                new OA\Property(
                                    property: 'results',
                                    type: 'object',
                                    additionalProperties: new OA\AdditionalProperties(type: 'string'),
                                    example: ['Login' => 'تسجيل الدخول', 'Login Button' => 'زر تسجيل الدخول']
                                ),
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
                description: 'Search query is required and must be at least 2 characters'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
        ]
    )]
    public function search(Request $request, string $code): JsonResponse
    {
        $language = $this->languageService->getLanguageByCode($code);

        if (!$language) {
            return $this->errorResponse('Language not found', 404);
        }

        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return $this->errorResponse('Search query must be at least 2 characters', 422);
        }

        $results = $this->languageService->searchTranslations($code, $query);

        return $this->successResponse([
            'query' => $query,
            'results_count' => count($results),
            'results' => $results,
        ], 'Search results retrieved successfully');
    }

    /**
     * Get missing translation keys.
     *
     * Returns keys that need translation (missing or same as source).
     */
    #[OA\Get(
        path: '/api/v1/admin/languages/{code}/translations/missing',
        summary: 'Get missing translations',
        description: 'Returns translation keys that are missing or untranslated (same as source language).',
        security: [['sanctum_admin' => []]],
        tags: ['Translations'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Language code (slug)',
                schema: new OA\Schema(type: 'string', example: 'ar')
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                description: 'Page number for pagination',
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                description: 'Number of items per page',
                schema: new OA\Schema(type: 'integer', default: 50, maximum: 200)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Missing translations retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Missing translations retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'language_code', type: 'string', example: 'ar'),
                                new OA\Property(property: 'total_missing', type: 'integer', example: 25),
                                new OA\Property(
                                    property: 'missing_keys',
                                    type: 'object',
                                    description: 'Key-value pairs where value is the source text',
                                    additionalProperties: new OA\AdditionalProperties(type: 'string')
                                ),
                                new OA\Property(
                                    property: 'pagination',
                                    properties: [
                                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                                        new OA\Property(property: 'per_page', type: 'integer', example: 50),
                                        new OA\Property(property: 'total', type: 'integer', example: 25),
                                        new OA\Property(property: 'total_pages', type: 'integer', example: 1),
                                    ],
                                    type: 'object'
                                ),
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
                response: 401,
                description: 'Unauthenticated'
            ),
        ]
    )]
    public function missingKeys(Request $request, string $code): JsonResponse
    {
        $language = $this->languageService->getLanguageByCode($code);

        if (!$language) {
            return $this->errorResponse('Language not found', 404);
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(200, max(1, (int) $request->input('per_page', 50)));

        $missingKeys = $this->languageService->getMissingKeys($code);

        // Paginate missing keys
        $allKeys = array_keys($missingKeys);
        $totalItems = count($allKeys);
        $totalPages = (int) ceil($totalItems / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedKeys = array_slice($allKeys, $offset, $perPage);

        $paginatedMissing = [];
        foreach ($paginatedKeys as $key) {
            $paginatedMissing[$key] = $missingKeys[$key];
        }

        return $this->successResponse([
            'language_code' => $code,
            'total_missing' => $totalItems,
            'missing_keys' => $paginatedMissing,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $totalItems,
                'total_pages' => $totalPages,
            ],
        ], 'Missing translations retrieved successfully');
    }

    /**
     * Update a single translation.
     *
     * Updates a single translation key-value pair.
     */
    #[OA\Patch(
        path: '/api/v1/admin/languages/{code}/translations/{key}',
        summary: 'Update single translation',
        description: 'Updates a single translation key-value pair.',
        security: [['sanctum_admin' => []]],
        tags: ['Translations'],
        parameters: [
            new OA\Parameter(
                name: 'code',
                in: 'path',
                required: true,
                description: 'Language code (slug)',
                schema: new OA\Schema(type: 'string', example: 'ar')
            ),
            new OA\Parameter(
                name: 'key',
                in: 'path',
                required: true,
                description: 'Translation key (URL encoded)',
                schema: new OA\Schema(type: 'string', example: 'Welcome')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['value'],
                properties: [
                    new OA\Property(
                        property: 'value',
                        type: 'string',
                        description: 'Translated value',
                        example: 'أهلاً وسهلاً'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Translation updated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Translation updated successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'key', type: 'string', example: 'Welcome'),
                                new OA\Property(property: 'value', type: 'string', example: 'أهلاً وسهلاً'),
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
    public function updateSingle(Request $request, string $code, string $key): JsonResponse
    {
        $language = $this->languageService->getLanguageByCode($code);

        if (!$language) {
            return $this->errorResponse('Language not found', 404);
        }

        $request->validate([
            'value' => ['required', 'string'],
        ]);

        // URL decode the key
        $key = urldecode($key);
        $value = $request->input('value');

        $success = $this->languageService->updateTranslations($code, [$key => $value], true);

        if (!$success) {
            return $this->errorResponse('Failed to update translation', 500);
        }

        return $this->successResponse([
            'key' => $key,
            'value' => $value,
        ], 'Translation updated successfully');
    }
}
