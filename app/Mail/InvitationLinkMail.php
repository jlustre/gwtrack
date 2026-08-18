<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InvitationLinkMail extends Mailable
{
    public function __construct(
        public string $customSubject,
        public string $emailBody,
        public string $senderName,
        public string $senderEmail,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address($this->senderEmail, $this->senderName),
            replyTo: [new \Illuminate\Mail\Mailables\Address($this->senderEmail, $this->senderName)],
            subject: $this->customSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->emailBody,
        );
    }
}
