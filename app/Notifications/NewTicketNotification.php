<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a new support ticket is created.
 *
 * This notification is sent to:
 * - Admins: When a user creates a new ticket
 */
class NewTicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly SupportTicket $ticket
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = config('app.admin_url') . '/support-tickets/' . $this->ticket->id;

        return (new MailMessage())
            ->subject('New Support Ticket: ' . $this->ticket->subject)
            ->greeting('Hello!')
            ->line('A new support ticket has been created.')
            ->line('**Ticket Details:**')
            ->line('**Title:** ' . $this->ticket->title)
            ->line('**Subject:** ' . $this->ticket->subject)
            ->line('**Priority:** ' . ucfirst($this->ticket->priority_label))
            ->line('**Department:** ' . ($this->ticket->department?->name ?? 'N/A'))
            ->line('**From:** ' . ($this->ticket->user?->name ?? 'Unknown') . ' (' . ($this->ticket->user?->email ?? 'N/A') . ')')
            ->action('View Ticket', $url)
            ->line('Please review and respond to this ticket as soon as possible.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_support_ticket',
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'subject' => $this->ticket->subject,
            'priority' => $this->ticket->priority,
            'priority_label' => $this->ticket->priority_label,
            'status' => $this->ticket->status,
            'status_label' => $this->ticket->status_label,
            'department' => $this->ticket->department?->name,
            'user_id' => $this->ticket->user_id,
            'user_name' => $this->ticket->user?->name,
            'user_email' => $this->ticket->user?->email,
            'message' => "New ticket: {$this->ticket->subject}",
            'created_at' => $this->ticket->created_at?->toISOString(),
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'support_ticket_created';
    }
}
