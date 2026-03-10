<?php

declare(strict_types=1);

namespace Modules\RealEstate\Http\Controllers\Agent;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\RealEstate\Entities\InquiryNote;
use Modules\RealEstate\Http\Controllers\BaseController;
use Modules\RealEstate\Services\InquiryNoteService;
use OpenApi\Attributes as OA;

/**
 * InquiryTimelineController
 *
 * Agent-facing endpoints for F2.2: Inquiry Timeline & Notes.
 *
 * Routes (TIER 4 agent middleware):
 *   GET    /agent/realestate/inquiries/{id}/timeline         → timeline
 *   POST   /agent/realestate/inquiries/{id}/notes            → addNote
 *   DELETE /agent/realestate/inquiries/{id}/notes/{noteId}   → deleteNote
 */
#[OA\Tag(name: 'Agent Inquiry Timeline', description: 'Timeline and note management for agent inquiries')]
class InquiryTimelineController extends BaseController
{
    public function __construct(
        protected InquiryNoteService $noteService
    ) {}

    // =========================================================================
    // GET /agent/realestate/inquiries/{id}/timeline
    // =========================================================================

    #[OA\Get(
        path: '/api/v1/tenant/{tenant}/agent/realestate/inquiries/{id}/timeline',
        summary: 'Get inquiry timeline',
        description: 'Chronological activity log for an inquiry. Includes auto-logged events (status changes, agent assignment, contact) and manual agent notes. Paginated, newest first.',
        tags: ['Agent Inquiry Timeline'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 50)),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Timeline events',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean'),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'string', example: 'act_42'),
                                new OA\Property(property: 'type', type: 'string', enum: ['status_changed', 'agent_assigned', 'contacted', 'note_added', 'created']),
                                new OA\Property(property: 'description', type: 'string'),
                                new OA\Property(property: 'causer', type: 'object', nullable: true),
                                new OA\Property(property: 'properties', type: 'object'),
                                new OA\Property(property: 'note', type: 'object', nullable: true),
                                new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time'),
                            ]
                        )),
                        new OA\Property(property: 'meta', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Inquiry not found or not assigned to you'),
        ]
    )]
    public function timeline(Request $request, int $id): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 50);

        $result = $this->noteService->getTimeline($id, auth()->id(), $perPage);

        if (!$result['ok']) {
            return $this->error($result['error'], code: $result['code']);
        }

        $paged = $result['data'];

        return response()->json([
            'success' => true,
            'data'    => $paged['items'],
            'meta'    => [
                'current_page' => $paged['current_page'],
                'last_page'    => $paged['last_page'],
                'per_page'     => $paged['per_page'],
                'total'        => $paged['total'],
            ],
        ]);
    }

    // =========================================================================
    // POST /agent/realestate/inquiries/{id}/notes
    // =========================================================================

    #[OA\Post(
        path: '/api/v1/tenant/{tenant}/agent/realestate/inquiries/{id}/notes',
        summary: 'Add a note to an inquiry',
        description: 'Creates a structured note on the inquiry. Automatically appears in the timeline. Max 2000 characters.',
        tags: ['Agent Inquiry Timeline'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', maxLength: 2000, description: 'Note text', example: 'Client called back, interested in 3-bed, budget ~2M EGP'),
                    new OA\Property(property: 'is_internal', type: 'boolean', default: false, description: 'Internal notes are only visible to agents and admins'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Note created and timeline updated'),
            new OA\Response(response: 404, description: 'Inquiry not found or not assigned to you'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function addNote(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'content'     => ['required', 'string', 'max:2000'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        $result = $this->noteService->addNote(auth()->id(), $id, $validated);

        if (!$result['ok']) {
            return $this->error($result['error'], code: $result['code']);
        }

        $note = $result['note'];

        return response()->json([
            'success' => true,
            'message' => 'Note added.',
            'data'    => $this->formatNote($note),
        ], 201);
    }

    // =========================================================================
    // DELETE /agent/realestate/inquiries/{id}/notes/{noteId}
    // =========================================================================

    #[OA\Delete(
        path: '/api/v1/tenant/{tenant}/agent/realestate/inquiries/{id}/notes/{noteId}',
        summary: 'Delete a note',
        description: 'Soft-deletes the note. The timeline event is retained but note content is hidden.',
        tags: ['Agent Inquiry Timeline'],
        responses: [
            new OA\Response(response: 200, description: 'Note deleted'),
            new OA\Response(response: 404, description: 'Note not found or you do not own it'),
        ]
    )]
    public function deleteNote(int $id, int $noteId): JsonResponse
    {
        $result = $this->noteService->deleteNote($noteId, auth()->id());

        if (!$result['ok']) {
            return $this->error($result['error'], code: $result['code']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Note deleted.',
        ]);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    private function formatNote(InquiryNote $note): array
    {
        return [
            'id'          => $note->id,
            'inquiry_id'  => $note->inquiry_id,
            'content'     => $note->content,
            'is_internal' => $note->is_internal,
            'agent'       => $note->agent ? [
                'id'   => $note->agent->id,
                'name' => $note->agent->name,
            ] : null,
            'created_at'  => $note->created_at?->toISOString(),
        ];
    }
}
