<?php

namespace App\Livewire\Prospects;

use App\Models\ProspectGoal;
use App\Services\Prospects\ProspectAnalyticsService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProspectGoalsPanel extends Component
{
    public string $periodType = 'monthly';

    public bool $compact = false;

    public bool $showForm = false;

    public string $metricKey = 'contacts';

    public int $targetValue = 10;

    public ?int $editingGoalId = null;

    public function mount(bool $compact = false): void
    {
        $this->compact = $compact;
        $this->authorize('viewAny', \App\Models\Prospect::class);
    }

    public function updatedPeriodType(): void
    {
        $this->resetForm();
    }

    public function openCreateForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editGoal(int $goalId): void
    {
        $goal = ProspectGoal::query()->where('user_id', auth()->id())->findOrFail($goalId);

        $this->editingGoalId = $goal->id;
        $this->metricKey = $goal->metric_key;
        $this->targetValue = $goal->target_value;
        $this->showForm = true;
    }

    public function saveGoal(ProspectAnalyticsService $analytics): void
    {
        $this->authorize('viewAny', \App\Models\Prospect::class);

        $validated = $this->validate([
            'periodType' => ['required', Rule::in(['weekly', 'monthly'])],
            'metricKey' => ['required', Rule::in(array_keys(config('prospects.goal_metrics', [])))],
            'targetValue' => ['required', 'integer', 'min:1', 'max:9999'],
        ]);

        $bounds = $analytics->periodBounds($validated['periodType']);
        $user = auth()->user();

        if ($this->editingGoalId !== null) {
            $goal = ProspectGoal::query()
                ->where('user_id', $user->id)
                ->findOrFail($this->editingGoalId);

            $goal->update([
                'metric_key' => $validated['metricKey'],
                'target_value' => $validated['targetValue'],
                'period_type' => $validated['periodType'],
                'period_start' => $bounds['start']->toDateString(),
                'period_end' => $bounds['end']->toDateString(),
            ]);
        } else {
            $goal = ProspectGoal::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'period_type' => $validated['periodType'],
                    'period_start' => $bounds['start']->toDateString(),
                    'metric_key' => $validated['metricKey'],
                ],
                [
                    'period_end' => $bounds['end']->toDateString(),
                    'target_value' => $validated['targetValue'],
                ],
            );
        }

        $analytics->refreshGoalActuals($user, $goal);

        $this->resetForm();
        $this->dispatch('goal-saved');
    }

    public function deleteGoal(int $goalId): void
    {
        ProspectGoal::query()
            ->where('user_id', auth()->id())
            ->whereKey($goalId)
            ->delete();

        if ($this->editingGoalId === $goalId) {
            $this->resetForm();
        }
    }

    public function resetForm(): void
    {
        $this->showForm = false;
        $this->editingGoalId = null;
        $this->metricKey = 'contacts';
        $this->targetValue = 10;
    }

    public function render(ProspectAnalyticsService $analytics): View
    {
        $bounds = $analytics->periodBounds($this->periodType);
        $user = auth()->user();

        $goals = ProspectGoal::query()
            ->where('user_id', $user->id)
            ->where('period_type', $this->periodType)
            ->whereDate('period_start', $bounds['start']->toDateString())
            ->orderBy('metric_key')
            ->get();

        if ($goals->isNotEmpty()) {
            $analytics->refreshGoalActuals($user);
            $goals = ProspectGoal::query()
                ->where('user_id', $user->id)
                ->where('period_type', $this->periodType)
                ->whereDate('period_start', $bounds['start']->toDateString())
                ->orderBy('metric_key')
                ->get();
        }

        return view('livewire.prospects.prospect-goals-panel', [
            'goals' => $goals,
            'metricLabels' => config('prospects.goal_metrics', []),
            'periodLabel' => $bounds['start']->format('M j').' – '.$bounds['end']->format('M j, Y'),
        ]);
    }
}
