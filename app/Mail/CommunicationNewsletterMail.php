<?php

namespace App\Mail;

use App\Models\AnnouncementNewsletter;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CommunicationNewsletterMail extends Mailable
{
    public function __construct(
        public User $user,
        public AnnouncementNewsletter $newsletter,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->newsletter->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: view('emails.communication.newsletter', [
                'user' => $this->user,
                'newsletter' => $this->newsletter,
            ])->render(),
        );
    }
}
