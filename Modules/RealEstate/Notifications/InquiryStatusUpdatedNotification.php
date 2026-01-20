<?php

declare(strict_types=1);

namespace Modules\RealEstate\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\RealEstate\Entities\PropertyInquiry;

/**
 * Notification sent when an inquiry status is updated.
 */
class InquiryStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Status labels for display.
     */
    protected array $statusLabels = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'qualified' => 'Qualified Lead',
        'converted' => 'Converted',
        'closed' => 'Closed',
    ];

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly PropertyInquiry $inquiry,
        public readonly string $previousStatus
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $newStatusLabel = $this->statusLabels[$this->inquiry->status] ?? $this->inquiry->status;
        $previousStatusLabel = $this->statusLabels[$this->previousStatus] ?? $this->previousStatus;

        return (new MailMessage())
            ->subject("Inquiry Status Updated: {$this->inquiry->name}")
            ->greeting("Hello!")
            ->line("An inquiry status has been updated.")
            ->line("**Customer:** {$this->inquiry->name}")
            ->line("**Previous Status:** {$previousStatusLabel}")
            ->line("**New Status:** {$newStatusLabel}")
            ->line("**Updated at:** " . now()->format('M d, Y H:i'))
            ->salutation('Best regards, ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'inquiry_status_updated',
            'inquiry_id' => $this->inquiry->id,
            'customer_name' => $this->inquiry->name,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->inquiry->status,
            'property_id' => $this->inquiry->property_id,
            'property_title' => $this->inquiry->property?->title,
            'updated_at' => now()->toISOString(),
        ];
    }
}
