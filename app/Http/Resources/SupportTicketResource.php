<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\SupportTicket
 */
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

