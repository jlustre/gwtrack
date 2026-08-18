<?php

namespace App\Notifications\Fna;

use App\Models\FnaRecord;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FnaSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly FnaRecord $fna,
        private readonly User $submittedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trigger' => 'fna_submitted',
            'category' => 'FNA Management',
            'title' => 'FNA submitted for review',
            'message' => "{$this->submittedBy->name} submitted an FNA for {$this->fna->client_name} ({$this->fna->reference_code}).",
            'fna_record_id' => $this->fna->id,
            'reference_code' => $this->fna->reference_code,
            'client_name' => $this->fna->client_name,
            'submitted_by' => $this->submittedBy->name,
            'action_route' => 'team.fna.show',
            'action_route_params' => ['fnaRecord' => $this->fna->id],
            'action_url' => route('team.fna.show', $this->fna, false),
        ];
    }
}
