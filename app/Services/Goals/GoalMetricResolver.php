<?php

namespace App\Services\Goals;

use App\Models\Booking;
use App\Models\Goal;
use App\Models\User;
use App\Services\DashboardStatsService;
use App\Services\DownlineHierarchyService;
use App\Services\Fna\FnaAnalyticsService;
use App\Services\Prospects\ProspectAnalyticsService;
use Carbon\Carbon;

class GoalMetricResolver
{
    public function __construct(
        private readonly ProspectAnalyticsService $prospects,
        private readonly DashboardStatsService $dashboardStats,
        private readonly FnaAnalyticsService $fnaAnalytics,
        private readonly DownlineHierarchyService $hierarchy,
        private readonly GoalProductionService $production,
    ) {}

    public function resolve(User $user, string $metricKey, ?Carbon $start = null, ?Carbon $end = null): float
    {
        $metric = config("goals.metrics.{$metricKey}");

        if ($metric === null) {
            return 0;
        }

        $start = ($start ?? now()->startOfMonth())->copy();
        $end = ($end ?? now()->endOfMonth())->copy();

        return match ($metric['source'] ?? 'manual') {
            'prospects' => (float) $this->prospectMetric($user, $metricKey, $start, $end),
            'production' => (float) $this->productionMetric($user, $metricKey, $start, $end),
            'fna' => (float) $this->fnaMetric($user, $metricKey, $start, $end),
            'fap' => (float) $this->dashboardStats->apprenticeshipPercent($user),
            'licensing' => (float) $this->dashboardStats->licensingPercent($user),
            'training' => (float) $this->dashboardStats->trainingPercent($user),
            'cfm_training' => (float) $this->dashboardStats->trainingPercent($user),
            'downline' => (float) $this->downlineMetric($user, $metricKey),
            'rank' => (float) $this->dashboardStats->onboardingPercent($user),
            'calendar' => (float) $this->calendarMetric($user, $metricKey, $start, $end),
            'cfm' => (float) $user->mentorAssignments()->where('status', 'active')->count(),
            default => 0,
        };
    }

    public function refreshGoal(Goal $goal): void
    {
        if (! filled($goal->metric_key)) {
            return;
        }

        $actual = $this->resolve(
            $goal->user,
            $goal->metric_key,
            $goal->starts_at ?? now()->startOfMonth(),
            $goal->deadline_at ?? now()->endOfMonth(),
        );

        $goal->forceFill([
            'actual_value' => $actual,
            'status' => $this->resolvedStatus($goal, $actual),
        ])->save();

        $goal->progressEntries()->create([
            'recorded_at' => now(),
            'value' => $actual,
            'source' => 'automated',
            'recorded_by_user_id' => null,
        ]);
    }

    public function refreshUserGoals(User $user): void
    {
        Goal::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'off_track'])
            ->whereNotNull('metric_key')
            ->each(fn (Goal $goal) => $this->refreshGoal($goal));
    }

    private function resolvedStatus(Goal $goal, float $actual): string
    {
        if ($goal->target_value > 0 && $actual >= (float) $goal->target_value) {
            return 'completed';
        }

        $goal->actual_value = $actual;

        return $goal->isOffTrack() ? 'off_track' : 'active';
    }

    private function prospectMetric(User $user, string $metricKey, Carbon $start, Carbon $end): int
    {
        return $this->prospects->computeMetricValue($user, $metricKey, $start, $end);
    }

    private function productionMetric(User $user, string $metricKey, Carbon $start, Carbon $end): int
    {
        $months = max(1, $start->diffInMonths($end) + 1);

        return match ($metricKey) {
            'monthly_premium' => (int) round($this->production->totalForUser($user, $start, $end) / $months),
            'team_production' => (int) round($this->production->teamTotalForUser($user, $start, $end)),
            default => (int) round($this->production->totalForUser($user, $start, $end)),
        };
    }

    private function calendarMetric(User $user, string $metricKey, Carbon $start, Carbon $end): int
    {
        return match ($metricKey) {
            'mentoring_sessions' => Booking::query()
                ->whereNull('cancelled_at')
                ->whereBetween('starts_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                ->where(fn ($query) => $query
                    ->where('cfm_id', $user->id)
                    ->orWhere('trainee_id', $user->id))
                ->count(),
            default => 0,
        };
    }

    private function fnaMetric(User $user, string $metricKey, Carbon $start, Carbon $end): int
    {
        return $this->fnaAnalytics->metricCountFor($user, $metricKey, $start, $end);
    }

    private function downlineMetric(User $user, string $metricKey): int
    {
        return match ($metricKey) {
            'direct_recruits' => $this->hierarchy->directRecruitsQuery($user)->count(),
            'team_recruits' => $this->hierarchy->descendantsQuery($user)->count(),
            default => $this->hierarchy->descendantsQuery($user)->count(),
        };
    }
}
