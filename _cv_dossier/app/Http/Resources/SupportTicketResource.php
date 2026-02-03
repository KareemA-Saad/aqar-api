<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin \App\Models\SupportTicket
 */
#[OA\Schema(
    schema: 'SupportTicketResource',
    title: 'Support Ticket Resource',
    description: 'Support ticket resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Unable to login', nullable: true),
        new OA\Property(property: 'subject', type: 'string', example: 'Login Issue', nullable: true),
        new OA\Property(property: 'description', type: 'string', example: 'I cannot login to my account', nullable: true),
        new OA\Property(property: 'via', type: 'string', example: 'email', nullable: true),
        new OA\Property(property: 'operating_system', type: 'string', example: 'Windows 10', nullable: true),
        new OA\Property(property: 'status', type: 'integer', example: 0, description: '0 = open, 1 = closed, 2 = pending'),
        new OA\Property(property: 'status_label', type: 'string', example: 'open'),
        new OA\Property(property: 'priority', type: 'integer', example: 2, description: '0 = low, 1 = medium, 2 = high, 3 = urgent'),
        new OA\Property(property: 'priority_label', type: 'string', example: 'high'),
        new OA\Property(property: 'user', type: 'object', nullable: true, description: 'User details (when loaded)'),
        new OA\Property(property: 'admin', type: 'object', nullable: true, description: 'Admin details (when loaded)'),
        new OA\Property(property: 'department', type: 'object', nullable: true, description: 'Department details (when loaded)'),
        new OA\Property(property: 'messages', type: 'array', items: new OA\Items(type: 'object'), nullable: true, description: 'Ticket messages (when loaded)'),
        new OA\Property(property: 'messages_count', type: 'integer', nullable: true, description: 'Messages count (when counted)'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z', nullable: true),
    ]
)]
class SupportTicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subject' => $this->subject,
            'description' => $this->description,
            'via' => $this->via,
            'operating_system' => $this->operating_system,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'priority' => $this->priority,
            'priority_label' => $this->priority_label,
            'user' => new UserResource($this->whenLoaded('user')),
            'admin' => new AdminResource($this->whenLoaded('admin')),
            'department' => new SupportDepartmentResource($this->whenLoaded('department')),
            'messages' => SupportTicketMessageResource::collection($this->whenLoaded('messages')),
            'messages_count' => $this->whenCounted('messages'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

