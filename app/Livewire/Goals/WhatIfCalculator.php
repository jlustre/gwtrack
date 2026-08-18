<?php

namespace App\Livewire\Goals;

use App\Models\Goal;
use App\Services\Goals\GoalWhatIfService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class WhatIfCalculator extends Component
{
    public string $planningType = 'income';

    public string $targetValue = '100000';

    public string $targetRank = 'SM';

    /** @var array{funnel: list<array<string, mixed>>, summary: array<string, float>}|null */
    public ?array $results = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Goal::class);
    }

    public function calculate(GoalWhatIfService $whatIf): void
    {
        $this->validate([
            'planningType' => ['required'],
            'targetValue' => ['required', 'numeric', 'min:1'],
        ]);

        $this->results = $whatIf->simulate(auth()->user(), [
            'planning_type' => $this->planningType,
            'target_value' => (float) $this->targetValue,
            'target_rank' => $this->targetRank,
        ], persist: true);
    }

    public function render(): View
    {
        return view('livewire.goals.what-if-calculator', [
            'planningTypes' => config('goals-planning.planning_types', []),
            'rankOptions' => array_keys(config('goals-planning.rank_requirements', [])),
        ]);
    }
}
