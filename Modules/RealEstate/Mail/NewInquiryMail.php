<?php

declare(strict_types=1);

namespace Modules\RealEstate\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\RealEstate\Entities\PropertyInquiry;

/**
 * Email sent to agents when a new inquiry is received.
 */
class NewInquiryMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly PropertyInquiry $inquiry
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $property = $this->inquiry->property;
        $compound = $this->inquiry->compound;
        
        $subject = $property 
            ? "New Inquiry: {$property->title}"
            : ($compound ? "New Inquiry: {$compound->name}" : "New Property Inquiry");

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'realestate::emails.new-inquiry',
            with: [
                'inquiry' => $this->inquiry,
                'property' => $this->inquiry->property,
                'compound' => $this->inquiry->compound,
                'adminUrl' => config('app.admin_url', config('app.url') . '/admin') . '/realestate/inquiries/' . $this->inquiry->id,
            ],
        );
    }
}
