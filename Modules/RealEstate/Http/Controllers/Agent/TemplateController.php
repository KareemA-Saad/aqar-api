<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Agent;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\PropertyInquiry;
use Modules\RealEstate\Entities\Template;
use Modules\RealEstate\Http\Controllers\BaseController;
use Modules\RealEstate\Services\TemplateService;
use OpenApi\Attributes as OA;

/**
 * Agent Template Controller — F2.4 Canned Response Templates
 *
 * Endpoints:
 *   GET  /agent/realestate/templates                       → list active templates
 *   POST /agent/realestate/templates/{id}/preview          → resolve for inquiry
 */
#[OA\Tag(name: 'Agent - Canned Templates', description: 'Canned response templates for agents')]
class TemplateController extends BaseController
{
    public function __construct(
        protected TemplateService $templateService
    ) {}

    // =========================================================================
    // GET /agent/realestate/templates
    // =========================================================================

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/agent/realestate/templates',
        summary: 'List active templates',
        description: 'Returns all active canned response templates. Optionally filter by category.',
        tags: ['Agent - Canned Templates'],
        parameters: [
            new OA\Parameter(name: 'category', in: 'query', description: 'Filter by category', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of active templates'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $category  = $request->query('category') ?: null;
        $templates = $this->templateService->getActive($category);
        $categories = $this->templateService->getCategories();

        return response()->json([
            'success'    => true,
            'data'       => $templates,
            'categories' => $categories,
        ]);
    }

    // =========================================================================
    // POST /agent/realestate/templates/{id}/preview
    // =========================================================================

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/agent/realestate/templates/{id}/preview',
        summary: 'Preview template with inquiry variables resolved',
        description: 'Resolves {{user.name}}, {{property.title}}, {{property.price}}, {{compound.name}}, {{agent.name}} for the given inquiry. Returns both EN and AR variants.',
        tags: ['Agent - Canned Templates'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['inquiry_id'],
                properties: [
                    new OA\Property(property: 'inquiry_id', type: 'integer', description: 'Inquiry to resolve variables against', example: 42),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Resolved template text',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'en', type: 'string'),
                            new OA\Property(property: 'ar', type: 'string'),
                            new OA\Property(property: 'variables_used', type: 'object'),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Template or inquiry not found'),
        ]
    )]
    public function preview(Request $request, int $id): JsonResponse
    {
        $data = $request->validate([
            'inquiry_id' => 'required|integer',
        ]);

        $template = Template::whereActive(true)->find($id);

        if (!$template) {
            return $this->notFound('Template not found or is inactive.');
        }

        $agentId = auth()->id();

        // Only allow agents to preview against their own assigned inquiries
        $inquiry = PropertyInquiry::where('id', $data['inquiry_id'])
            ->where('agent_id', $agentId)
            ->first();

        if (!$inquiry) {
            return $this->notFound('Inquiry not found or not assigned to you.');
        }

        $resolved = $this->templateService->resolveForInquiry($template, $inquiry);

        return response()->json([
            'success' => true,
            'data'    => $resolved,
        ]);
    }
}
