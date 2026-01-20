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
 * Email sent to customers confirming their inquiry was received.
 */
class InquiryConfirmationMail extends Mailable implements ShouldQueue
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
        return new Envelope(
            subject: "We've Received Your Inquiry - " . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'realestate::emails.inquiry-confirmation',
            with: [
                'inquiry' => $this->inquiry,
                'property' => $this->inquiry->property,
                'compound' => $this->inquiry->compound,
                'websiteUrl' => config('app.url'),
            ],
        );
    }
}
