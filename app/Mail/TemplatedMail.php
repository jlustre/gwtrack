<?php



namespace App\Mail;



use Illuminate\Mail\Mailable;

use Illuminate\Mail\Mailables\Content;

use Illuminate\Mail\Mailables\Envelope;



class TemplatedMail extends Mailable

{

    public function __construct(

        public string $customSubject,

        public string $emailBody,

    ) {}



    public function envelope(): Envelope

    {

        return new Envelope(

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

