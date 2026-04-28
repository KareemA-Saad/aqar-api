<?php

declare(strict_types=1);

namespace Modules\RealEstate\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\RealEstate\Entities\Reminder;

/**
 * Notification sent to an agent when one of their reminders is due.
 *
 * Delivered via:
 *  - mail  (standard MailMessage)
 *  - database  (stored in notifications table for in-app bell)
 *
 * Part of F2.1: Follow-up Reminders & SLA Timers
 */
class ReminderDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Reminder $reminder
    ) {}

    /**
     * Delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Mail representation.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $inquiry  = $this->reminder->inquiry;
        $property = $inquiry?->property;
        $compound = $inquiry?->compound;

        $subject = $property
            ? "Reminder: Follow up on {$property->title}"
            : ($compound ? "Reminder: Follow up on inquiry for {$compound->name}" : 'Reminder: Follow up on inquiry');

        $mail = (new MailMessage())
            ->subject($subject)
            ->greeting("Hello {$notifiable->name},")
            ->line('This is your scheduled follow-up reminder.');

        if ($inquiry) {
            $mail->line("**Lead:** {$inquiry->name} ({$inquiry->phone})")
                 ->line("**Status:** " . ucfirst($inquiry->status))
                 ->line("**SLA Status:** " . ucfirst($inquiry->sla_status));
        }

        if ($this->reminder->message) {
            $mail->line('---')
                 ->line("**Your Note:** {$this->reminder->message}");
        }

        return $mail
            ->line('---')
            ->line('Please follow up with this lead as soon as possible.')
            ->salutation('Best regards, ' . config('app.name'));
    }

    /**
     * Database / in-app notification payload.
     */
    public function toDatabase(object $notifiable): array
    {
        $inquiry = $this->reminder->inquiry;

        return [
            'type'        => 'reminder_due',
            'reminder_id' => $this->reminder->id,
            'inquiry_id'  => $this->reminder->inquiry_id,
            'lead_name'   => $inquiry?->name,
            'lead_phone'  => $inquiry?->phone,
            'sla_status'  => $inquiry?->sla_status,
            'message'     => $this->reminder->message,
        ];
    }
}
