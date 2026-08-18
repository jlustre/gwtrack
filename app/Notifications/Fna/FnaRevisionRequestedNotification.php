<?php

namespace App\Notifications\Fna;

use App\Models\FnaRecord;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FnaRevisionRequestedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly FnaRecord $fna,
        private readonly User $reviewedBy,
        private readonly string $comment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trigger' => 'fna_revision_requested',
            'category' => 'FNA Management',
            'title' => 'FNA revision requested',
            'message' => "{$this->reviewedBy->name} requested revisions on your FNA for {$this->fna->client_name}.",
            'fna_record_id' => $this->fna->id,
            'reference_code' => $this->fna->reference_code,
            'client_name' => $this->fna->client_name,
            'reviewed_by' => $this->reviewedBy->name,
            'revision_comment' => $this->comment,
            'action_route' => 'team.fna.wizard',
            'action_route_params' => ['fnaRecord' => $this->fna->id],
            'action_url' => route('team.fna.wizard', $this->fna, false),
        ];
    }
}
