<?php

declare(strict_types=1);

namespace Modules\RealEstate\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

/**
 * Email sent as a viewing appointment reminder.
 */
class ViewingReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Appointment date.
     */
    public Carbon $appointmentDate;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly array $appointmentData
    ) {
        $this->appointmentDate = Carbon::parse($appointmentData['appointment_date']);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $propertyTitle = $this->appointmentData['property_title'] ?? 'Property';
        
        return new Envelope(
            subject: "Viewing Reminder: {$propertyTitle} - {$this->appointmentDate->format('M d, Y')}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'realestate::emails.viewing-reminder',
            with: [
                'appointmentData' => $this->appointmentData,
                'appointmentDate' => $this->appointmentDate,
            ],
        );
    }
}
