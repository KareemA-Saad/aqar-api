<?php

declare(strict_types=1);

namespace Modules\Event\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Event\Entities\EventComment;
use Modules\Event\Http\Resources\EventCommentResource;
use OpenApi\Attributes as OA;

/**
 * Tenant Admin Event Comment Controller
 */
#[OA\Tag(
    name: 'Tenant Admin - Event Comments',
    description: 'Manage event comments within a tenant'
)]
final class EventCommentController extends BaseApiController
{
    /**
     * List comments for a specific event.
     */
    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/admin/events/{eventId}/comments',
        summary: 'List event comments',
        description: 'Get paginated list of comments for a specific event',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Comments']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, maximum: 100))]
    #[OA\Response(response: 200, description: 'Comments retrieved successfully')]
    public function index(Request $request, int $eventId): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 100);

        $comments = EventComment::where('event_id', $eventId)
            ->with(['user', 'event'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return $this->paginated($comments, EventCommentResource::class, 'Event comments retrieved successfully');
    }

    /**
     * Delete a comment.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/event-comments/{id}',
        summary: 'Delete comment',
        description: 'Delete an event comment',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Comments']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Comment deleted successfully')]
    #[OA\Response(response: 404, description: 'Comment not found')]
    public function destroy(int $id): JsonResponse
    {
        $comment = EventComment::find($id);

        if (!$comment) {
            return $this->error('Comment not found', 404);
        }

        $comment->delete();

        return $this->success(null, 'Event comment deleted successfully');
    }

    /**
     * Bulk delete comments for an event.
     */
    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/admin/events/{eventId}/comments',
        summary: 'Bulk delete event comments',
        description: 'Delete all comments for a specific event',
        security: [['sanctum' => []]],
        tags: ['Tenant Admin - Event Comments']
    )]
    #[OA\Parameter(name: 'tenant', in: 'path', required: true, schema: new OA\Schema(type: 'string'))]
    #[OA\Parameter(name: 'eventId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Comments deleted successfully')]
    public function bulkDelete(int $eventId): JsonResponse
    {
        $count = EventComment::where('event_id', $eventId)->delete();

        return $this->success(
            ['count' => $count],
            "{$count} comment(s) deleted successfully"
        );
    }
}
