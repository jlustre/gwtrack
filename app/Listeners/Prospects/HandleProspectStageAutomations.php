<?php

namespace App\Listeners\Prospects;

use App\Events\Prospects\ProspectStageChanged;
use App\Services\Prospects\CrmAutomationService;

class HandleProspectStageAutomations
{
    public function __construct(private CrmAutomationService $automations) {}

    public function handle(ProspectStageChanged $event): void
    {
        if ($event->fromStageId === null) {
            return;
        }

        $this->automations->handleStageChanged($event->prospect, $event->actor, $event->toStageId);
    }
}
