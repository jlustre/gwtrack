<?php

namespace App\Livewire\Goals;

use App\Models\Goal;
use App\Services\Goals\GoalAlertService;
use App\Services\Goals\GoalBlueprintService;
use App\Services\Goals\GoalForecastingService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class GoalPerformanceInsights extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', Goal::class);
    }

    public function render(
        GoalForecastingService $forecasting,
        GoalAlertService $alerts,
        GoalBlueprintService $blueprints,
    ): View {
        $user = auth()->user();

        return view('livewire.goals.goal-performance-insights', [
            'forecasts' => $forecasting->forecastSummaryFor($user),
            'alerts' => $alerts->evaluateUser($user)->take(5),
            'blueprint' => $blueprints->latestFor($user),
        ]);
    }
}
