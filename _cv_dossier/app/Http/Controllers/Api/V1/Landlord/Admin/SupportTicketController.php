<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\SupportTicket\ReplyRequest;
use App\Http\Requests\SupportTicket\UpdateTicketRequest;
use App\Http\Resources\SupportTicketMessageResource;
use App\Http\Resources\SupportTicketResource;
use App\Models\Admin;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Admin Support Ticket Controller
 *
 * Handles support ticket management operations for administrators.
 *
 * @package App\Http\Controllers\Api\V1\Landlord\Admin
 */
#[OA\Tag(
    name: 'Admin Support Tickets',
    description: 'Support ticket management endpoints for administrators (Guard: api_admin)'
)]
final class SupportTicketController extends BaseApiController
{
    public function __construct(
        private readonly SupportTicketService $ticketService,
    ) {}

    /**
     * List all support tickets with filters.
     */
    #[OA\Get(
        path: '/api/v1/admin/support-tickets',
        summary: 'List all support tickets',
        description: 'Get paginated list of all support tickets with optional filters',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Support Tickets']
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        required: false,
        description: 'Filter by status (0=open, 1=closed, 2=pending)',
        schema: new OA\Schema(type: 'integer', enum: [0, 1, 2])
    )]
    #[OA\Parameter(
        name: 'priority',
        in: 'query',
        required: false,
        description: 'Filter by priority (0=low, 1=medium, 2=high, 3=urgent)',
        schema: new OA\Schema(type: 'integer', enum: [0, 1, 2, 3])
    )]
    #[OA\Parameter(
        name: 'department_id',
        in: 'query',
        required: false,
        description: 'Filter by department ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'admin_id',
        in: 'query',
        required: false,
        description: 'Filter by assigned admin ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Parameter(
        name: 'search',
        in: 'query',
        required: false,
        description: 'Search by title, subject, description, or user name/email',
        schema: new OA\Schema(type: 'string', example: 'login issue')
    )]
    #[OA\Parameter(
        name: 'per_page',
        in: 'query',
        required: false,
        description: 'Items per page (max 100)',
        schema: new OA\Schema(type: 'integer', default: 15, maximum: 100)
    )]
    #[OA\Response(
        response: 200,
        description: 'Tickets retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Support tickets retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SupportTicketResource')
                ),
                new OA\Property(
                    property: 'pagination',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 50),
                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 4),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    public function index(Request $request): JsonResponse
    {
        /** @var Admin|null $admin */
        $admin = auth('api_admin')->user();

        if (!$admin) {
            return $this->unauthorized();
        }

        $filters = [
            'status' => $request->query('status') !== null ? (int) $request->query('status') : null,
            'priority' => $request->query('priority') !== null ? (int) $request->query('priority') : null,
            'department_id' => $request->query('department_id'),
            'admin_id' => $request->query('admin_id'),
            'search' => $request->query('search'),
            'per_page' => (int) $request->query('per_page', $this->perPage),
        ];

        $tickets = $this->ticketService->getTicketList($filters);

        return $this->paginated(
            $tickets,
            SupportTicketResource::class,
            'Support tickets retrieved'
        );
    }

    /**
     * Get ticket details with all messages.
     */
    #[OA\Get(
        path: '/api/v1/admin/support-tickets/{id}',
        summary: 'Get ticket details',
        description: 'Retrieve a specific support ticket with all messages',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Support Tickets']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Ticket ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Ticket retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Ticket retrieved'),
                new OA\Property(property: 'data', ref: '#/components/schemas/SupportTicketResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Ticket not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Ticket not found'),
            ]
        )
    )]
    public function show(int $id): JsonResponse
    {
        /** @var Admin|null $admin */
        $admin = auth('api_admin')->user();

        if (!$admin) {
            return $this->unauthorized();
        }

        $ticket = $this->ticketService->getTicketById($id);

        if (!$ticket) {
            return $this->notFound('Ticket not found');
        }

        return $this->success(
            new SupportTicketResource($ticket),
            'Ticket retrieved'
        );
    }

    /**
     * Update ticket details.
     */
    #[OA\Put(
        path: '/api/v1/admin/support-tickets/{id}',
        summary: 'Update ticket',
        description: 'Update ticket status, priority, assignee, or department',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Support Tickets']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Ticket ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/UpdateTicketRequest')
    )]
    #[OA\Response(
        response: 200,
        description: 'Ticket updated successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Ticket updated'),
                new OA\Property(property: 'data', ref: '#/components/schemas/SupportTicketResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Ticket not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Ticket not found'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function update(UpdateTicketRequest $request, int $id): JsonResponse
    {
        /** @var Admin|null $admin */
        $admin = auth('api_admin')->user();

        if (!$admin) {
            return $this->unauthorized();
        }

        $ticket = $this->ticketService->getTicketById($id);

        if (!$ticket) {
            return $this->notFound('Ticket not found');
        }

        try {
            $data = $request->validatedData();
            $updatedTicket = $this->ticketService->updateTicket($ticket, $data);

            return $this->success(
                new SupportTicketResource($updatedTicket),
                'Ticket updated'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to update ticket: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Add admin reply to ticket.
     */
    #[OA\Post(
        path: '/api/v1/admin/support-tickets/{id}/reply',
        summary: 'Reply to ticket',
        description: 'Add an admin reply to a support ticket',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Support Tickets']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Ticket ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/TicketReplyRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Reply added successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Reply added'),
                new OA\Property(property: 'data', ref: '#/components/schemas/SupportTicketMessageResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Ticket not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Ticket not found'),
            ]
        )
    )]
    #[OA\Response(
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function reply(ReplyRequest $request, int $id): JsonResponse
    {
        /** @var Admin|null $admin */
        $admin = auth('api_admin')->user();

        if (!$admin) {
            return $this->unauthorized();
        }

        $ticket = $this->ticketService->getTicketById($id);

        if (!$ticket) {
            return $this->notFound('Ticket not found');
        }

        try {
            $data = $request->validatedData();
            $message = $this->ticketService->addReply(
                $ticket,
                $admin,
                $data['message'],
                $data['attachment']
            );

            return $this->created(
                new SupportTicketMessageResource($message),
                'Reply added'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to add reply: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Close a ticket.
     */
    #[OA\Post(
        path: '/api/v1/admin/support-tickets/{id}/close',
        summary: 'Close ticket',
        description: 'Close a support ticket',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Support Tickets']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'Ticket ID',
        schema: new OA\Schema(type: 'integer', example: 1)
    )]
    #[OA\Response(
        response: 200,
        description: 'Ticket closed successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Ticket closed'),
                new OA\Property(property: 'data', ref: '#/components/schemas/SupportTicketResource'),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Ticket not found',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Ticket not found'),
            ]
        )
    )]
    public function close(int $id): JsonResponse
    {
        /** @var Admin|null $admin */
        $admin = auth('api_admin')->user();

        if (!$admin) {
            return $this->unauthorized();
        }

        $ticket = $this->ticketService->getTicketById($id);

        if (!$ticket) {
            return $this->notFound('Ticket not found');
        }

        try {
            $this->ticketService->closeTicket($ticket);
            $ticket->refresh();

            return $this->success(
                new SupportTicketResource($ticket),
                'Ticket closed'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to close ticket: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get ticket statistics.
     */
    #[OA\Get(
        path: '/api/v1/admin/support-tickets/stats',
        summary: 'Get ticket statistics',
        description: 'Get support ticket statistics (open, pending, closed counts)',
        security: [['sanctum_admin' => []]],
        tags: ['Admin Support Tickets']
    )]
    #[OA\Response(
        response: 200,
        description: 'Statistics retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Statistics retrieved'),
                new OA\Property(
                    property: 'data',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 150),
                        new OA\Property(property: 'open', type: 'integer', example: 45),
                        new OA\Property(property: 'pending', type: 'integer', example: 30),
                        new OA\Property(property: 'closed', type: 'integer', example: 75),
                        new OA\Property(
                            property: 'by_priority',
                            properties: [
                                new OA\Property(property: 'low', type: 'integer', example: 20),
                                new OA\Property(property: 'medium', type: 'integer', example: 30),
                                new OA\Property(property: 'high', type: 'integer', example: 15),
                                new OA\Property(property: 'urgent', type: 'integer', example: 10),
                            ],
                            type: 'object'
                        ),
                        new OA\Property(
                            property: 'by_department',
                            type: 'object',
                            example: ['Technical Support' => 40, 'Billing' => 25, 'General' => 10]
                        ),
                    ],
                    type: 'object'
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'Unauthenticated',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated'),
            ]
        )
    )]
    public function stats(): JsonResponse
    {
        /** @var Admin|null $admin */
        $admin = auth('api_admin')->user();

        if (!$admin) {
            return $this->unauthorized();
        }

        $stats = $this->ticketService->getTicketStats();

        return $this->success($stats, 'Statistics retrieved');
    }
}
