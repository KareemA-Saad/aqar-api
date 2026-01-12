<?php

declare(strict_types=1);

namespace Modules\Event\Http\Controllers\Api\V1\Frontend;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Event\Entities\Event;
use Modules\Event\Entities\EventCategory;
use Modules\Event\Entities\EventComment;
use Modules\Event\Http\Requests\EventBookingRequest;
use Modules\Event\Http\Requests\StoreEventCommentRequest;
use Modules\Event\Http\Resources\EventCategoryResource;
use Modules\Event\Http\Resources\EventCommentResource;
use Modules\Event\Http\Resources\EventPaymentLogResource;
use Modules\Event\Http\Resources\EventResource;
use Modules\Event\Services\EventBookingService;
use Modules\Event\Services\EventService;
use OpenApi\Attributes as OA;

/**
 * Frontend Event Controller
 *
 * Public event endpoints within a tenant context.
 */
#[OA\Tag(
    name: 'Tenant Frontend - Events',
    description: 'Public event endpoints for tenant frontend'
)]
final class EventController extends BaseApiController
{
    public function __construct(
        private readonly EventService $eventService,
        private readonly EventBookingService $bookingService,
    ) {}

    /**
     * List published events.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/events',
        summary: 'List published events',
        description: 'Get paginated list of published events',
        tags: ['Tenant Frontend - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'upcoming', in: 'query', schema: new OA\Schema(type: 'boolean'))]
    #[OA\Parameter(name: 'min_cost', in: 'query', schema: new OA\Schema(type: 'number'))]
    #[OA\Parameter(name: 'max_cost', in: 'query', schema: new OA\Schema(type: 'number'))]
    #[OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15))]
    #[OA\Response(response: 200, description: 'Events retrieved successfully')]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'category_id', 'search', 'upcoming', 'min_cost', 'max_cost',
            'sort_by', 'sort_order'
        ]);
        $perPage = min((int) $request->input('per_page', 15), 100);

        $events = $this->eventService->getPublishedEvents($filters, $perPage);

        return $this->paginated($events, EventResource::class, 'Events retrieved successfully');
    }

    /**
     * Get upcoming events.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/events/upcoming',
        summary: 'Get upcoming events',
        description: 'Get paginated list of upcoming published events',
        tags: ['Tenant Frontend - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15))]
    #[OA\Response(response: 200, description: 'Upcoming events retrieved successfully')]
    public function upcoming(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $events = $this->eventService->getUpcomingEvents($perPage);

        return $this->paginated($events, EventResource::class, 'Upcoming events retrieved successfully');
    }

    /**
     * Get event by slug.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/events/{slug}',
        summary: 'Get event by slug',
        description: 'Get a single published event by slug with related events',
        tags: ['Tenant Frontend - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Event retrieved successfully')]
    #[OA\Response(response: 404, description: 'Event not found')]
    public function show(string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)
            ->where('status', true)
            ->with(['category', 'metainfo'])
            ->withCount('comments')
            ->first();

        if (!$event) {
            return $this->error('Event not found', 404);
        }

        $relatedEvents = $this->eventService->getRelatedEvents($event, 4);

        return $this->success([
            'event' => new EventResource($event),
            'related_events' => EventResource::collection($relatedEvents),
        ], 'Event retrieved successfully');
    }

    /**
     * Get events by category.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/events/category/{categoryId}',
        summary: 'Get events by category',
        description: 'Get paginated list of events by category',
        tags: ['Tenant Frontend - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'categoryId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15))]
    #[OA\Response(response: 200, description: 'Events retrieved successfully')]
    #[OA\Response(response: 404, description: 'Category not found')]
    public function byCategory(Request $request, int $categoryId): JsonResponse
    {
        $category = EventCategory::find($categoryId);

        if (!$category) {
            return $this->error('Category not found', 404);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $events = $this->eventService->getEventsByCategory($categoryId, $perPage);

        return $this->success([
            'category' => new EventCategoryResource($category),
            'events' => $this->paginated($events, EventResource::class, '')->getData()->data,
        ], 'Events retrieved successfully');
    }

    /**
     * Search events.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/events/search',
        summary: 'Search events',
        description: 'Search published events by query',
        tags: ['Tenant Frontend - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'q', in: 'query', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15))]
    #[OA\Response(response: 200, description: 'Search results retrieved successfully')]
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $perPage = min((int) $request->input('per_page', 15), 100);

        $events = $this->eventService->searchEvents($query, $perPage);

        return $this->paginated($events, EventResource::class, 'Search results retrieved successfully');
    }

    /**
     * Get event categories.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/event-categories',
        summary: 'Get active event categories',
        description: 'Get all active event categories',
        tags: ['Tenant Frontend - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Categories retrieved successfully')]
    public function categories(): JsonResponse
    {
        $categories = EventCategory::where('status', true)
            ->withCount('events')
            ->orderBy('title', 'asc')
            ->get();

        return $this->success(
            EventCategoryResource::collection($categories),
            'Event categories retrieved successfully'
        );
    }

    /**
     * Get event comments.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/events/{slug}/comments',
        summary: 'Get event comments',
        description: 'Get paginated comments for an event',
        tags: ['Tenant Frontend - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 10))]
    #[OA\Response(response: 200, description: 'Comments retrieved successfully')]
    #[OA\Response(response: 404, description: 'Event not found')]
    public function comments(Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->first();

        if (!$event) {
            return $this->error('Event not found', 404);
        }

        $perPage = min((int) $request->input('per_page', 10), 50);
        $comments = EventComment::where('event_id', $event->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginated($comments, EventCommentResource::class, 'Comments retrieved successfully');
    }

    /**
     * Store a comment (requires authentication).
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/events/{slug}/comments',
        summary: 'Post a comment',
        description: 'Post a comment on an event (requires authentication)',
        security: [['sanctum' => []]],
        tags: ['Tenant Frontend - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreEventCommentRequest'))]
    #[OA\Response(response: 201, description: 'Comment posted successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 404, description: 'Event not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function storeComment(StoreEventCommentRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->where('status', true)->first();

        if (!$event) {
            return $this->error('Event not found', 404);
        }

        $userId = null;
        if (Auth::guard('api_tenant_user')->check()) {
            $userId = Auth::guard('api_tenant_user')->id();
        }

        $comment = EventComment::create([
            'event_id' => $event->id,
            'user_id' => $userId,
            'commented_by' => $request->validated()['commented_by'],
            'comment_content' => $request->validated()['comment_content'],
        ]);

        return $this->success(
            new EventCommentResource($comment->load('user')),
            'Comment posted successfully',
            201
        );
    }

    /**
     * Book event tickets (TEST MODE).
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/events/{slug}/book',
        summary: 'Book event tickets',
        description: 'Book tickets for an event (TEST MODE - auto-approved)',
        tags: ['Tenant Frontend - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'slug', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/EventBookingRequest'))]
    #[OA\Response(response: 201, description: 'Booking created successfully')]
    #[OA\Response(response: 400, description: 'Not enough tickets available')]
    #[OA\Response(response: 404, description: 'Event not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function book(EventBookingRequest $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)->where('status', true)->first();

        if (!$event) {
            return $this->error('Event not found', 404);
        }

        try {
            $booking = $this->bookingService->createBooking($event, $request->validated());

            return $this->success(
                new EventPaymentLogResource($booking),
                'Event booking created successfully! Your ticket code: ' . $booking->ticket_code,
                201
            );
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 400);
        }
    }

    /**
     * Get booking by ticket code (for user to view their booking).
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/bookings/ticket/{code}',
        summary: 'Get booking by ticket code',
        description: 'View booking details using ticket code',
        tags: ['Tenant Frontend - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'code', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Booking found')]
    #[OA\Response(response: 404, description: 'Booking not found')]
    public function getBooking(string $code): JsonResponse
    {
        $booking = $this->bookingService->getBookingByTicketCode($code);

        if (!$booking) {
            return $this->error('Booking not found', 404);
        }

        return $this->success(
            new EventPaymentLogResource($booking),
            'Booking details retrieved successfully'
        );
    }
}
