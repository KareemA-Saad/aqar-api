<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\RealEstate\Http\Requests\StorePropertyInquiryRequest;
use Modules\RealEstate\Services\InquiryService;
use Modules\RealEstate\Transformers\PropertyInquiryResource;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Inquiries', description: 'Property inquiry submission endpoints')]
class PropertyInquiryController extends Controller
{
    public function __construct(
        protected InquiryService $inquiryService
    ) {}

    /**
     * Submit a property inquiry.
     */
    #[OA\Post(
        path: '/api/realestate/properties/{property}/inquire',
        summary: 'Submit property inquiry',
        tags: ['Inquiries'],
        parameters: [
            new OA\Parameter(name: 'property', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StorePropertyInquiryRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Inquiry submitted'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function storeForProperty(StorePropertyInquiryRequest $request, int $property): JsonResponse
    {
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
        path: '/api/realestate/compounds/{compound}/inquire',
        summary: 'Submit compound inquiry',
        tags: ['Inquiries'],
        parameters: [
            new OA\Parameter(name: 'compound', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StorePropertyInquiryRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Inquiry submitted'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function storeForCompound(StorePropertyInquiryRequest $request, int $compound): JsonResponse
    {
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
        path: '/api/realestate/inquiries',
        summary: 'Submit general inquiry',
        tags: ['Inquiries'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StorePropertyInquiryRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Inquiry submitted'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function store(StorePropertyInquiryRequest $request): JsonResponse
    {
        $inquiry = $this->inquiryService->createInquiry($request->validated());
        
        return response()->json([
            'message' => 'Your inquiry has been submitted successfully. Our team will contact you soon.',
            'data' => new PropertyInquiryResource($inquiry),
        ], 201);
    }
}
