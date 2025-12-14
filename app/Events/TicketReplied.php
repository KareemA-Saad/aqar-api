<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event dispatched when a reply is added to a support ticket.
 */
class TicketReplied implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly SupportTicketMessage $message
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Broadcast to the ticket channel
        $channels[] = new PrivateChannel("ticket.{$this->ticket->id}");

        // If reply is from admin, notify the user
        if ($this->message->isFromAdmin()) {
            $channels[] = new PrivateChannel("user.{$this->ticket->user_id}.tickets");
        } else {
            // If reply is from user, notify admins
            $channels[] = new PrivateChannel('admin.support-tickets');
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'ticket.replied';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'ticket_title' => $this->ticket->title,
            'ticket_status' => $this->ticket->status,
            'ticket_status_label' => $this->ticket->status_label,
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->message,
                'type' => $this->message->type,
                'is_from_admin' => $this->message->isFromAdmin(),
                'attachment' => $this->message->attachment,
                'created_at' => $this->message->created_at?->toISOString(),
            ],
            'sender' => $this->message->isFromAdmin()
                ? [
                    'id' => $this->message->admin?->id,
                    'name' => $this->message->admin?->name,
                ]
                : [
                    'id' => $this->message->user?->id,
                    'name' => $this->message->user?->name,
                ],
        ];
    }
}
