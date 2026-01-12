<?php

declare(strict_types=1);

namespace Modules\Event\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Event\Entities\Event;
use Modules\Event\Http\Requests\BulkEventRequest;
use Modules\Event\Http\Requests\StoreEventRequest;
use Modules\Event\Http\Requests\UpdateEventRequest;
use Modules\Event\Http\Resources\EventResource;
use Modules\Event\Services\EventService;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin Event Controller
 *
 * Manages events within a tenant context.
 * Requires tenant admin authentication and tenant context.
 */
#[OA\Tag(
    name: 'Tenant Admin - Events',
    description: 'Manage events within a tenant'
)]
final class EventController extends BaseApiController
{
    public function __construct(
        private readonly EventService $eventService,
    ) {}

    /**
     * List all events with pagination and filters.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/events',
        summary: 'List events',
        description: 'Get paginated list of events with optional filters',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'category_id', in: 'query', schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'integer', enum: [0, 1]))]
    #[OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'))]
    #[OA\Parameter(name: 'upcoming', in: 'query', schema: new OA\Schema(type: 'boolean'))]
    #[OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', enum: ['title', 'date', 'cost', 'created_at']))]
    #[OA\Parameter(name: 'sort_order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'asc'))]
    #[OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, maximum: 100))]
    #[OA\Response(response: 200, description: 'Events retrieved successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'category_id', 'status', 'date_from', 'date_to',
            'upcoming', 'past', 'available_tickets', 'organizer',
            'min_cost', 'max_cost', 'sort_by', 'sort_order'
        ]);

        $perPage = min((int) $request->input('per_page', 15), 100);
        $events = $this->eventService->getEvents($filters, $perPage);

        return $this->paginated($events, EventResource::class, 'Events retrieved successfully');
    }

    /**
     * Create a new event.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/events',
        summary: 'Create event',
        description: 'Create a new event',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/StoreEventRequest'))]
    #[OA\Response(response: 201, description: 'Event created successfully')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->eventService->createEvent($request->validated());

        return $this->success(
            new EventResource($event),
            'Event created successfully',
            201
        );
    }

    /**
     * Get a specific event.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/events/{id}',
        summary: 'Get event',
        description: 'Get a specific event by ID',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Event retrieved successfully')]
    #[OA\Response(response: 404, description: 'Event not found')]
    public function show(int $id): JsonResponse
    {
        $event = Event::with(['category', 'metainfo'])->withCount('comments')->find($id);

        if (!$event) {
            return $this->error('Event not found', 404);
        }

        return $this->success(
            new EventResource($event),
            'Event retrieved successfully'
        );
    }

    /**
     * Update an event.
     */
    #[OA\Put(
        path: '/api/v1/tenant/{tenant}/admin/events/{id}',
        summary: 'Update event',
        description: 'Update an existing event',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/UpdateEventRequest'))]
    #[OA\Response(response: 200, description: 'Event updated successfully')]
    #[OA\Response(response: 404, description: 'Event not found')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function update(UpdateEventRequest $request, int $id): JsonResponse
    {
        $event = Event::find($id);

        if (!$event) {
            return $this->error('Event not found', 404);
        }

        $updatedEvent = $this->eventService->updateEvent($event, $request->validated());

        return $this->success(
            new EventResource($updatedEvent),
            'Event updated successfully'
        );
    }

    /**
     * Delete an event.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/events/{id}',
        summary: 'Delete event',
        description: 'Delete an event',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Event deleted successfully')]
    #[OA\Response(response: 404, description: 'Event not found')]
    public function destroy(int $id): JsonResponse
    {
        $event = Event::find($id);

        if (!$event) {
            return $this->error('Event not found', 404);
        }

        $this->eventService->deleteEvent($event);

        return $this->success(null, 'Event deleted successfully');
    }

    /**
     * Clone an event.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/events/{id}/clone',
        summary: 'Clone event',
        description: 'Clone an existing event',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 201, description: 'Event cloned successfully')]
    #[OA\Response(response: 404, description: 'Event not found')]
    public function clone(int $id): JsonResponse
    {
        $event = Event::find($id);

        if (!$event) {
            return $this->error('Event not found', 404);
        }

        $clonedEvent = $this->eventService->cloneEvent($event);

        return $this->success(
            new EventResource($clonedEvent),
            'Event cloned successfully',
            201
        );
    }

    /**
     * Bulk actions on events.
     */
    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/admin/events/bulk',
        summary: 'Bulk event actions',
        description: 'Perform bulk actions on events (delete, publish, unpublish)',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/BulkEventRequest'))]
    #[OA\Response(response: 200, description: 'Bulk action completed successfully')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function bulkAction(BulkEventRequest $request): JsonResponse
    {
        $data = $request->validated();
        $ids = $data['ids'];
        $action = $data['action'];

        $count = 0;

        switch ($action) {
            case 'delete':
                foreach ($ids as $id) {
                    $event = Event::find($id);
                    if ($event) {
                        $this->eventService->deleteEvent($event);
                        $count++;
                    }
                }
                $message = "{$count} event(s) deleted successfully";
                break;

            case 'publish':
                $count = Event::whereIn('id', $ids)->update(['status' => true]);
                $message = "{$count} event(s) published successfully";
                break;

            case 'unpublish':
                $count = Event::whereIn('id', $ids)->update(['status' => false]);
                $message = "{$count} event(s) unpublished successfully";
                break;

            default:
                return $this->error('Invalid action', 400);
        }

        return $this->success(['count' => $count], $message);
    }

    /**
     * Get event statistics.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/events/{id}/statistics',
        summary: 'Get event statistics',
        description: 'Get statistics for a specific event',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Events']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Statistics retrieved successfully')]
    #[OA\Response(response: 404, description: 'Event not found')]
    public function statistics(int $id): JsonResponse
    {
        $event = Event::with('paymentLogs')->find($id);

        if (!$event) {
            return $this->error('Event not found', 404);
        }

        $statistics = $this->eventService->getEventStatistics($event);

        return $this->success($statistics, 'Event statistics retrieved successfully');
    }
}
