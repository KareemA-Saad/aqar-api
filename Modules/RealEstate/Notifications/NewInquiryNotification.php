<?php

declare(strict_types=1);

namespace Modules\RealEstate\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\RealEstate\Entities\PropertyInquiry;

/**
 * Notification sent to agents/admins when a new property inquiry is submitted.
 */
class NewInquiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly PropertyInquiry $inquiry
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
        $property = $this->inquiry->property;
        $compound = $this->inquiry->compound;
        
        $subject = $property 
            ? "New Inquiry for: {$property->title}"
            : ($compound ? "New Inquiry for: {$compound->name}" : "New Property Inquiry");

        $adminUrl = config('app.admin_url', config('app.url') . '/admin');
        $url = $adminUrl . '/realestate/inquiries/' . $this->inquiry->id;

        $mail = (new MailMessage())
            ->subject($subject)
            ->greeting("Hello {$notifiable->name}!")
            ->line('You have received a new property inquiry.')
            ->line('---')
            ->line('**Customer Details:**')
            ->line("**Name:** {$this->inquiry->name}")
            ->line("**Email:** {$this->inquiry->email}")
            ->line("**Phone:** {$this->inquiry->phone}");

        if ($property) {
            $mail->line('---')
                ->line('**Property:**')
                ->line("**Title:** {$property->title}")
                ->line("**Reference:** {$property->reference_number}")
                ->line("**Price:** {$property->price_formatted}");
        }

        if ($compound) {
            $mail->line('---')
                ->line('**Compound:** ' . $compound->name);
        }

        if ($this->inquiry->message) {
            $mail->line('---')
                ->line('**Customer Message:**')
                ->line($this->inquiry->message);
        }

        return $mail
            ->action('View Inquiry', $url)
            ->line('Please respond to this inquiry promptly.')
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
            'type' => 'new_property_inquiry',
            'inquiry_id' => $this->inquiry->id,
            'customer_name' => $this->inquiry->name,
            'customer_email' => $this->inquiry->email,
            'customer_phone' => $this->inquiry->phone,
            'property_id' => $this->inquiry->property_id,
            'property_title' => $this->inquiry->property?->title,
            'compound_id' => $this->inquiry->compound_id,
            'compound_name' => $this->inquiry->compound?->name,
            'message' => $this->inquiry->message,
            'created_at' => $this->inquiry->created_at->toISOString(),
        ];
    }
}
