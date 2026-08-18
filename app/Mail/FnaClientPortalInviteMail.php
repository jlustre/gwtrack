<?php

namespace App\Mail;

use App\Models\FnaClientInvite;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class FnaClientPortalInviteMail extends Mailable
{
    public function __construct(
        public FnaClientInvite $invite,
        public User $agent,
        public string $securityCode,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->agent->email, $this->agent->name),
            replyTo: [new Address($this->agent->email, $this->agent->name)],
            subject: 'Complete your Financial Needs Analysis',
        );
    }

    public function content(): Content
    {
        $this->invite->loadMissing(['prospect.owner.mentor']);

        $cfmName = $this->invite->prospect?->owner?->mentor?->name;

        if (! $cfmName && $this->agent->hasRole('certified-field-mentor')) {
            $cfmName = $this->agent->name;
        }

        return new Content(
            view: 'emails.fna-client-portal-invite',
            with: [
                'inviteUrl' => $this->invite->inviteUrl(),
                'recipientName' => $this->invite->recipient_name,
                'agentName' => $this->agent->name,
                'cfmName' => $cfmName,
                'securityCode' => $this->securityCode,
                'personalMessage' => $this->invite->personal_message,
                'expiresAt' => $this->invite->expires_at,
            ],
        );
    }
}
