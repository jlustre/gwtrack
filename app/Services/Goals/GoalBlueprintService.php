<?php

namespace App\Services\Goals;

use App\Models\GoalBlueprint;
use App\Models\User;

class GoalBlueprintService
{
    public function __construct(
        private readonly GoalForecastingService $forecasting,
        private readonly GoalMetricResolver $metricResolver,
    ) {}

    /**
     * @return array{
     *     blueprint: GoalBlueprint,
     *     stages: list<array<string, mixed>>,
     *     forecasts: array<string, mixed>,
     * }
     */
    public function blueprintView(GoalBlueprint $blueprint): array
    {
        $blueprint->load(['goals' => fn ($q) => $q->with(['category', 'activityTargets', 'childDependencies.childGoal'])]);

        foreach ($blueprint->goals as $goal) {
            if (filled($goal->metric_key)) {
                $this->metricResolver->refreshGoal($goal);
            }
        }

        $blueprint->refresh();
        $blueprint->load('goals');

        $stages = collect($blueprint->funnel_snapshot ?? [])
            ->map(function (array $stage) use ($blueprint): array {
                $goal = $blueprint->goals->firstWhere('funnel_stage_key', $stage['key']);
                $progress = $goal?->progressPercent() ?? 0;
                $forecast = $goal ? $this->forecasting->forecastGoal($goal) : null;

                return [
                    ...$stage,
                    'goal_id' => $goal?->id,
                    'actual_value' => (float) ($goal?->actual_value ?? 0),
                    'target_value' => (float) ($goal?->target_value ?? $stage['annual_target'] ?? 0),
                    'progress_percent' => $progress,
                    'status' => $goal?->status ?? 'planned',
                    'projected_percent' => $forecast['projected_percent'] ?? 0,
                    'pace_status' => $forecast['pace_status'] ?? 'unknown',
                ];
            })
            ->values()
            ->all();

        $rootGoal = $blueprint->rootGoal;
        $forecasts = $rootGoal ? $this->forecasting->forecastGoal($rootGoal) : [];

        return [
            'blueprint' => $blueprint,
            'stages' => $stages,
            'forecasts' => $forecasts,
        ];
    }

    public function latestFor(User $user): ?GoalBlueprint
    {
        return GoalBlueprint::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->latest()
            ->first();
    }
}
