<?php

namespace App\Livewire\Goals;

use App\Models\Goal;
use App\Services\Activities\DailyActivityService;
use App\Services\Goals\GoalActivityScorecardService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ActivityScorecard extends Component
{
    public string $periodType = DailyActivityService::PERIOD_WEEKLY;

    public function mount(): void
    {
        $this->authorize('viewAny', Goal::class);
    }

    public function updatedPeriodType(DailyActivityService $activities): void
    {
        if (! in_array($this->periodType, $activities->allowedPeriods(), true)) {
            $this->periodType = DailyActivityService::PERIOD_WEEKLY;
        }
    }

    public function render(GoalActivityScorecardService $scorecard): View
    {
        return view('livewire.goals.activity-scorecard', [
            'scorecard' => $scorecard->scorecardFor(auth()->user(), $this->periodType),
            'periods' => [
                DailyActivityService::PERIOD_DAILY => 'Daily',
                DailyActivityService::PERIOD_WEEKLY => 'Weekly',
                DailyActivityService::PERIOD_MONTHLY => 'Monthly',
            ],
        ]);
    }
}
