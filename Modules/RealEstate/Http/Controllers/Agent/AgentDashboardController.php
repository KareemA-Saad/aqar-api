<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\PropertyInquiry;
use Modules\RealEstate\Services\InquiryService;
use Modules\RealEstate\Transformers\PropertyResource;
use Modules\RealEstate\Transformers\PropertyInquiryResource;
use OpenApi\Attributes as OA;

/**
 * Agent Dashboard Controller
 * 
 * Provides API endpoints for agents to manage their assigned properties
 * and inquiries.
 */
#[OA\Tag(name: 'Agent Dashboard', description: 'Agent-specific endpoints for managing properties and inquiries')]
class AgentDashboardController extends Controller
{
    public function __construct(
        protected InquiryService $inquiryService
    ) {}

    /**
     * Get agent dashboard overview.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/agent/realestate/dashboard',
        summary: 'Get agent dashboard overview',
        description: 'Returns statistics and summary for the authenticated agent',
        tags: ['Agent Dashboard'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dashboard data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'statistics', type: 'object'),
                        new OA\Property(property: 'recent_inquiries', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'recent_properties', type: 'array', items: new OA\Items(type: 'object')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function dashboard(Request $request): JsonResponse
    {
        $agentId = auth()->id();

        // Get inquiry statistics
        $inquiryStats = $this->getAgentInquiryStats($agentId);

        // Get property statistics
        $propertyStats = $this->getAgentPropertyStats($agentId);

        // Get recent inquiries
        $recentInquiries = PropertyInquiry::where('agent_id', $agentId)
            ->with(['property', 'compound'])
            ->latest()
            ->take(5)
            ->get();

        // Get recent assigned properties
        $recentProperties = Property::where('agent_id', $agentId)
            ->with(['compound', 'propertyType'])
            ->latest()
            ->take(5)
            ->get();

        return response()->json([
            'statistics' => [
                'inquiries' => $inquiryStats,
                'properties' => $propertyStats,
            ],
            'recent_inquiries' => PropertyInquiryResource::collection($recentInquiries),
            'recent_properties' => PropertyResource::collection($recentProperties),
        ]);
    }

    /**
     * Get agent's assigned properties.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/agent/realestate/properties',
        summary: 'Get agent assigned properties',
        description: 'Returns paginated list of properties assigned to the authenticated agent',
        tags: ['Agent Dashboard'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', schema: new OA\Schema(type: 'string', enum: ['active', 'sold', 'rented'])),
            new OA\Parameter(name: 'is_published', in: 'query', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Properties list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function properties(Request $request): JsonResponse
    {
        $agentId = auth()->id();

        $query = Property::where('agent_id', $agentId)
            ->with(['compound', 'propertyType', 'area'])
            ->withCount('inquiries');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('is_published')) {
            $query->where('is_published', $request->boolean('is_published'));
        }

        $properties = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => PropertyResource::collection($properties),
            'meta' => [
                'current_page' => $properties->currentPage(),
                'last_page' => $properties->lastPage(),
                'per_page' => $properties->perPage(),
                'total' => $properties->total(),
            ],
        ]);
    }

    /**
     * Get agent's assigned inquiries.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/agent/realestate/inquiries',
        summary: 'Get agent assigned inquiries',
        description: 'Returns paginated list of inquiries assigned to the authenticated agent',
        tags: ['Agent Dashboard'],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'status', in: 'query', description: 'Filter by status', schema: new OA\Schema(type: 'string', enum: ['new', 'contacted', 'qualified', 'converted', 'closed'])),
            new OA\Parameter(name: 'property_id', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Inquiries list'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function inquiries(Request $request): JsonResponse
    {
        $agentId = auth()->id();

        $query = PropertyInquiry::where('agent_id', $agentId)
            ->with(['property', 'compound', 'user']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('property_id')) {
            $query->where('property_id', $request->input('property_id'));
        }

        $inquiries = $query->latest()->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => PropertyInquiryResource::collection($inquiries),
            'meta' => [
                'current_page' => $inquiries->currentPage(),
                'last_page' => $inquiries->lastPage(),
                'per_page' => $inquiries->perPage(),
                'total' => $inquiries->total(),
            ],
        ]);
    }

    /**
     * Get agent inquiry statistics.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/agent/realestate/statistics',
        summary: 'Get agent statistics',
        description: 'Returns detailed statistics for the authenticated agent',
        tags: ['Agent Dashboard'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Statistics data',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'inquiries', type: 'object'),
                        new OA\Property(property: 'properties', type: 'object'),
                        new OA\Property(property: 'performance', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function statistics(Request $request): JsonResponse
    {
        $agentId = auth()->id();

        return response()->json([
            'inquiries' => $this->getAgentInquiryStats($agentId),
            'properties' => $this->getAgentPropertyStats($agentId),
            'performance' => $this->getAgentPerformanceStats($agentId),
        ]);
    }

    /**
     * Update inquiry from agent's perspective.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/agent/realestate/inquiries/{id}',
        summary: 'Update inquiry status',
        description: 'Allows agent to update inquiry status and add notes',
        tags: ['Agent Dashboard'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['new', 'contacted', 'qualified', 'converted', 'closed']),
                    new OA\Property(property: 'admin_notes', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Inquiry updated'),
            new OA\Response(response: 403, description: 'Not assigned to this inquiry'),
            new OA\Response(response: 404, description: 'Inquiry not found'),
        ]
    )]
    public function updateInquiry(Request $request, int $id): JsonResponse
    {
        $agentId = auth()->id();

        $inquiry = PropertyInquiry::where('agent_id', $agentId)
            ->findOrFail($id);

        $request->validate([
            'status' => 'sometimes|string|in:new,contacted,qualified,converted,closed',
            'admin_notes' => 'sometimes|string|max:2000',
        ]);

        if ($request->filled('status')) {
            $this->inquiryService->updateStatus($inquiry, $request->input('status'));
        }

        if ($request->filled('admin_notes')) {
            $this->inquiryService->addNotes($inquiry, $request->input('admin_notes'));
        }

        return response()->json([
            'message' => 'Inquiry updated successfully.',
            'data' => new PropertyInquiryResource($inquiry->fresh(['property', 'compound'])),
        ]);
    }

    /**
     * Mark inquiry as contacted.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/agent/realestate/inquiries/{id}/contact',
        summary: 'Mark inquiry as contacted',
        tags: ['Agent Dashboard'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Inquiry marked as contacted'),
            new OA\Response(response: 403, description: 'Not assigned to this inquiry'),
        ]
    )]
    public function markContacted(int $id): JsonResponse
    {
        $agentId = auth()->id();

        $inquiry = PropertyInquiry::where('agent_id', $agentId)
            ->findOrFail($id);

        $this->inquiryService->markAsContacted($inquiry);

        return response()->json([
            'message' => 'Inquiry marked as contacted.',
            'data' => new PropertyInquiryResource($inquiry->fresh()),
        ]);
    }

    /**
     * Get agent inquiry statistics.
     */
    protected function getAgentInquiryStats(int $agentId): array
    {
        $inquiries = PropertyInquiry::where('agent_id', $agentId);

        return [
            'total' => (clone $inquiries)->count(),
            'new' => (clone $inquiries)->where('status', 'new')->count(),
            'contacted' => (clone $inquiries)->where('status', 'contacted')->count(),
            'qualified' => (clone $inquiries)->where('status', 'qualified')->count(),
            'converted' => (clone $inquiries)->where('status', 'converted')->count(),
            'closed' => (clone $inquiries)->where('status', 'closed')->count(),
            'today' => (clone $inquiries)->whereDate('created_at', today())->count(),
            'this_week' => (clone $inquiries)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => (clone $inquiries)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];
    }

    /**
     * Get agent property statistics.
     */
    protected function getAgentPropertyStats(int $agentId): array
    {
        $properties = Property::where('agent_id', $agentId);

        return [
            'total' => (clone $properties)->count(),
            'published' => (clone $properties)->where('is_published', true)->count(),
            'draft' => (clone $properties)->where('is_published', false)->count(),
            'for_sale' => (clone $properties)->where('purpose', 'sale')->count(),
            'for_rent' => (clone $properties)->where('purpose', 'rent')->count(),
            'featured' => (clone $properties)->where('is_featured', true)->count(),
            'total_views' => (clone $properties)->sum('views_count'),
        ];
    }

    /**
     * Get agent performance statistics.
     */
    protected function getAgentPerformanceStats(int $agentId): array
    {
        $totalInquiries = PropertyInquiry::where('agent_id', $agentId)->count();
        $convertedInquiries = PropertyInquiry::where('agent_id', $agentId)->where('status', 'converted')->count();

        // Calculate average response time (if contacted_at is tracked)
        $avgResponseTime = PropertyInquiry::where('agent_id', $agentId)
            ->whereNotNull('contacted_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, contacted_at)) as avg_hours')
            ->value('avg_hours');

        return [
            'conversion_rate' => $totalInquiries > 0 ? round(($convertedInquiries / $totalInquiries) * 100, 2) : 0,
            'total_conversions' => $convertedInquiries,
            'avg_response_time_hours' => $avgResponseTime ? round($avgResponseTime, 1) : null,
            'inquiries_this_month' => PropertyInquiry::where('agent_id', $agentId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'conversions_this_month' => PropertyInquiry::where('agent_id', $agentId)
                ->where('status', 'converted')
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->count(),
        ];
    }
}
