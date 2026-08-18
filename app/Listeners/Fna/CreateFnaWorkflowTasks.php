<?php

namespace App\Listeners\Fna;

use App\Events\Fna\FnaApproved;
use App\Events\Fna\FnaRevisionRequested;
use App\Events\Fna\FnaSubmittedForReview;
use App\Services\Fna\FnaTaskBridge;

class CreateFnaWorkflowTasks
{
    public function __construct(private FnaTaskBridge $tasks) {}

    public function handleSubmitted(FnaSubmittedForReview $event): void
    {
        $template = config('fna.task_templates.submitted');

        if (is_array($template)) {
            $this->tasks->createFromTemplate($event->fna, $event->submittedBy, $template, $event->cfm);
        }
    }

    public function handleRevision(FnaRevisionRequested $event): void
    {
        $template = config('fna.task_templates.revision_requested');

        if (is_array($template)) {
            $this->tasks->createFromTemplate($event->fna, $event->reviewedBy, $template, $event->fna->owner);
        }
    }

    public function handleApproved(FnaApproved $event): void
    {
        $template = config('fna.task_templates.approved');

        if (is_array($template)) {
            $this->tasks->createFromTemplate($event->fna, $event->reviewedBy, $template, $event->fna->owner);
        }
    }
}
