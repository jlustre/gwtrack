<?php

namespace App\Notifications\Fna;

use App\Models\FnaClientInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FnaClientPortalSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public FnaClientInvite $invite) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $fna = $this->invite->fnaRecord;

        return [
            'trigger' => 'fna_client_portal_submitted',
            'category' => 'FNA Management',
            'title' => 'Client FNA submitted',
            'message' => ($this->invite->recipient_name ?: $fna?->client_name ?: 'Your client')
                .' completed their FNA via the client portal.',
            'fna_record_id' => $fna?->id,
            'invite_id' => $this->invite->id,
            'action_route' => $fna ? 'team.fna.show' : null,
            'action_route_params' => $fna ? ['fnaRecord' => $fna->id] : [],
            'action_url' => $fna ? route('team.fna.show', $fna, false) : null,
        ];
    }
}
