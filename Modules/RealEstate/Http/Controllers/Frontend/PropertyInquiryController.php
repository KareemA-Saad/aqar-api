<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use Modules\RealEstate\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\RealEstate\Entities\PropertyInquiry;
use Modules\RealEstate\Http\Requests\StorePropertyInquiryRequest;
use Modules\RealEstate\Services\InquiryService;
use Modules\RealEstate\Transformers\PropertyInquiryResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Inquiries', description: 'Property inquiry submission endpoints')]
class PropertyInquiryController extends BaseController
{
    public function __construct(
        protected InquiryService $inquiryService
    ) {}

    /**
     * Submit a property inquiry.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/realestate/inquiries/property/{property}',
        summary: 'Submit property inquiry',
        tags: ['Inquiries'],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RE_StorePropertyInquiryRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Inquiry submitted'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function storeForProperty(StorePropertyInquiryRequest $request, int $property): JsonResponse
    {
        Log::info('RealEstate: Submitting property inquiry', [
            'property_id' => $property,
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
        ]);

        $data = $request->validated();
        $data['property_id'] = $property;
        
        $inquiry = $this->inquiryService->createInquiry($data);
        
        return response()->json([
            'message' => 'Your inquiry has been submitted successfully. Our team will contact you soon.',
            'data' => new PropertyInquiryResource($inquiry),
        ], 201);
    }

    /**
     * Submit a compound inquiry.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/realestate/inquiries/compound/{compound}',
        summary: 'Submit compound inquiry',
        tags: ['Inquiries'],
        parameters: [
            new OA\Parameter(name: 'compound', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RE_StorePropertyInquiryRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Inquiry submitted'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function storeForCompound(StorePropertyInquiryRequest $request, int $compound): JsonResponse
    {
        Log::info('RealEstate: Submitting compound inquiry', [
            'compound_id' => $compound,
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
        ]);

        $data = $request->validated();
        $data['compound_id'] = $compound;
        
        $inquiry = $this->inquiryService->createInquiry($data);
        
        return response()->json([
            'message' => 'Your inquiry has been submitted successfully. Our team will contact you soon.',
            'data' => new PropertyInquiryResource($inquiry),
        ], 201);
    }

    /**
     * Submit a general inquiry.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/realestate/inquiries/general',
        summary: 'Submit general inquiry',
        tags: ['Inquiries'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/RE_StorePropertyInquiryRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Inquiry submitted'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StorePropertyInquiryRequest $request): JsonResponse
    {
        Log::info('RealEstate: Submitting general inquiry', [
            'authenticated' => auth()->check(),
            'user_id' => auth()->id(),
        ]);

        $inquiry = $this->inquiryService->createInquiry($request->validated());
        
        return response()->json([
            'message' => 'Your inquiry has been submitted successfully. Our team will contact you soon.',
            'data' => new PropertyInquiryResource($inquiry),
        ], 201);
    }

    /**
     * Get authenticated user's inquiries.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/my-inquiries',
        summary: 'Get user inquiries',
        description: 'Returns paginated list of inquiries submitted by the authenticated user',
        security: [['sanctum' => []]],
        tags: ['Inquiries'],
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                description: 'Filter by status',
                schema: new OA\Schema(type: 'string', enum: ['new', 'contacted', 'qualified', 'converted', 'closed'])
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                description: 'Items per page (max 50)',
                schema: new OA\Schema(type: 'integer', default: 15, maximum: 50)
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                schema: new OA\Schema(type: 'integer', default: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User inquiries list',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/RE_PropertyInquiryResource')
                        ),
                        new OA\Property(
                            property: 'meta',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'current_page', type: 'integer'),
                                new OA\Property(property: 'last_page', type: 'integer'),
                                new OA\Property(property: 'per_page', type: 'integer'),
                                new OA\Property(property: 'total', type: 'integer'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function myInquiries(Request $request): JsonResponse
    {
        $userId = auth()->id();
        
        Log::info('RealEstate: User fetching their inquiries', [
            'user_id' => $userId,
            'filters' => $request->only(['status', 'per_page', 'page']),
        ]);

        // Validate query parameters
        $request->validate([
            'status' => 'sometimes|string|in:new,contacted,qualified,converted,closed',
            'per_page' => 'sometimes|integer|min:1|max:50',
            'page' => 'sometimes|integer|min:1',
        ]);

        $perPage = min((int) $request->input('per_page', 15), 50);

        // Build query scoped to authenticated user only
        $query = PropertyInquiry::where('user_id', $userId)
            ->with(['property.compound', 'property.propertyType', 'compound', 'agent'])
            ->orderBy('created_at', 'desc');

        // Apply status filter if provided
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
            Log::debug('RealEstate: Applying status filter', ['status' => $request->input('status')]);
        }

        $inquiries = $query->paginate($perPage);

        Log::info('RealEstate: User inquiries retrieved', [
            'user_id' => $userId,
            'total_inquiries' => $inquiries->total(),
            'current_page' => $inquiries->currentPage(),
        ]);

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
     * Get a specific inquiry for authenticated user.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/realestate/my-inquiries/{id}',
        summary: 'Get specific user inquiry',
        description: 'Returns details of a specific inquiry by the authenticated user',
        security: [['sanctum' => []]],
        tags: ['Inquiries'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Inquiry details',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/RE_PropertyInquiryResource'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Inquiry not found or does not belong to user'),
        ]
    )]
    public function showMyInquiry(int $id): JsonResponse
    {
        $userId = auth()->id();
        
        Log::info('RealEstate: User fetching specific inquiry', [
            'user_id' => $userId,
            'inquiry_id' => $id,
        ]);

        // Find inquiry scoped to authenticated user only
        $inquiry = PropertyInquiry::where('user_id', $userId)
            ->where('id', $id)
            ->with(['property.compound', 'property.propertyType', 'compound', 'agent'])
            ->first();

        if (!$inquiry) {
            Log::warning('RealEstate: Inquiry not found or unauthorized access attempt', [
                'user_id' => $userId,
                'inquiry_id' => $id,
            ]);

            return response()->json([
                'message' => 'Inquiry not found.',
            ], 404);
        }

        Log::info('RealEstate: Inquiry retrieved successfully', [
            'user_id' => $userId,
            'inquiry_id' => $id,
            'inquiry_status' => $inquiry->status,
        ]);

        return response()->json([
            'data' => new PropertyInquiryResource($inquiry),
        ]);
    }
}
