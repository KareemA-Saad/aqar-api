<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\Template;
use Modules\RealEstate\Http\Controllers\BaseController;
use Modules\RealEstate\Services\TemplateService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Canned Templates', description: 'Manage bilingual canned response templates')]
class TemplateController extends BaseController
{
    public function __construct(
        protected TemplateService $templateService
    ) {}

    // =========================================================================
    // GET /admin/realestate/templates
    // =========================================================================

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/templates',
        summary: 'List all templates',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Canned Templates'],
        parameters: [
            new OA\Parameter(name: 'category', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'is_active', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated template list'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->templateService->getPaginated($request->only([
            'category', 'is_active', 'search', 'per_page',
        ]));

        return response()->json([
            'success' => true,
            'data'    => $paginator->items(),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    // =========================================================================
    // POST /admin/realestate/templates
    // =========================================================================

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/realestate/templates',
        summary: 'Create a template',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Canned Templates'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'body_en', 'body_ar'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Property Follow-Up'),
                    new OA\Property(property: 'category', type: 'string', example: 'follow_up'),
                    new OA\Property(property: 'body_en', type: 'string', example: 'Hi {{user.name}}, thank you for your interest in {{property.title}}.'),
                    new OA\Property(property: 'body_ar', type: 'string', example: 'مرحبا {{user.name}}، شكراً لاهتمامك بـ {{property.title}}.'),
                    new OA\Property(property: 'is_active', type: 'boolean', default: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Template created'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:255',
            'category'  => 'nullable|string|max:100',
            'body_en'   => 'required|string|max:5000',
            'body_ar'   => 'required|string|max:5000',
            'is_active' => 'boolean',
        ]);

        $template = $this->templateService->create($data);

        return response()->json(['success' => true, 'data' => $template], 201);
    }

    // =========================================================================
    // GET /admin/realestate/templates/{id}
    // =========================================================================

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/realestate/templates/{id}',
        summary: 'Get a single template',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Canned Templates'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Template found'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $template = Template::find($id);

        if (!$template) {
            return $this->notFound('Template not found.');
        }

        return response()->json(['success' => true, 'data' => $template]);
    }

    // =========================================================================
    // PUT /admin/realestate/templates/{id}
    // =========================================================================

    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/realestate/templates/{id}',
        summary: 'Update a template',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Canned Templates'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'category', type: 'string'),
                    new OA\Property(property: 'body_en', type: 'string'),
                    new OA\Property(property: 'body_ar', type: 'string'),
                    new OA\Property(property: 'is_active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Template updated'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function update(Request $request, int $id): JsonResponse
    {
        $template = Template::find($id);

        if (!$template) {
            return $this->notFound('Template not found.');
        }

        $data = $request->validate([
            'name'      => 'sometimes|string|max:255',
            'category'  => 'nullable|string|max:100',
            'body_en'   => 'sometimes|string|max:5000',
            'body_ar'   => 'sometimes|string|max:5000',
            'is_active' => 'boolean',
        ]);

        $template = $this->templateService->update($template, $data);

        return response()->json(['success' => true, 'data' => $template]);
    }

    // =========================================================================
    // DELETE /admin/realestate/templates/{id}
    // =========================================================================

    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/realestate/templates/{id}',
        summary: 'Delete a template',
        security: [['sanctum_tenant_admin' => []]],
        tags: ['Admin - Canned Templates'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Deleted'),
            new OA\Response(response: 404, description: 'Not found'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $template = Template::find($id);

        if (!$template) {
            return $this->notFound('Template not found.');
        }

        $this->templateService->delete($template);

        return response()->json(['success' => true, 'message' => 'Template deleted.']);
    }
}
