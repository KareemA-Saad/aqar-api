<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a reply is added to a support ticket.
 *
 * This notification is sent to:
 * - User: When an admin replies to their ticket
 * - Admins: When a user replies to a ticket
 */
class TicketReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly SupportTicketMessage $message
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
        $isAdminReply = $this->message->isFromAdmin();
        $senderName = $isAdminReply
            ? ($this->message->admin?->name ?? 'Support Team')
            : ($this->message->user?->name ?? 'User');

        // Build URL based on who's receiving the notification
        $url = $isAdminReply
            ? config('app.frontend_url') . '/support-tickets/' . $this->ticket->id
            : config('app.admin_url') . '/support-tickets/' . $this->ticket->id;

        $mailMessage = (new MailMessage())
            ->subject('Re: ' . $this->ticket->subject . ' - Ticket #' . $this->ticket->id);

        if ($isAdminReply) {
            // Notification to user
            $mailMessage
                ->greeting('Hello ' . ($this->ticket->user?->name ?? 'there') . '!')
                ->line('Our support team has replied to your ticket.')
                ->line('**Ticket:** ' . $this->ticket->subject)
                ->line('**Reply from:** ' . $senderName)
                ->line('---')
                ->line($this->truncateMessage($this->message->message))
                ->line('---')
                ->action('View Full Conversation', $url)
                ->line('Thank you for contacting us!');
        } else {
            // Notification to admin
            $mailMessage
                ->greeting('Hello!')
                ->line('A user has replied to a support ticket.')
                ->line('**Ticket:** ' . $this->ticket->subject)
                ->line('**Status:** ' . ucfirst($this->ticket->status_label))
                ->line('**Reply from:** ' . $senderName)
                ->line('---')
                ->line($this->truncateMessage($this->message->message))
                ->line('---')
                ->action('View & Respond', $url)
                ->line('Please review and respond as needed.');
        }

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $isAdminReply = $this->message->isFromAdmin();

        return [
            'type' => 'ticket_reply',
            'ticket_id' => $this->ticket->id,
            'ticket_title' => $this->ticket->title,
            'ticket_subject' => $this->ticket->subject,
            'ticket_status' => $this->ticket->status,
            'ticket_status_label' => $this->ticket->status_label,
            'message_id' => $this->message->id,
            'message_preview' => $this->truncateMessage($this->message->message, 100),
            'is_admin_reply' => $isAdminReply,
            'sender_id' => $isAdminReply ? $this->message->admin?->id : $this->message->user?->id,
            'sender_name' => $isAdminReply ? $this->message->admin?->name : $this->message->user?->name,
            'sender_type' => $this->message->type,
            'has_attachment' => !empty($this->message->attachment),
            'message' => $isAdminReply
                ? "Support replied to: {$this->ticket->subject}"
                : "User replied to: {$this->ticket->subject}",
            'created_at' => $this->message->created_at?->toISOString(),
        ];
    }

    /**
     * Get the notification's database type.
     */
    public function databaseType(object $notifiable): string
    {
        return 'support_ticket_reply';
    }

    /**
     * Truncate message for preview.
     */
    private function truncateMessage(string $message, int $length = 200): string
    {
        $message = strip_tags($message);

        if (strlen($message) <= $length) {
            return $message;
        }

        return substr($message, 0, $length) . '...';
    }
}
