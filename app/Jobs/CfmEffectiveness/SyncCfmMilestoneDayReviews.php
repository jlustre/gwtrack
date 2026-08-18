<?php

namespace App\Jobs\CfmEffectiveness;

use App\Services\CfmEffectiveness\CfmMilestoneReviewTriggerService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncCfmMilestoneDayReviews implements ShouldQueue
{
    use Queueable;

    public function handle(CfmMilestoneReviewTriggerService $triggers): void
    {
        $triggers->syncAllActiveAssignments();
    }
}
