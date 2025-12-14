<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SupportTicket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a new support ticket is created.
 */
class TicketCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly SupportTicket $ticket
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.support-tickets'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ticket.created';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'subject' => $this->ticket->subject,
            'priority' => $this->ticket->priority,
            'priority_label' => $this->ticket->priority_label,
            'status' => $this->ticket->status,
            'status_label' => $this->ticket->status_label,
            'user' => [
                'id' => $this->ticket->user?->id,
                'name' => $this->ticket->user?->name,
                'email' => $this->ticket->user?->email,
            ],
            'department' => [
                'id' => $this->ticket->department?->id,
                'name' => $this->ticket->department?->name,
            ],
            'created_at' => $this->ticket->created_at?->toISOString(),
        ];
    }
}
