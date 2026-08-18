<?php

namespace App\Listeners\Fna;

use App\Events\Fna\FnaApproved;
use App\Events\Fna\FnaMeetingScheduled;
use App\Events\Fna\FnaRevisionRequested;
use App\Events\Fna\FnaSubmittedForReview;
use App\Services\Fna\FnaProspectBridge;

class SyncFnaProspectIntegration
{
    public function __construct(private FnaProspectBridge $bridge) {}

    public function handleSubmitted(FnaSubmittedForReview $event): void
    {
        $fna = $event->fna->fresh();

        $this->bridge->syncProspectFnaStatus($fna);
        $this->bridge->logProspectTimeline(
            $fna,
            $event->submittedBy,
            "FNA submitted to CFM for review ({$fna->statusLabel()}).",
        );
    }

    public function handleApproved(FnaApproved $event): void
    {
        $fna = $event->fna->fresh();

        $this->bridge->syncProspectFnaStatus($fna);
        $this->bridge->logProspectTimeline(
            $fna,
            $event->reviewedBy,
            'FNA approved by CFM. Ready to schedule client review meeting.',
        );
        $this->bridge->advanceStageAfterApproval($fna, $event->reviewedBy);
    }

    public function handleRevision(FnaRevisionRequested $event): void
    {
        $fna = $event->fna->fresh();

        $this->bridge->syncProspectFnaStatus($fna);
        $this->bridge->logProspectTimeline(
            $fna,
            $event->reviewedBy,
            "CFM requested FNA revisions: {$event->comment}",
        );
    }

    public function handleMeetingScheduled(FnaMeetingScheduled $event): void
    {
        $fna = $event->fna->fresh();

        $this->bridge->syncProspectFnaStatus($fna);
        $this->bridge->logProspectTimeline(
            $fna,
            $event->scheduledBy,
            'Client FNA review meeting scheduled for '.$event->event->starts_at?->format('M j, Y g:i A').'.',
        );
    }
}
