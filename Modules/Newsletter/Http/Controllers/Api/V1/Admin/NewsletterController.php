<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Newsletter\Http\Requests\BulkNewsletterRequest;
use Modules\Newsletter\Http\Resources\NewsletterResource;
use Modules\Newsletter\Services\NewsletterService;
use Modules\Newsletter\Entities\Newsletter;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Newsletter', description: 'Newsletter subscription management endpoints')]
class NewsletterController extends Controller
{
    public function __construct(
        private readonly NewsletterService $newsletterService
    ) {
    }

    #[OA\Get(
        path: '/api/v1/admin/newsletters',
        summary: 'Get paginated list of newsletter subscriptions',
        tags: ['Admin - Newsletter'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'verified', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['email', 'created_at', 'updated_at'])),
            new OA\Parameter(name: 'sort_order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Newsletter subscriptions retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/NewsletterResource')),
                        new OA\Property(property: 'meta', type: 'object'),
                        new OA\Property(property: 'links', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'verified', 'sort_by', 'sort_order']);
        $perPage = (int) $request->get('per_page', 15);
        
        $newsletters = $this->newsletterService->getSubscriptions($filters, $perPage);
        
        return response()->json(NewsletterResource::collection($newsletters));
    }

    #[OA\Get(
        path: '/api/v1/admin/newsletters/{id}',
        summary: 'Get a specific newsletter subscription',
        tags: ['Admin - Newsletter'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Newsletter subscription retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/NewsletterResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Newsletter subscription not found'),
        ]
    )]
    public function show(Newsletter $newsletter): JsonResponse
    {
        return response()->json([
            'data' => NewsletterResource::make($newsletter),
        ]);
    }

    #[OA\Delete(
        path: '/api/v1/admin/newsletters/{id}',
        summary: 'Delete a newsletter subscription',
        tags: ['Admin - Newsletter'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Newsletter subscription deleted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Newsletter subscription not found'),
        ]
    )]
    public function destroy(Newsletter $newsletter): JsonResponse
    {
        $this->newsletterService->deleteSubscription($newsletter);
        
        return response()->json([
            'message' => 'Newsletter subscription deleted successfully',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/admin/newsletters/bulk',
        summary: 'Perform bulk actions on newsletter subscriptions',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/BulkNewsletterRequest')
        ),
        tags: ['Admin - Newsletter'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Bulk action completed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'processed', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function bulkAction(BulkNewsletterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $newsletters = Newsletter::whereIn('id', $validated['ids'])->get();
        
        $processed = 0;
        
        foreach ($newsletters as $newsletter) {
            match ($validated['action']) {
                'delete' => $this->newsletterService->deleteSubscription($newsletter),
                'verify' => $newsletter->update(['verified' => true, 'token' => null]),
            };
            $processed++;
        }
        
        return response()->json([
            'message' => "Bulk action '{$validated['action']}' completed successfully",
            'processed' => $processed,
        ]);
    }

    #[OA\Get(
        path: '/api/v1/admin/newsletters/statistics/overview',
        summary: 'Get newsletter subscription statistics',
        tags: ['Admin - Newsletter'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statistics retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 150),
                        new OA\Property(property: 'verified', type: 'integer', example: 120),
                        new OA\Property(property: 'pending', type: 'integer', example: 30),
                    ]
                )
            ),
        ]
    )]
    public function statistics(): JsonResponse
    {
        $stats = $this->newsletterService->getStatistics();
        
        return response()->json($stats);
    }

    #[OA\Get(
        path: '/api/v1/admin/newsletters/export/emails',
        summary: 'Export all verified email addresses',
        tags: ['Admin - Newsletter'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Emails exported successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'emails',
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'email')
                        ),
                        new OA\Property(property: 'count', type: 'integer'),
                    ]
                )
            ),
        ]
    )]
    public function exportEmails(): JsonResponse
    {
        $emails = $this->newsletterService->exportVerifiedEmails();
        
        return response()->json([
            'emails' => $emails,
            'count' => count($emails),
        ]);
    }
}
