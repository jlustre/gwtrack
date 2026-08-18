<?php

namespace App\Services\Goals;

use App\Models\User;
use App\Services\Activities\DailyActivityService;

class GoalActivityScorecardService
{
    public function __construct(
        private readonly DailyActivityService $dailyActivities,
    ) {}

    /**
     * @return array{
     *     period_type: string,
     *     period_label: string,
     *     activities: list<array{key: string, label: string, target: float, actual: float, percent: int, format?: string}>,
     *     overall_score: int,
     * }
     */
    public function scorecardFor(User $user, string $periodType = 'weekly'): array
    {
        if (! in_array($periodType, $this->dailyActivities->allowedPeriods(), true)) {
            $periodType = DailyActivityService::PERIOD_WEEKLY;
        }

        $dashboard = $this->dailyActivities->dashboardFor($user, $periodType);

        $activities = collect($dashboard['chart'])
            ->map(fn (array $row): array => [
                'key' => $row['key'],
                'label' => $row['label'],
                'target' => (float) $row['target'],
                'actual' => (float) $row['actual'],
                'percent' => (int) $row['percent'],
                'format' => $row['format'] ?? 'number',
                'description' => $row['description'] ?? null,
                'examples' => $row['examples'] ?? [],
            ])
            ->values()
            ->all();

        return [
            'period_type' => $dashboard['period_type'],
            'period_label' => $dashboard['period_label'],
            'activities' => $activities,
            'overall_score' => $dashboard['overall_score'],
        ];
    }
}
