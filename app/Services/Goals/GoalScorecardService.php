<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\GoalCategory;
use App\Models\GoalScorecard;
use App\Models\User;
use Carbon\Carbon;

class GoalScorecardService
{
    public function generateForUser(User $user, string $periodType, Carbon $start, Carbon $end): GoalScorecard
    {
        $goals = Goal::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed', 'off_track'])
            ->where(function ($query) use ($start, $end): void {
                $query->whereBetween('starts_at', [$start, $end])
                    ->orWhereBetween('deadline_at', [$start, $end])
                    ->orWhere(fn ($q) => $q->where('starts_at', '<=', $start)->where('deadline_at', '>=', $end));
            })
            ->with('category')
            ->get();

        $scores = GoalCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(function (GoalCategory $category) use ($goals): array {
                $categoryGoals = $goals->where('goal_category_id', $category->id);

                if ($categoryGoals->isEmpty()) {
                    return [$category->slug => null];
                }

                $avg = (int) round($categoryGoals->avg(fn (Goal $g) => $g->progressPercent()));

                return [$category->slug => [
                    'name' => $category->name,
                    'goal_count' => $categoryGoals->count(),
                    'score' => $avg,
                ]];
            })
            ->filter()
            ->all();

        $overall = $goals->isEmpty()
            ? 0
            : (int) round($goals->avg(fn (Goal $g) => $g->progressPercent()));

        return GoalScorecard::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'period_type' => $periodType,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
            ],
            [
                'scores' => $scores,
                'overall_score' => $overall,
                'generated_at' => now(),
            ],
        );
    }

    public function generateWeeklyForAllUsers(): void
    {
        $start = now()->subWeek()->startOfWeek();
        $end = now()->subWeek()->endOfWeek();

        User::query()->where('is_active', true)->select('id')->chunkById(100, function ($users) use ($start, $end): void {
            foreach ($users as $user) {
                $this->generateForUser($user, 'weekly', $start, $end);
            }
        });
    }
}
