<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * @mixin \App\Models\SupportTicketMessage
 */
#[OA\Schema(
    schema: 'SupportTicketMessageResource',
    title: 'Support Ticket Message Resource',
    description: 'Support ticket message resource representation',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'message', type: 'string', example: 'Thank you for reaching out. We are working on your issue.'),
        new OA\Property(property: 'attachment', type: 'string', example: 'uploads/tickets/file.pdf', nullable: true),
        new OA\Property(property: 'type', type: 'string', enum: ['user', 'admin'], example: 'admin'),
        new OA\Property(property: 'is_from_admin', type: 'boolean', example: true),
        new OA\Property(property: 'notify', type: 'boolean', example: true),
        new OA\Property(property: 'sender', type: 'object', nullable: true, description: 'Sender details (User or Admin)'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z', nullable: true),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', example: '2024-01-01T00:00:00.000000Z', nullable: true),
    ]
)]
class SupportTicketMessageResource extends JsonResource
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
            'message' => $this->message,
            'attachment' => $this->attachment,
            'type' => $this->type,
            'is_from_admin' => $this->isFromAdmin(),
            'notify' => $this->notify,
            'sender' => $this->when(
                $this->relationLoaded('user') || $this->relationLoaded('admin'),
                fn () => $this->isFromAdmin()
                    ? new AdminResource($this->admin)
                    : new UserResource($this->user)
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

