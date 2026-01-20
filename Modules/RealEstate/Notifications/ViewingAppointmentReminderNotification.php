<?php

declare(strict_types=1);

namespace Modules\RealEstate\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

/**
 * Notification sent as a reminder for property viewing appointments.
 */
class ViewingAppointmentReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly array $appointmentData
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
        $appointmentDate = Carbon::parse($this->appointmentData['appointment_date']);
        $propertyTitle = $this->appointmentData['property_title'] ?? 'Property';
        $customerName = $this->appointmentData['customer_name'];
        $customerPhone = $this->appointmentData['customer_phone'] ?? 'N/A';
        $customerEmail = $this->appointmentData['customer_email'] ?? 'N/A';
        $address = $this->appointmentData['address'] ?? 'To be confirmed';

        $mail = (new MailMessage())
            ->subject("Viewing Reminder: {$propertyTitle}")
            ->greeting("Hello {$notifiable->name}!")
            ->line('This is a reminder about an upcoming property viewing.')
            ->line('---')
            ->line('**Appointment Details:**')
            ->line("**Date:** {$appointmentDate->format('l, F j, Y')}")
            ->line("**Time:** {$appointmentDate->format('g:i A')}")
            ->line("**Property:** {$propertyTitle}")
            ->line("**Address:** {$address}");

        $mail->line('---')
            ->line('**Customer Details:**')
            ->line("**Name:** {$customerName}")
            ->line("**Phone:** {$customerPhone}")
            ->line("**Email:** {$customerEmail}");

        if (!empty($this->appointmentData['notes'])) {
            $mail->line('---')
                ->line('**Notes:**')
                ->line($this->appointmentData['notes']);
        }

        return $mail
            ->line('---')
            ->line('Please ensure you are prepared and on time for this viewing.')
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
            'type' => 'viewing_appointment_reminder',
            'appointment_date' => $this->appointmentData['appointment_date'],
            'property_id' => $this->appointmentData['property_id'] ?? null,
            'property_title' => $this->appointmentData['property_title'] ?? null,
            'customer_name' => $this->appointmentData['customer_name'],
            'customer_phone' => $this->appointmentData['customer_phone'] ?? null,
            'customer_email' => $this->appointmentData['customer_email'] ?? null,
            'address' => $this->appointmentData['address'] ?? null,
            'inquiry_id' => $this->appointmentData['inquiry_id'] ?? null,
        ];
    }
}
