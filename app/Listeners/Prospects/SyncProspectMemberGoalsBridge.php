<?php

namespace App\Listeners\Prospects;

use App\Events\Prospects\ProspectConverted;
use App\Services\Prospects\ProspectMemberGoalsBridge;

class SyncProspectMemberGoalsBridge
{
    public function __construct(
        private readonly ProspectMemberGoalsBridge $bridge,
    ) {}

    public function handle(ProspectConverted $event): void
    {
        $this->bridge->handleConversion($event);
    }
}
