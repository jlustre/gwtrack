<?php

namespace App\Listeners\Fna;

use App\Events\Fna\FnaRevisionRequested;
use App\Models\FnaRecord;
use App\Services\Notifications\NotificationOrchestrator;

class NotifyAssociateOfFnaRevision
{
    public function __construct(
        private readonly NotificationOrchestrator $notifications,
    ) {}

    public function handle(FnaRevisionRequested $event): void
    {
        $event->fna->loadMissing('owner');

        if (! $event->fna->owner) {
            return;
        }

        $fna = $event->fna;

        $this->notifications->dispatch('fna_revision_requested', [
            'queue' => true,
            'sender' => $event->reviewedBy,
            'recipients' => [$fna->owner->id],
            'module' => 'fna',
            'priority' => 'high',
            'related' => ['type' => FnaRecord::class, 'id' => $fna->id],
            'related_user_id' => $event->reviewedBy->id,
            'template_data' => [
                'reviewed_by' => $event->reviewedBy->name,
                'client_name' => $fna->client_name,
            ],
            'title' => 'FNA revision requested',
            'message' => "{$event->reviewedBy->name} requested revisions on your FNA for {$fna->client_name}.",
            'action_link' => [
                'route' => 'team.fna.wizard',
                'params' => ['fnaRecord' => $fna->id],
                'label' => 'Revise FNA',
            ],
            'payload' => [
                'fna_record_id' => $fna->id,
                'reference_code' => $fna->reference_code,
                'client_name' => $fna->client_name,
                'reviewed_by' => $event->reviewedBy->name,
                'revision_comment' => $event->comment,
            ],
        ]);
    }
}
