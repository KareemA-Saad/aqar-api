<?php

declare(strict_types=1);

namespace Modules\RealEstate\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\RealEstate\Entities\PropertyInquiry;

/**
 * Notification sent to customers confirming their inquiry was received.
 */
class InquiryReceivedNotification extends Notification implements ShouldQueue
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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->inquiry->property;
        $compound = $this->inquiry->compound;
        
        $subject = "We've Received Your Inquiry";
        $websiteUrl = config('app.url');

        $mail = (new MailMessage())
            ->subject($subject)
            ->greeting("Hello {$this->inquiry->name}!")
            ->line('Thank you for your interest! We have received your inquiry and our team will contact you shortly.')
            ->line('---')
            ->line('**Your Inquiry Details:**')
            ->line("**Reference Number:** INQ-{$this->inquiry->id}");

        if ($property) {
            $mail->line('---')
                ->line('**Property of Interest:**')
                ->line("**Title:** {$property->title}");
            
            if ($property->price_formatted) {
                $mail->line("**Price:** {$property->price_formatted}");
            }
            
            if ($property->compound) {
                $mail->line("**Location:** {$property->compound->name}");
            }
        }

        if ($compound && !$property) {
            $mail->line('---')
                ->line('**Compound of Interest:**')
                ->line("**Name:** {$compound->name}");
            
            if ($compound->area) {
                $mail->line("**Area:** {$compound->area->name}");
            }
        }

        $mail->line('---')
            ->line('**What happens next?**')
            ->line('1. Our team will review your inquiry')
            ->line('2. A dedicated agent will be assigned to assist you')
            ->line('3. You will receive a call/email within 24 hours')
            ->line('---')
            ->line('If you have any urgent questions, feel free to contact us directly.')
            ->action('Browse More Properties', $websiteUrl . '/properties')
            ->salutation('Best regards, ' . config('app.name') . ' Team');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'inquiry_received_confirmation',
            'inquiry_id' => $this->inquiry->id,
            'property_id' => $this->inquiry->property_id,
            'compound_id' => $this->inquiry->compound_id,
        ];
    }
}
