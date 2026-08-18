<?php

namespace App\Livewire\Goals;

use App\Models\Goal;
use App\Services\Goals\GoalDashboardService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class GoalDashboard extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Goal::class);
    }

    public function render(GoalDashboardService $dashboard): View
    {
        $summary = $dashboard->summaryFor(auth()->user());
        $trendMax = max(1, collect($summary['monthly_trend'])->max(fn (array $row) => max($row['completed'], $row['created'])) ?? 1);

        return view('livewire.goals.goal-dashboard', [
            'summary' => $summary,
            'trendMax' => $trendMax,
        ]);
    }
}
