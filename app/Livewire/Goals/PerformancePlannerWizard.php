<?php

namespace App\Livewire\Goals;

use App\Models\Goal;
use App\Services\Goals\GoalFunnelEngine;
use App\Services\Goals\GoalPlanningService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PerformancePlannerWizard extends Component
{
    public int $step = 1;

    public string $planningType = 'income';

    public string $targetValue = '';

    public string $targetRank = 'SM';

    public string $planName = '';

    public ?string $deadlineAt = null;

    /** @var list<array<string, mixed>> */
    public array $previewFunnel = [];

    public function mount(): void
    {
        $this->authorize('create', Goal::class);
        $this->deadlineAt = now()->endOfYear()->toDateString();
        $this->planName = now()->year.' Performance Plan';
    }

    public function selectPlanningType(string $type): void
    {
        if (! array_key_exists($type, config('goals-planning.planning_types', []))) {
            return;
        }

        $this->planningType = $type;
        $this->previewFunnel = [];
        $this->step = 1;
    }

    public function goToStep(int $step): void
    {
        $this->step = max(1, min(2, $step));
    }

    public function calculateFunnel(GoalFunnelEngine $engine): void
    {
        $this->validate([
            'planningType' => ['required', 'in:'.implode(',', array_keys(config('goals-planning.planning_types', [])))],
            'targetValue' => ['required', 'numeric', 'min:1'],
        ]);

        $this->previewFunnel = $engine->buildFunnel(
            auth()->user(),
            $this->planningType,
            (float) $this->targetValue,
            $this->planningType === 'rank' ? $this->targetRank : null,
        );

        $this->step = 2;
    }

    public function createPlan(GoalPlanningService $planning): void
    {
        $this->validate([
            'planningType' => ['required'],
            'targetValue' => ['required', 'numeric', 'min:1'],
            'planName' => ['required', 'string', 'min:3'],
            'deadlineAt' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $blueprint = $planning->createBlueprint(auth()->user(), $this->planningType, (float) $this->targetValue, [
            'name' => $this->planName,
            'deadline_at' => $this->deadlineAt,
            'target_rank' => $this->planningType === 'rank' ? $this->targetRank : null,
        ]);

        session()->flash('goals_status', "Performance plan \"{$blueprint->name}\" created with ".count($blueprint->goals).' linked goals.');

        $this->redirect(route('goals.blueprint.show', $blueprint), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.goals.performance-planner-wizard', [
            'planningTypes' => config('goals-planning.planning_types', []),
            'rankOptions' => array_keys(config('goals-planning.rank_requirements', [])),
            'fieldHelp' => config('goals-planning.wizard_fields', []),
        ]);
    }
}
