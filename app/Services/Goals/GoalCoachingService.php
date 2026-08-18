<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\User;
use Illuminate\Support\Collection;

class GoalCoachingService
{
    public function __construct(
        private readonly GoalMetricResolver $metricResolver,
    ) {}

    /**
     * @return list<string>
     */
    public function suggestionsFor(User $user): array
    {
        if (! config('goals.ai_coaching.enabled', true)) {
            return [];
        }

        $suggestions = [];
        $threshold = (int) config('goals.ai_coaching.thresholds.behind_percent', 80);
        $criticalDays = (int) config('goals.ai_coaching.thresholds.critical_days_remaining', 7);

        Goal::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'off_track'])
            ->with('category')
            ->get()
            ->each(function (Goal $goal) use (&$suggestions, $threshold, $criticalDays): void {
                $progress = $goal->progressPercent();

                if ($goal->target_value > 0 && $progress < $threshold) {
                    $gap = max(0, (float) $goal->target_value - (float) $goal->actual_value);
                    $suggestions[] = "You are behind on \"{$goal->name}\" — about {$gap} remaining toward your target.";
                }

                if ($goal->deadline_at && now()->diffInDays($goal->deadline_at, false) <= $criticalDays && $progress < 100) {
                    $suggestions[] = "\"{$goal->name}\" deadline is approaching. Schedule a focused work block this week.";
                }

                if ($goal->status === 'off_track') {
                    $suggestions[] = "Goal \"{$goal->name}\" is off track. Review milestones or ask your accountability partner for coaching.";
                }
            });

        if ($suggestions === []) {
            $recruitingGoals = Goal::query()
                ->where('user_id', $user->id)
                ->whereHas('category', fn ($q) => $q->where('slug', 'recruiting'))
                ->where('status', 'active')
                ->count();

            if ($recruitingGoals === 0) {
                $suggestions[] = 'Set a recruiting goal to align daily prospecting with your team growth targets.';
            }
        }

        return array_slice($suggestions, 0, 5);
    }

    /**
     * @return Collection<int, Goal>
     */
    public function traineeGoalsFor(User $coach): Collection
    {
        $traineeIds = $coach->mentorAssignments()
            ->where('status', 'active')
            ->pluck('apprentice_id');

        if ($traineeIds->isEmpty()) {
            return collect();
        }

        return Goal::query()
            ->whereIn('user_id', $traineeIds)
            ->with(['user', 'category', 'milestones'])
            ->orderByDesc('updated_at')
            ->get();
    }
}
