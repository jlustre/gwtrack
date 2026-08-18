<?php

namespace App\Listeners\Fna;

use App\Events\Fna\FnaApproved;
use App\Models\FnaRecord;
use App\Services\Notifications\NotificationOrchestrator;

class NotifyAssociateOfFnaApproval
{
    public function __construct(
        private readonly NotificationOrchestrator $notifications,
    ) {}

    public function handle(FnaApproved $event): void
    {
        $event->fna->loadMissing('owner');

        if (! $event->fna->owner) {
            return;
        }

        $fna = $event->fna;

        $this->notifications->dispatch('fna_approved', [
            'queue' => true,
            'sender' => $event->reviewedBy,
            'recipients' => [$fna->owner->id],
            'module' => 'fna',
            'priority' => 'medium',
            'related' => ['type' => FnaRecord::class, 'id' => $fna->id],
            'related_user_id' => $event->reviewedBy->id,
            'template_data' => [
                'reviewed_by' => $event->reviewedBy->name,
                'client_name' => $fna->client_name,
            ],
            'title' => 'FNA approved by CFM',
            'message' => "{$event->reviewedBy->name} approved your FNA for {$fna->client_name}. You may schedule a client review meeting.",
            'action_link' => [
                'route' => 'team.fna.show',
                'params' => ['fnaRecord' => $fna->id],
                'label' => 'View FNA',
            ],
            'payload' => [
                'fna_record_id' => $fna->id,
                'reference_code' => $fna->reference_code,
                'client_name' => $fna->client_name,
                'reviewed_by' => $event->reviewedBy->name,
            ],
        ]);
    }
}
