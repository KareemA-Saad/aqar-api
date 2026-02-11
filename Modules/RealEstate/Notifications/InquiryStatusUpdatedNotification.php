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
        // Send email to customers (users), database notification to agents/admins
        if ($notifiable->id === $this->inquiry->user_id) {
            return ['mail', 'database'];
        }
        
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $newStatusLabel = $this->statusLabels[$this->inquiry->status] ?? $this->inquiry->status;
        $previousStatusLabel = $this->statusLabels[$this->previousStatus] ?? $this->previousStatus;

        $propertyInfo = $this->inquiry->property 
            ? "Property: {$this->inquiry->property->title}" 
            : ($this->inquiry->compound ? "Compound: {$this->inquiry->compound->name}" : "General Inquiry");

        return (new MailMessage())
            ->subject("Your Inquiry Status Has Been Updated")
            ->greeting("Hello {$this->inquiry->name}!")
            ->line("We wanted to update you on the status of your inquiry.")
            ->line("**{$propertyInfo}**")
            ->line("**Previous Status:** {$previousStatusLabel}")
            ->line("**Current Status:** {$newStatusLabel}")
            ->line("Our team is working on your inquiry and will reach out to you shortly.")
            ->line("If you have any questions, feel free to contact us.")
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
