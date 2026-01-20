<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\PropertyInquiry;
use Modules\RealEstate\Http\Requests\UpdatePropertyInquiryRequest;
use Modules\RealEstate\Services\InquiryService;
use Modules\RealEstate\Transformers\PropertyInquiryResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Admin - Inquiries', description: 'Property inquiry/CRM management endpoints')]
class PropertyInquiryController extends Controller
{
    public function __construct(
        protected InquiryService $inquiryService
    ) {}

    /**
     * List all inquiries with filters.
     */
    #[OA\Get(
        path: '/api/admin/realestate/inquiries',
        summary: 'List all inquiries',
        tags: ['Admin - Inquiries'],
        parameters: [
            new OA\Parameter(name: 'filter[property_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[compound_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'filter[status]', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'filter[agent_id]', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'List of inquiries'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $inquiries = $this->inquiryService->getPaginatedInquiries($request->all());
        
        return response()->json([
            'data' => PropertyInquiryResource::collection($inquiries),
            'meta' => [
                'total' => $inquiries->total(),
                'per_page' => $inquiries->perPage(),
                'current_page' => $inquiries->currentPage(),
                'last_page' => $inquiries->lastPage(),
            ],
        ]);
    }

    /**
     * Show a single inquiry.
     */
    #[OA\Get(
        path: '/api/admin/realestate/inquiries/{id}',
        summary: 'Get inquiry details',
        tags: ['Admin - Inquiries'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Inquiry details'),
            new OA\Response(response: 404, description: 'Inquiry not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $inquiry = $this->inquiryService->getInquiry($id);
        
        if (!$inquiry) {
            return response()->json(['message' => 'Inquiry not found.'], 404);
        }
        
        return response()->json([
            'data' => new PropertyInquiryResource($inquiry),
        ]);
    }

    /**
     * Update inquiry status/agent.
     */
    #[OA\Put(
        path: '/api/admin/realestate/inquiries/{id}',
        summary: 'Update inquiry',
        tags: ['Admin - Inquiries'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdatePropertyInquiryRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Inquiry updated'),
            new OA\Response(response: 404, description: 'Inquiry not found'),
        ]
    )]
    public function update(UpdatePropertyInquiryRequest $request, PropertyInquiry $inquiry): JsonResponse
    {
        $data = $request->validated();
        
        if (isset($data['status'])) {
            $this->inquiryService->updateStatus($inquiry, $data['status']);
        }
        
        if (isset($data['agent_id'])) {
            $this->inquiryService->assignAgent($inquiry, $data['agent_id']);
        }
        
        if (isset($data['admin_notes'])) {
            $this->inquiryService->addNotes($inquiry, $data['admin_notes']);
        }
        
        return response()->json([
            'message' => 'Inquiry updated successfully.',
            'data' => new PropertyInquiryResource($inquiry->fresh(['property', 'compound', 'user', 'agent'])),
        ]);
    }

    /**
     * Delete an inquiry.
     */
    #[OA\Delete(
        path: '/api/admin/realestate/inquiries/{id}',
        summary: 'Delete an inquiry',
        tags: ['Admin - Inquiries'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Inquiry deleted'),
            new OA\Response(response: 404, description: 'Inquiry not found'),
        ]
    )]
    public function destroy(PropertyInquiry $inquiry): JsonResponse
    {
        $this->inquiryService->deleteInquiry($inquiry);
        
        return response()->json([
            'message' => 'Inquiry deleted successfully.',
        ]);
    }

    /**
     * Get inquiry statistics.
     */
    #[OA\Get(
        path: '/api/admin/realestate/inquiries/statistics',
        summary: 'Get inquiry statistics',
        tags: ['Admin - Inquiries'],
        responses: [
            new OA\Response(response: 200, description: 'Inquiry statistics'),
        ]
    )]
    public function statistics(): JsonResponse
    {
        return response()->json([
            'data' => $this->inquiryService->getStatistics(),
        ]);
    }

    /**
     * Mark inquiry as contacted.
     */
    #[OA\Post(
        path: '/api/admin/realestate/inquiries/{id}/contacted',
        summary: 'Mark inquiry as contacted',
        tags: ['Admin - Inquiries'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Inquiry marked as contacted'),
        ]
    )]
    public function markContacted(PropertyInquiry $inquiry): JsonResponse
    {
        $inquiry = $this->inquiryService->markAsContacted($inquiry);
        
        return response()->json([
            'message' => 'Inquiry marked as contacted.',
            'data' => new PropertyInquiryResource($inquiry),
        ]);
    }

    /**
     * Mark inquiry as qualified.
     */
    #[OA\Post(
        path: '/api/admin/realestate/inquiries/{id}/qualified',
        summary: 'Mark inquiry as qualified',
        tags: ['Admin - Inquiries'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Inquiry marked as qualified'),
        ]
    )]
    public function markQualified(PropertyInquiry $inquiry): JsonResponse
    {
        $inquiry = $this->inquiryService->markAsQualified($inquiry);
        
        return response()->json([
            'message' => 'Inquiry marked as qualified.',
            'data' => new PropertyInquiryResource($inquiry),
        ]);
    }

    /**
     * Mark inquiry as converted.
     */
    #[OA\Post(
        path: '/api/admin/realestate/inquiries/{id}/converted',
        summary: 'Mark inquiry as converted',
        tags: ['Admin - Inquiries'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Inquiry marked as converted'),
        ]
    )]
    public function markConverted(PropertyInquiry $inquiry): JsonResponse
    {
        $inquiry = $this->inquiryService->markAsConverted($inquiry);
        
        return response()->json([
            'message' => 'Inquiry marked as converted.',
            'data' => new PropertyInquiryResource($inquiry),
        ]);
    }

    /**
     * Assign agent to inquiry.
     */
    #[OA\Post(
        path: '/api/admin/realestate/inquiries/{id}/assign',
        summary: 'Assign agent to inquiry',
        tags: ['Admin - Inquiries'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'agent_id', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Agent assigned'),
        ]
    )]
    public function assignAgent(Request $request, PropertyInquiry $inquiry): JsonResponse
    {
        $request->validate([
            'agent_id' => 'required|integer|exists:users,id',
        ]);
        
        $inquiry = $this->inquiryService->assignAgent($inquiry, $request->input('agent_id'));
        
        return response()->json([
            'message' => 'Agent assigned successfully.',
            'data' => new PropertyInquiryResource($inquiry),
        ]);
    }

    /**
     * Bulk update status.
     */
    #[OA\Post(
        path: '/api/admin/realestate/inquiries/bulk-status',
        summary: 'Bulk update inquiry status',
        tags: ['Admin - Inquiries'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'ids', type: 'array', items: new OA\Items(type: 'integer')),
                    new OA\Property(property: 'status', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Bulk status update completed'),
        ]
    )]
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
            'status' => 'required|string|in:' . implode(',', PropertyInquiry::$statuses),
        ]);
        
        $count = $this->inquiryService->bulkUpdateStatus(
            $request->input('ids'),
            $request->input('status')
        );
        
        return response()->json([
            'message' => "{$count} inquiries updated.",
            'affected_count' => $count,
        ]);
    }

    /**
     * Export inquiries.
     */
    #[OA\Get(
        path: '/api/admin/realestate/inquiries/export',
        summary: 'Export inquiries',
        tags: ['Admin - Inquiries'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Exported inquiries data'),
        ]
    )]
    public function export(Request $request): JsonResponse
    {
        $data = $this->inquiryService->exportInquiries($request->all());
        
        return response()->json([
            'data' => $data,
            'count' => count($data),
        ]);
    }
}
