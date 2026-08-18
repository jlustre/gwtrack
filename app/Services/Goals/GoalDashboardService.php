<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\GoalAchievement;
use App\Models\GoalCategory;
use App\Models\User;
use Illuminate\Support\Collection;

class GoalDashboardService
{
    public function __construct(
        private readonly GoalCoachingService $coaching,
    ) {}

    /**
     * @return array{
     *     total: int,
     *     active: int,
     *     completed: int,
     *     off_track: int,
     *     completion_percent: int,
     *     current_streak: int,
     *     by_category: list<array{slug: string, name: string, count: int, avg_progress: int}>,
     *     monthly_trend: list<array{month: string, completed: int, created: int}>,
     *     recent_goals: Collection<int, Goal>,
     *     achievements: Collection<int, GoalAchievement>,
     *     ai_suggestions: list<string>,
     * }
     */
    public function summaryFor(User $user): array
    {
        $goals = Goal::query()
            ->where('user_id', $user->id)
            ->with('category')
            ->get();

        $active = $goals->whereIn('status', ['active', 'off_track']);
        $completed = $goals->where('status', 'completed');
        $offTrack = $goals->where('status', 'off_track');

        $completionPercent = $goals->isEmpty()
            ? 0
            : (int) round($goals->avg(fn (Goal $goal) => $goal->progressPercent()));

        $byCategory = GoalCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function (GoalCategory $category) use ($goals): array {
                $categoryGoals = $goals->where('goal_category_id', $category->id);

                return [
                    'slug' => $category->slug,
                    'name' => $category->name,
                    'count' => $categoryGoals->count(),
                    'avg_progress' => $categoryGoals->isEmpty()
                        ? 0
                        : (int) round($categoryGoals->avg(fn (Goal $goal) => $goal->progressPercent())),
                ];
            })
            ->values()
            ->all();

        $monthlyTrend = collect(range(5, 0))
            ->map(function (int $monthsAgo) use ($user): array {
                $month = now()->subMonths($monthsAgo);

                return [
                    'month' => $month->format('M'),
                    'completed' => Goal::query()
                        ->where('user_id', $user->id)
                        ->where('status', 'completed')
                        ->whereYear('completed_at', $month->year)
                        ->whereMonth('completed_at', $month->month)
                        ->count(),
                    'created' => Goal::query()
                        ->where('user_id', $user->id)
                        ->whereYear('created_at', $month->year)
                        ->whereMonth('created_at', $month->month)
                        ->count(),
                ];
            })
            ->all();

        return [
            'total' => $goals->count(),
            'active' => $active->count(),
            'completed' => $completed->count(),
            'off_track' => $offTrack->count(),
            'completion_percent' => $completionPercent,
            'current_streak' => (int) $goals->max('current_streak'),
            'by_category' => $byCategory,
            'monthly_trend' => $monthlyTrend,
            'recent_goals' => $goals->sortByDesc('updated_at')->take(6)->values(),
            'achievements' => GoalAchievement::query()
                ->where('user_id', $user->id)
                ->with('badge')
                ->latest('earned_at')
                ->limit(5)
                ->get(),
            'ai_suggestions' => $this->coaching->suggestionsFor($user),
        ];
    }
}
