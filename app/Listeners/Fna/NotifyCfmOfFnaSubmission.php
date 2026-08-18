<?php

namespace App\Listeners\Fna;

use App\Events\Fna\FnaSubmittedForReview;
use App\Models\FnaRecord;
use App\Services\Notifications\NotificationOrchestrator;

class NotifyCfmOfFnaSubmission
{
    public function __construct(
        private readonly NotificationOrchestrator $notifications,
    ) {}

    public function handle(FnaSubmittedForReview $event): void
    {
        if (! $event->cfm) {
            return;
        }

        $fna = $event->fna;

        $this->notifications->dispatch('fna_submitted', [
            'queue' => true,
            'sender' => $event->submittedBy,
            'recipients' => [$event->cfm->id],
            'module' => 'fna',
            'priority' => 'medium',
            'related' => ['type' => FnaRecord::class, 'id' => $fna->id],
            'related_user_id' => $event->submittedBy->id,
            'template_data' => [
                'submitted_by' => $event->submittedBy->name,
                'client_name' => $fna->client_name,
                'reference_code' => $fna->reference_code,
            ],
            'title' => 'FNA submitted for review',
            'message' => "{$event->submittedBy->name} submitted an FNA for {$fna->client_name} ({$fna->reference_code}).",
            'action_link' => [
                'route' => 'team.fna.show',
                'params' => ['fnaRecord' => $fna->id],
                'label' => 'Review FNA',
            ],
            'payload' => [
                'fna_record_id' => $fna->id,
                'reference_code' => $fna->reference_code,
                'client_name' => $fna->client_name,
                'submitted_by' => $event->submittedBy->name,
            ],
        ]);
    }
}
