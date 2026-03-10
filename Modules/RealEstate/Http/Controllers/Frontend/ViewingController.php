<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Http\Controllers\BaseController;
use Modules\RealEstate\Services\ViewingService;
use OpenApi\Attributes as OA;

/**
 * Frontend Viewing Controller — F2.5 Viewing Scheduler
 *
 * Public / authenticated user endpoint for booking viewings.
 *
 * Endpoint:
 *   POST /realestate/viewings  → book
 */
#[OA\Tag(name: 'Viewings', description: 'Property viewing booking for users')]
class ViewingController extends BaseController
{
    public function __construct(
        protected ViewingService $viewingService
    ) {}

    // =========================================================================
    // POST /realestate/viewings
    // =========================================================================

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/realestate/viewings',
        summary: 'Book a property viewing',
        description: 'User or visitor books a property/compound viewing. Requires authentication (TIER 2). The viewing is created with `pending` status and assigned to the responsible agent (if set).',
        tags: ['Viewings'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['contact_name', 'scheduled_at'],
                properties: [
                    new OA\Property(property: 'property_id', type: 'integer', description: 'ID of the property to view (mutually exclusive with compound_id)', example: 5),
                    new OA\Property(property: 'compound_id', type: 'integer', description: 'ID of the compound to view', example: 2),
                    new OA\Property(property: 'contact_name', type: 'string', example: 'Ahmed Hassan'),
                    new OA\Property(property: 'contact_phone', type: 'string', example: '+201001234567'),
                    new OA\Property(property: 'contact_email', type: 'string', format: 'email', example: 'ahmed@example.com'),
                    new OA\Property(property: 'scheduled_at', type: 'string', format: 'date-time', description: 'Desired viewing date-time (must be in the future)', example: '2026-04-15 14:00:00'),
                    new OA\Property(property: 'notes', type: 'string', description: 'Any notes or special requests', example: 'Prefer ground floor units.'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Viewing booked (status: pending)'),
            new OA\Response(response: 422, description: 'Validation error'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function book(Request $request): JsonResponse
    {
        $data = $request->validate([
            'property_id'   => 'nullable|integer|exists:re_properties,id',
            'compound_id'   => 'nullable|integer|exists:re_compounds,id',
            'contact_name'  => 'required|string|max:255',
            'contact_phone' => 'nullable|string|max:30',
            'contact_email' => 'nullable|email|max:255',
            'scheduled_at'  => 'required|date|after:now',
            'notes'         => 'nullable|string|max:1000',
        ]);

        // Must specify at least property or compound
        if (empty($data['property_id']) && empty($data['compound_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'Please specify either a property_id or compound_id for the viewing.',
            ], 422);
        }

        $data['user_id'] = auth()->id();

        $viewing = $this->viewingService->book($data);

        return response()->json([
            'success' => true,
            'message' => 'Viewing request received. We will confirm your appointment shortly.',
            'data'    => $viewing,
        ], 201);
    }
}
