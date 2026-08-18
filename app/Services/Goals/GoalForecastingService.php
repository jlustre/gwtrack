<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\GoalForecast;
use App\Models\User;

class GoalForecastingService
{
    public function __construct(
        private readonly GoalMetricResolver $metricResolver,
    ) {}

    /**
     * @return array{
     *     projected_value: float,
     *     projected_percent: int,
     *     pace_status: string,
     *     recommended_actions: list<string>,
     * }
     */
    public function forecastGoal(Goal $goal): array
    {
        if (filled($goal->metric_key)) {
            $this->metricResolver->refreshGoal($goal->fresh());
            $goal->refresh();
        }

        $target = (float) $goal->target_value;
        $actual = (float) $goal->actual_value;
        $progress = $goal->progressPercent();

        if (! $goal->starts_at || ! $goal->deadline_at || $goal->deadline_at->isPast()) {
            $projected = $actual;
            $projectedPercent = $progress;
        } else {
            $totalDays = max(1, $goal->starts_at->diffInDays($goal->deadline_at));
            $elapsedDays = max(1, $goal->starts_at->diffInDays(now()));
            $dailyPace = $actual / $elapsedDays;
            $remainingDays = max(0, now()->diffInDays($goal->deadline_at, false));
            $projected = $actual + ($dailyPace * $remainingDays);
            $projectedPercent = $target > 0 ? (int) min(100, round(($projected / $target) * 100)) : 0;
        }

        $paceStatus = match (true) {
            $projectedPercent >= 100 => 'on_track',
            $projectedPercent >= 80 => 'slightly_behind',
            default => 'behind',
        };

        $actions = $this->recommendedActions($goal, $projectedPercent);

        GoalForecast::query()->updateOrCreate(
            [
                'goal_id' => $goal->id,
                'forecast_date' => now()->toDateString(),
            ],
            [
                'projected_value' => round($projected, 2),
                'projected_percent' => $projectedPercent,
                'confidence' => 75,
                'pace_status' => $paceStatus,
                'recommended_actions' => $actions,
                'notes' => "At current pace: {$projectedPercent}% of target",
            ],
        );

        return [
            'projected_value' => round($projected, 2),
            'projected_percent' => $projectedPercent,
            'pace_status' => $paceStatus,
            'recommended_actions' => $actions,
        ];
    }

    /**
     * @return array<string, array{projected_percent: int, pace_status: string, goal_name: string}>
     */
    public function forecastSummaryFor(User $user): array
    {
        $summary = [];

        Goal::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'off_track'])
            ->where('goal_type', 'outcome')
            ->get()
            ->each(function (Goal $goal) use (&$summary): void {
                $forecast = $this->forecastGoal($goal);
                $summary[$goal->planning_type ?? $goal->funnel_stage_key ?? 'goal_'.$goal->id] = [
                    'goal_name' => $goal->name,
                    'projected_percent' => $forecast['projected_percent'],
                    'pace_status' => $forecast['pace_status'],
                    'recommended_actions' => $forecast['recommended_actions'],
                ];
            });

        return $summary;
    }

    /**
     * @return list<string>
     */
    private function recommendedActions(Goal $goal, int $projectedPercent): array
    {
        if ($projectedPercent >= 100) {
            return ['Maintain current activity levels to exceed your target.'];
        }

        $gap = max(0, (int) round(100 - $projectedPercent));
        $actions = ["Increase activity by approximately {$gap}% to reach your target on time."];

        if ($goal->funnel_stage_key === 'annual_income' || $goal->planning_type === 'income') {
            $actions[] = 'Schedule 2 additional FNAs this week.';
            $actions[] = 'Increase prospecting contacts by 15%.';
        }

        if ($goal->planning_type === 'recruiting') {
            $actions[] = 'Add 5 more recruiting presentations this month.';
        }

        if ($goal->isOffTrack()) {
            $actions[] = 'Review your Success Blueprint and focus on the lowest funnel stage.';
        }

        return array_slice($actions, 0, 4);
    }
}
