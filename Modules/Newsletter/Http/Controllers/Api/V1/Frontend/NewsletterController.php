<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Newsletter\Http\Requests\SubscribeNewsletterRequest;
use Modules\Newsletter\Services\NewsletterService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Frontend - Newsletter', description: 'Public newsletter subscription endpoints')]
class NewsletterController extends Controller
{
    public function __construct(
        private readonly NewsletterService $newsletterService
    ) {
    }

    #[OA\Post(
        path: '/api/v1/frontend/newsletter/subscribe',
        summary: 'Subscribe to newsletter',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SubscribeNewsletterRequest')
        ),
        tags: ['Frontend - Newsletter'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Subscribed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'token', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function subscribe(SubscribeNewsletterRequest $request): JsonResponse
    {
        $newsletter = $this->newsletterService->subscribe($request->validated()['email']);
        
        return response()->json([
            'message' => 'Thank you for subscribing! Please check your email to verify your subscription.',
            'token' => $newsletter->token,
        ], 201);
    }

    #[OA\Get(
        path: '/api/v1/frontend/newsletter/verify/{token}',
        summary: 'Verify newsletter subscription',
        tags: ['Frontend - Newsletter'],
        parameters: [
            new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Subscription verified successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Invalid or expired token'),
        ]
    )]
    public function verify(string $token): JsonResponse
    {
        $newsletter = $this->newsletterService->verifySubscription($token);
        
        if (!$newsletter) {
            return response()->json([
                'message' => 'Invalid or expired verification token',
            ], 404);
        }
        
        return response()->json([
            'message' => 'Your email has been verified successfully! You are now subscribed to our newsletter.',
        ]);
    }

    #[OA\Post(
        path: '/api/v1/frontend/newsletter/unsubscribe',
        summary: 'Unsubscribe from newsletter',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                ]
            )
        ),
        tags: ['Frontend - Newsletter'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Unsubscribed successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Email not found'),
        ]
    )]
    public function unsubscribe(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);
        
        $result = $this->newsletterService->unsubscribe($request->email);
        
        if (!$result) {
            return response()->json([
                'message' => 'Email address not found in our newsletter list',
            ], 404);
        }
        
        return response()->json([
            'message' => 'You have been successfully unsubscribed from our newsletter',
        ]);
    }
}
