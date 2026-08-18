<?php

namespace App\Livewire\Goals;

use App\Models\Goal;
use App\Models\GoalCategory;
use App\Services\Goals\GoalMetricResolver;
use App\Services\Goals\GoalService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class GoalIndex extends Component
{
    public string $viewMode = 'cards';

    public string $statusFilter = 'active';

    public ?int $categoryFilter = null;

    public string $search = '';

    public ?int $editingGoalId = null;

    public string $editName = '';

    public string $editDescription = '';

    public ?int $editGoalCategoryId = null;

    public string $editStatus = 'active';

    public string $editTargetValue = '';

    public string $editActualValue = '';

    public ?string $editStartsAt = null;

    public ?string $editDeadlineAt = null;

    public bool $editHasAutomatedMetric = false;

    protected $queryString = [
        'viewMode' => ['except' => 'cards'],
        'statusFilter' => ['except' => 'active'],
    ];

    public function mount(): void
    {
        $this->authorize('viewAny', Goal::class);
    }

    public function setViewMode(string $mode): void
    {
        if (array_key_exists($mode, config('goals.views', []))) {
            $this->viewMode = $mode;
        }
    }

    public function refreshProgress(GoalMetricResolver $resolver): void
    {
        $resolver->refreshUserGoals(auth()->user());
        session()->flash('goals_status', 'Goal progress refreshed from connected modules.');
    }

    public function editGoal(int $goalId): void
    {
        $goal = $this->ownedGoal($goalId);
        $this->authorize('update', $goal);

        $this->editingGoalId = $goal->id;
        $this->editName = $goal->name;
        $this->editDescription = $goal->description ?? '';
        $this->editGoalCategoryId = $goal->goal_category_id;
        $this->editStatus = $goal->status;
        $this->editTargetValue = (string) $goal->target_value;
        $this->editActualValue = (string) $goal->actual_value;
        $this->editStartsAt = $goal->starts_at?->toDateString();
        $this->editDeadlineAt = $goal->deadline_at?->toDateString();
        $this->editHasAutomatedMetric = filled($goal->metric_key);
    }

    public function cancelEdit(): void
    {
        $this->resetEditForm();
    }

    public function saveGoal(GoalService $goalService): void
    {
        $goal = $this->ownedGoal($this->editingGoalId);
        $this->authorize('update', $goal);

        $validated = $this->validate([
            'editName' => ['required', 'string', 'min:3', 'max:255'],
            'editDescription' => ['nullable', 'string', 'max:5000'],
            'editGoalCategoryId' => ['required', 'exists:goal_categories,id'],
            'editStatus' => ['required', Rule::in(array_keys(config('goals.statuses', [])))],
            'editTargetValue' => ['required', 'numeric', 'min:0'],
            'editActualValue' => ['nullable', 'numeric', 'min:0'],
            'editStartsAt' => ['nullable', 'date'],
            'editDeadlineAt' => ['nullable', 'date', 'after_or_equal:editStartsAt'],
        ]);

        $goalService->update($goal, [
            'name' => $validated['editName'],
            'description' => $validated['editDescription'] ?: null,
            'goal_category_id' => $validated['editGoalCategoryId'],
            'status' => $validated['editStatus'],
            'target_value' => (float) $validated['editTargetValue'],
            'starts_at' => $validated['editStartsAt'] ?? null,
            'deadline_at' => $validated['editDeadlineAt'] ?? null,
            'actual_value' => ! $this->editHasAutomatedMetric ? (float) ($validated['editActualValue'] ?? 0) : null,
        ]);

        $this->resetEditForm();
        session()->flash('goals_status', "Goal \"{$validated['editName']}\" updated.");
    }

    public function deleteGoal(int $goalId, GoalService $goalService): void
    {
        $goal = $this->ownedGoal($goalId);
        $this->authorize('delete', $goal);

        $name = $goal->name;
        $goalService->delete($goal);

        if ($this->editingGoalId === $goalId) {
            $this->resetEditForm();
        }

        session()->flash('goals_status', "Goal \"{$name}\" deleted.");
    }

    public function render(): View
    {
        $query = Goal::query()
            ->where('user_id', auth()->id())
            ->with(['category', 'milestones', 'accountabilityPartner']);

        if ($this->statusFilter === 'active') {
            // "Active" means in-progress work: include off-track so newly flagged goals stay visible.
            $query->whereIn('status', ['active', 'off_track']);
        } elseif ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->categoryFilter) {
            $query->where('goal_category_id', $this->categoryFilter);
        }

        if (filled($this->search)) {
            $query->where(function ($q): void {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%');
            });
        }

        $goals = $query->orderByDesc('updated_at')->get();

        $calendarEvents = $goals->flatMap(function (Goal $goal) {
            $events = collect();

            if ($goal->deadline_at) {
                $events->push([
                    'date' => $goal->deadline_at->toDateString(),
                    'title' => $goal->name.' (deadline)',
                    'type' => 'deadline',
                    'goal_id' => $goal->id,
                    'status' => $goal->status,
                ]);
            }

            if ($goal->starts_at) {
                $events->push([
                    'date' => $goal->starts_at->toDateString(),
                    'title' => $goal->name.' (start)',
                    'type' => 'start',
                    'goal_id' => $goal->id,
                    'status' => $goal->status,
                ]);
            }

            foreach ($goal->milestones as $milestone) {
                if ($milestone->due_at) {
                    $events->push([
                        'date' => $milestone->due_at->toDateString(),
                        'title' => $milestone->name,
                        'type' => 'milestone',
                        'goal_id' => $goal->id,
                        'status' => $goal->status,
                    ]);
                }
            }

            return $events;
        })->sortBy('date')->values();

        $timelineGoals = $goals->sortBy(fn (Goal $g) => $g->starts_at ?? $g->created_at)->values();

        return view('livewire.goals.goal-index', [
            'goals' => $goals,
            'timelineGoals' => $timelineGoals,
            'calendarEvents' => $calendarEvents,
            'categories' => GoalCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'viewModes' => config('goals.views', []),
        ]);
    }

    private function ownedGoal(?int $goalId): Goal
    {
        return Goal::query()
            ->where('user_id', auth()->id())
            ->findOrFail($goalId);
    }

    private function resetEditForm(): void
    {
        $this->reset([
            'editingGoalId',
            'editName',
            'editDescription',
            'editGoalCategoryId',
            'editStatus',
            'editTargetValue',
            'editActualValue',
            'editStartsAt',
            'editDeadlineAt',
            'editHasAutomatedMetric',
        ]);
        $this->editStatus = 'active';
    }
}
