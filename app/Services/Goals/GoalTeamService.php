<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Support\Collection;

class GoalTeamService
{
    /**
     * @param  Collection<int, Goal>  $goals
     * @return array{
     *     member_count: int,
     *     total_goals: int,
     *     active: int,
     *     completed: int,
     *     off_track: int,
     *     avg_progress: int,
     *     due_this_week: int,
     * }
     */
    public function summaryFor(Collection $goals): array
    {
        $activeGoals = $goals->whereIn('status', ['active', 'off_track']);

        return [
            'member_count' => $goals->pluck('user_id')->unique()->count(),
            'total_goals' => $goals->count(),
            'active' => $activeGoals->count(),
            'completed' => $goals->where('status', 'completed')->count(),
            'off_track' => $goals->where('status', 'off_track')->count(),
            'avg_progress' => $goals->isEmpty()
                ? 0
                : (int) round($goals->avg(fn (Goal $goal) => $goal->progressPercent())),
            'due_this_week' => $goals
                ->filter(fn (Goal $goal) => $goal->deadline_at
                    && $goal->deadline_at->between(now()->startOfDay(), now()->addDays(7)->endOfDay())
                    && $goal->status !== 'completed')
                ->count(),
        ];
    }

    /**
     * @param  Collection<int, Goal>  $goals
     * @return Collection<int, array{
     *     user: User,
     *     goal_count: int,
     *     active_count: int,
     *     off_track_count: int,
     *     avg_progress: int,
     *     goals: Collection<int, Goal>,
     * }>
     */
    public function memberRollup(Collection $goals): Collection
    {
        return $goals
            ->groupBy('user_id')
            ->map(function (Collection $memberGoals): array {
                /** @var User|null $user */
                $user = $memberGoals->first()?->user;

                return [
                    'user' => $user,
                    'goal_count' => $memberGoals->count(),
                    'active_count' => $memberGoals->whereIn('status', ['active', 'off_track'])->count(),
                    'off_track_count' => $memberGoals->where('status', 'off_track')->count(),
                    'avg_progress' => $memberGoals->isEmpty()
                        ? 0
                        : (int) round($memberGoals->avg(fn (Goal $goal) => $goal->progressPercent())),
                    'goals' => $memberGoals->sortByDesc('updated_at')->values(),
                ];
            })
            ->filter(fn (array $row) => $row['user'] !== null)
            ->sortByDesc(fn (array $row) => ($row['off_track_count'] * 1000) + $row['goal_count'])
            ->values();
    }

    /**
     * @param  Collection<int, Goal>  $goals
     * @return Collection<int, Goal>
     */
    public function offTrackGoals(Collection $goals): Collection
    {
        return $goals
            ->filter(fn (Goal $goal) => $goal->status === 'off_track' || $goal->isOffTrack())
            ->sortBy('deadline_at')
            ->values();
    }
}
