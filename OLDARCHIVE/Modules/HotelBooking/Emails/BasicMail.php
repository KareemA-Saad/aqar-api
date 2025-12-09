<?php

namespace Modules\HotelBooking\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class BasicMail extends Mailable
{
    use Queueable, SerializesModels;
    public $data;

    public function __construct($args)
    {
        $this->data = $args;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(get_static_option('site_global_email'), get_static_option('site_title'))
            ->subject($this->data['subject'])
            ->view('emails.basic-mail-template');
    }
}
