<?php

namespace App\Livewire\Goals;

use App\Models\Goal;
use App\Models\GoalCategory;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use App\Services\Goals\GoalCoachingService;
use App\Services\Goals\GoalTeamService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class TeamGoalsPanel extends Component
{
    public string $scope = 'downline';

    public ?int $memberFilter = null;

    public ?int $categoryFilter = null;

    public string $statusFilter = 'active';

    public string $search = '';

    public string $viewMode = 'goals';

    public ?int $expandedGoalId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Goal::class);
        abort_unless(auth()->user()->can('view team goals'), 403);
    }

    public function setViewMode(string $mode): void
    {
        if (array_key_exists($mode, $this->viewModes())) {
            $this->viewMode = $mode;
            $this->expandedGoalId = null;
        }
    }

    public function toggleGoal(int $goalId): void
    {
        $this->expandedGoalId = $this->expandedGoalId === $goalId ? null : $goalId;
    }

    public function selectMember(?int $memberId): void
    {
        $this->memberFilter = $memberId;
        $this->viewMode = 'goals';
        $this->expandedGoalId = null;
    }

    /**
     * @return array<string, string>
     */
    private function viewModes(): array
    {
        return [
            'goals' => 'All goals',
            'members' => 'By member',
            'off_track' => 'Needs attention',
        ];
    }

    public function render(
        DownlineHierarchyService $hierarchy,
        GoalCoachingService $coaching,
        GoalTeamService $teamService,
    ): View {
        $viewer = auth()->user();
        $members = $this->scopedMembersQuery($hierarchy, $viewer)->orderBy('name')->get(['id', 'name']);
        $memberIds = $members->pluck('id');

        $query = Goal::query()
            ->whereIn('user_id', $memberIds)
            ->with(['user.rank', 'category', 'milestones', 'accountabilityPartner']);

        if ($this->memberFilter) {
            $query->where('user_id', $this->memberFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->categoryFilter) {
            $query->where('goal_category_id', $this->categoryFilter);
        }

        if (filled($this->search)) {
            $query->where(function (Builder $builder): void {
                $builder->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('description', 'like', '%'.$this->search.'%')
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%'.$this->search.'%'));
            });
        }

        $allGoals = (clone $query)->orderByDesc('updated_at')->limit(200)->get();
        $goals = $this->viewMode === 'off_track'
            ? $teamService->offTrackGoals($allGoals)
            : $allGoals;

        return view('livewire.goals.team-goals-panel', [
            'goals' => $goals,
            'allGoals' => $allGoals,
            'members' => $members,
            'categories' => GoalCategory::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'summary' => $teamService->summaryFor($allGoals),
            'memberRollup' => $teamService->memberRollup($allGoals),
            'offTrackGoals' => $teamService->offTrackGoals($allGoals),
            'traineeGoals' => $viewer->can('coach goals') ? $coaching->traineeGoalsFor($viewer) : collect(),
            'viewModes' => $this->viewModes(),
            'canCoach' => $viewer->can('coach goals'),
        ]);
    }

    private function scopedMembersQuery(DownlineHierarchyService $hierarchy, User $viewer): Builder
    {
        return match ($this->scope) {
            'personal' => User::query()->whereKey($viewer->id),
            'directs' => $hierarchy->directRecruitsQuery($viewer),
            default => $hierarchy->dashboardMembersQuery($viewer),
        };
    }
}
