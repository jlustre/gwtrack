<?php

namespace App\Listeners\Fna;

use App\Events\Fna\FnaApproved;
use App\Events\Fna\FnaSubmittedForReview;
use App\Services\Fna\FnaGoalsBridge;

class SyncFnaGoalsBridge
{
    public function __construct(
        private readonly FnaGoalsBridge $bridge,
    ) {}

    public function handleSubmitted(FnaSubmittedForReview $event): void
    {
        $this->bridge->handleSubmitted($event->fna->fresh(), $event->submittedBy);
    }

    public function handleApproved(FnaApproved $event): void
    {
        $this->bridge->handleApproved($event->fna->fresh(), $event->reviewedBy);
    }
}
