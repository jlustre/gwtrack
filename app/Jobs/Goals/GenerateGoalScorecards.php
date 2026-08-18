<?php

namespace App\Jobs\Goals;

use App\Services\Goals\GoalScorecardService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateGoalScorecards implements ShouldQueue
{
    use Queueable;

    public function handle(GoalScorecardService $scorecards): void
    {
        $scorecards->generateWeeklyForAllUsers();
    }
}
