<?php

namespace App\Jobs\Prospects;

use App\Services\Prospects\ProspectFollowUpEngine;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RunProspectFollowUpEngine implements ShouldQueue
{
    use Queueable;

    public function handle(ProspectFollowUpEngine $engine): void
    {
        $engine->runForAllOwners();
    }
}
