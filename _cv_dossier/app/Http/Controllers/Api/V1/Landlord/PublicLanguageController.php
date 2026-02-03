<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Resources\LanguageResource;
use App\Services\LanguageService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Public Language Controller
 *
 * Handles public language endpoints (no authentication required).
 *
 * @package App\Http\Controllers\Api\V1\Landlord
 */
#[OA\Tag(
    name: 'Public Languages',
    description: 'Public language endpoints (no authentication required)'
)]
final class PublicLanguageController extends BaseApiController
{
    /**
     * Create a new controller instance.
     *
     * @param LanguageService $languageService The language service
     */
    public function __construct(
        private readonly LanguageService $languageService
    ) {}

    /**
     * Get all active languages.
     *
     * Returns a list of all active languages for frontend use.
     */
    #[OA\Get(
        path: '/api/v1/languages',
        summary: 'List active languages',
        description: 'Retrieves a list of all active languages available for the application. No authentication required.',
        tags: ['Public Languages'],
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
                            properties: [
                                new OA\Property(
                                    property: 'languages',
                                    type: 'array',
                                    items: new OA\Items(ref: '#/components/schemas/LanguageResource')
                                ),
                                new OA\Property(
                                    property: 'default',
                                    ref: '#/components/schemas/LanguageResource'
                                ),
                                new OA\Property(property: 'current', type: 'string', example: 'en'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
        ]
    )]
    public function index(): JsonResponse
    {
        $languages = $this->languageService->getAvailableLanguages(activeOnly: true);
        $defaultLanguage = $this->languageService->getDefaultLanguage();

        return $this->successResponse([
            'languages' => LanguageResource::collection($languages),
            'default' => $defaultLanguage ? new LanguageResource($defaultLanguage) : null,
            'current' => app()->getLocale(),
        ], 'Languages retrieved successfully');
    }

    /**
     * Get the current language.
     *
     * Returns the currently active language based on headers/session.
     */
    #[OA\Get(
        path: '/api/v1/languages/current',
        summary: 'Get current language',
        description: 'Returns the currently active language based on request headers or session.',
        tags: ['Public Languages'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Current language retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Current language retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'locale', type: 'string', example: 'en'),
                                new OA\Property(
                                    property: 'language',
                                    ref: '#/components/schemas/LanguageResource',
                                    nullable: true
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
        ]
    )]
    public function current(): JsonResponse
    {
        $locale = app()->getLocale();
        $language = $this->languageService->getLanguageByCode($locale);

        return $this->successResponse([
            'locale' => $locale,
            'language' => $language ? new LanguageResource($language) : null,
        ], 'Current language retrieved successfully');
    }

    /**
     * Get translations for a language.
     *
     * Returns all translations for a specific language code.
     */
    #[OA\Get(
        path: '/api/v1/languages/{code}/translations',
        summary: 'Get public translations',
        description: 'Returns all translations for a specific language code. Useful for frontend localization.',
        tags: ['Public Languages'],
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
                description: 'Translations retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Translations retrieved successfully'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'language_code', type: 'string', example: 'ar'),
                                new OA\Property(
                                    property: 'translations',
                                    type: 'object',
                                    additionalProperties: new OA\AdditionalProperties(type: 'string')
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Language not found or inactive'
            ),
        ]
    )]
    public function translations(string $code): JsonResponse
    {
        $language = $this->languageService->getLanguageByCode($code);

        if (!$language || !$language->status) {
            return $this->errorResponse('Language not found or inactive', 404);
        }

        $translations = $this->languageService->getTranslations($code);

        return $this->successResponse([
            'language_code' => $code,
            'translations' => $translations,
        ], 'Translations retrieved successfully');
    }
}
