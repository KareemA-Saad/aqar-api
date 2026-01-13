<?php

declare(strict_types=1);

namespace Modules\EmailTemplate\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\EmailTemplate\Http\Requests\BulkEmailTemplateRequest;
use Modules\EmailTemplate\Http\Requests\StoreEmailTemplateRequest;
use Modules\EmailTemplate\Http\Requests\UpdateEmailTemplateRequest;
use Modules\EmailTemplate\Http\Resources\EmailTemplateResource;
use Modules\EmailTemplate\Services\EmailTemplateService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Email Template', description: 'Email template management endpoints')]
class EmailTemplateController extends Controller
{
    public function __construct(
        private readonly EmailTemplateService $emailTemplateService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/email-templates',
        summary: 'Get paginated list of email templates',
        tags: ['Admin - Email Template'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1])),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['name', 'type', 'created_at'])),
            new OA\Parameter(name: 'sort_order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email templates retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/EmailTemplateResource')),
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(property: 'links', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $templates = $this->emailTemplateService->getTemplates($request->all());
        
        return response()->json([
            'success' => true,
            'data' => EmailTemplateResource::collection($templates->items()),
            'meta' => [
                'current_page' => $templates->currentPage(),
                'last_page' => $templates->lastPage(),
                'per_page' => $templates->perPage(),
                'total' => $templates->total(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/email-templates',
        summary: 'Create a new email template',
        tags: ['Admin - Email Template'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreEmailTemplateRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Email template created successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'id', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StoreEmailTemplateRequest $request): JsonResponse
    {
        $id = $this->emailTemplateService->createTemplate($request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Email template created successfully',
            'id' => $id,
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/admin/email-templates/{id}',
        summary: 'Get email template by ID',
        tags: ['Admin - Email Template'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email template retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/EmailTemplateResource')
            ),
            new OA\Response(response: 404, description: 'Email template not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $template = $this->emailTemplateService->getTemplateById($id);
        
        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'Email template not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new EmailTemplateResource($template),
        ]);
    }

    #[OA\Put(
        path: '/api/v1/admin/email-templates/{id}',
        summary: 'Update email template',
        tags: ['Admin - Email Template'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateEmailTemplateRequest')
        ),
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Email template updated successfully'),
            new OA\Response(response: 404, description: 'Email template not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(UpdateEmailTemplateRequest $request, int $id): JsonResponse
    {
        $updated = $this->emailTemplateService->updateTemplate($id, $request->validated());
        
        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Email template not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email template updated successfully',
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/email-templates/{id}',
        summary: 'Delete email template',
        tags: ['Admin - Email Template'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Email template deleted successfully'),
            new OA\Response(response: 404, description: 'Email template not found'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->emailTemplateService->deleteTemplate($id);
        
        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Email template not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email template deleted successfully',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/email-templates/bulk',
        summary: 'Perform bulk operations on email templates',
        tags: ['Admin - Email Template'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BulkEmailTemplateRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Bulk operation completed successfully'),
        ]
    )]
    public function bulkAction(BulkEmailTemplateRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $templateIds = $validated['template_ids'];

        $count = match ($action) {
            'delete' => $this->emailTemplateService->bulkDelete($templateIds),
            'activate' => $this->emailTemplateService->bulkActivate($templateIds),
            'deactivate' => $this->emailTemplateService->bulkDeactivate($templateIds),
        };

        return response()->json([
            'success' => true,
            'message' => "Successfully {$action}d {$count} email template(s)",
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/email-templates/types/list',
        summary: 'Get available template types',
        tags: ['Admin - Email Template'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Template types retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'types', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function templateTypes(): JsonResponse
    {
        $types = $this->emailTemplateService->getTemplateTypes();
        
        return response()->json([
            'success' => true,
            'types' => $types,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/email-templates/variables/list',
        summary: 'Get common template variables',
        tags: ['Admin - Email Template'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Variables retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'variables', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function templateVariables(): JsonResponse
    {
        $variables = $this->emailTemplateService->getCommonVariables();
        
        return response()->json([
            'success' => true,
            'variables' => $variables,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/email-templates/statistics/overview',
        summary: 'Get email template statistics',
        tags: ['Admin - Email Template'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statistics retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total_templates', type: 'integer', example: 20),
                        new OA\Property(property: 'active_templates', type: 'integer', example: 15),
                        new OA\Property(property: 'inactive_templates', type: 'integer', example: 5),
                    ]
                )
            ),
        ]
    )]
    public function statistics(): JsonResponse
    {
        $statistics = $this->emailTemplateService->getStatistics();
        
        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/email-templates/{id}/duplicate',
        summary: 'Duplicate email template',
        tags: ['Admin - Email Template'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Template duplicated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'id', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Template not found'),
        ]
    )]
    public function duplicate(int $id): JsonResponse
    {
        $newId = $this->emailTemplateService->duplicateTemplate($id);
        
        if (!$newId) {
            return response()->json([
                'success' => false,
                'message' => 'Email template not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email template duplicated successfully',
            'id' => $newId,
        ], 201);
    }

    #[OA\Post(
        path: '/api/v1/admin/email-templates/{id}/preview',
        summary: 'Preview email template with sample data',
        tags: ['Admin - Email Template'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'sample_data',
                        type: 'object',
                        example: ['user_name' => 'John Doe', 'user_email' => 'john@example.com']
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Preview generated successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'subject', type: 'string'),
                        new OA\Property(property: 'body', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Template not found'),
        ]
    )]
    public function preview(Request $request, int $id): JsonResponse
    {
        $sampleData = $request->input('sample_data', []);
        $preview = $this->emailTemplateService->previewTemplate($id, $sampleData);
        
        if (!$preview) {
            return response()->json([
                'success' => false,
                'message' => 'Email template not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'preview' => $preview,
        ]);
    }
}
