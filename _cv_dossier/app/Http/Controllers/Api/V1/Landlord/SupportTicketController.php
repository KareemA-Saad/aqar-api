<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Landlord;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\SupportTicket\ReplyRequest;
use App\Http\Requests\SupportTicket\StoreTicketRequest;
use App\Http\Resources\SupportDepartmentResource;
use App\Http\Resources\SupportTicketMessageResource;
use App\Http\Resources\SupportTicketResource;
use App\Models\User;
use App\Services\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * User Support Ticket Controller
 *
 * Handles support ticket operations for users.
 *
 * @package App\Http\Controllers\Api\V1\Landlord
 */
#[OA\Tag(
    name: 'User Support Tickets',
    description: 'Support ticket endpoints for users (Guard: api_user). Manage own support tickets.'
)]
final class SupportTicketController extends BaseApiController
{
    public function __construct(
        private readonly SupportTicketService $ticketService,
    ) {}

    /**
     * List user's own support tickets.
     */
    #[OA\Get(
        path: '/api/v1/support-tickets',
        summary: 'List own tickets',
        description: 'Get paginated list of the authenticated user\'s support tickets',
        security: [['sanctum_user' => []]],
        tags: ['User Support Tickets']
    )]
    #[OA\Parameter(
        name: 'status',
        in: 'query',
        required: false,
        description: 'Filter by status (0=open, 1=closed, 2=pending)',
        schema: new OA\Schema(type: 'integer', enum: [0, 1, 2])
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
                new OA\Property(property: 'message', type: 'string', example: 'Your support tickets retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SupportTicketResource')
                ),
                new OA\Property(
                    property: 'pagination',
                    properties: [
                        new OA\Property(property: 'total', type: 'integer', example: 10),
                        new OA\Property(property: 'per_page', type: 'integer', example: 15),
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'last_page', type: 'integer', example: 1),
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
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $filters = [
            'status' => $request->query('status') !== null ? (int) $request->query('status') : null,
            'per_page' => (int) $request->query('per_page', $this->perPage),
        ];

        $tickets = $this->ticketService->getUserTickets($user, $filters);

        return $this->paginated(
            $tickets,
            SupportTicketResource::class,
            'Your support tickets retrieved'
        );
    }

    /**
     * Create a new support ticket.
     */
    #[OA\Post(
        path: '/api/v1/support-tickets',
        summary: 'Create a ticket',
        description: 'Create a new support ticket',
        security: [['sanctum_user' => []]],
        tags: ['User Support Tickets']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: '#/components/schemas/StoreTicketRequest')
    )]
    #[OA\Response(
        response: 201,
        description: 'Ticket created successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Ticket created successfully'),
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
        response: 422,
        description: 'Validation error',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                new OA\Property(property: 'errors', type: 'object'),
            ]
        )
    )]
    public function store(StoreTicketRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        try {
            $data = $request->validatedData();
            $ticket = $this->ticketService->createTicket($user, $data);

            return $this->created(
                new SupportTicketResource($ticket),
                'Ticket created successfully'
            );
        } catch (\Exception $e) {
            return $this->error('Failed to create ticket: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get ticket details with messages.
     */
    #[OA\Get(
        path: '/api/v1/support-tickets/{id}',
        summary: 'Get ticket details',
        description: 'Retrieve a specific support ticket with all messages (only own tickets)',
        security: [['sanctum_user' => []]],
        tags: ['User Support Tickets']
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
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $ticket = $this->ticketService->getUserTicketById($id, $user);

        if (!$ticket) {
            return $this->notFound('Ticket not found');
        }

        return $this->success(
            new SupportTicketResource($ticket),
            'Ticket retrieved'
        );
    }

    /**
     * Add user reply to ticket.
     */
    #[OA\Post(
        path: '/api/v1/support-tickets/{id}/reply',
        summary: 'Reply to ticket',
        description: 'Add a reply to your own support ticket',
        security: [['sanctum_user' => []]],
        tags: ['User Support Tickets']
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
        response: 403,
        description: 'Ticket is closed',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'message', type: 'string', example: 'Cannot reply to a closed ticket'),
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
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $ticket = $this->ticketService->getUserTicketById($id, $user);

        if (!$ticket) {
            return $this->notFound('Ticket not found');
        }

        // Check if ticket is closed
        if ($ticket->status === 1) {
            return $this->forbidden('Cannot reply to a closed ticket');
        }

        try {
            $data = $request->validatedData();
            $message = $this->ticketService->addReply(
                $ticket,
                $user,
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
     * Close user's own ticket.
     */
    #[OA\Post(
        path: '/api/v1/support-tickets/{id}/close',
        summary: 'Close ticket',
        description: 'Close your own support ticket',
        security: [['sanctum_user' => []]],
        tags: ['User Support Tickets']
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
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $ticket = $this->ticketService->getUserTicketById($id, $user);

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
     * Get available departments for ticket creation.
     */
    #[OA\Get(
        path: '/api/v1/support-departments',
        summary: 'List departments',
        description: 'Get list of active support departments for ticket creation',
        security: [['sanctum_user' => []]],
        tags: ['User Support Tickets']
    )]
    #[OA\Response(
        response: 200,
        description: 'Departments retrieved successfully',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Departments retrieved'),
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/SupportDepartmentResource')
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
    public function departments(): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('api_user')->user();

        if (!$user) {
            return $this->unauthorized();
        }

        $departments = $this->ticketService->getActiveDepartments();

        return $this->success(
            SupportDepartmentResource::collection($departments),
            'Departments retrieved'
        );
    }
}
