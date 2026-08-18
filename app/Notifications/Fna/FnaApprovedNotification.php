<?php

namespace App\Notifications\Fna;

use App\Models\FnaRecord;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class FnaApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly FnaRecord $fna,
        private readonly User $reviewedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trigger' => 'fna_approved',
            'category' => 'FNA Management',
            'title' => 'FNA approved by CFM',
            'message' => "{$this->reviewedBy->name} approved your FNA for {$this->fna->client_name}. You may schedule a client review meeting.",
            'fna_record_id' => $this->fna->id,
            'reference_code' => $this->fna->reference_code,
            'client_name' => $this->fna->client_name,
            'reviewed_by' => $this->reviewedBy->name,
            'action_route' => 'team.fna.show',
            'action_route_params' => ['fnaRecord' => $this->fna->id],
            'action_url' => route('team.fna.show', $this->fna, false),
        ];
    }
}
