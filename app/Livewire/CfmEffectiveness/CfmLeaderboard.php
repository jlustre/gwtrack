<?php

namespace App\Livewire\CfmEffectiveness;

use App\Services\CfmEffectiveness\CfmEffectivenessDashboardService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class CfmLeaderboard extends Component
{
    #[Url]
    public string $metric = 'overall_effectiveness';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('view CFM effectiveness'), 403);
    }

    public function render(CfmEffectivenessDashboardService $dashboard): View
    {
        return view('livewire.cfm-effectiveness.leaderboard', [
            'metrics' => config('cfm-effectiveness.leaderboard_metrics', []),
            'entries' => $dashboard->leaderboardFor($this->metric)->take(20)->values(),
        ]);
    }
}
