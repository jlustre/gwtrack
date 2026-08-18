<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\GoalAlert;
use App\Models\GoalRecommendation;
use App\Models\User;
use Illuminate\Support\Collection;

class GoalAlertService
{
    public function __construct(
        private readonly GoalMetricResolver $metricResolver,
        private readonly GoalForecastingService $forecasting,
    ) {}

    public function evaluateUser(User $user): Collection
    {
        $alerts = collect();
        $rules = config('goals-planning.alert_rules', []);

        $this->checkActivityGap($user, 'contacts', (int) ($rules['no_prospecting_days'] ?? 7), 'no_prospecting', 'No prospecting activity', $alerts);
        $this->checkActivityGap($user, 'presentations', (int) ($rules['no_presentations_days'] ?? 14), 'no_presentations', 'No presentations completed', $alerts);
        $this->checkActivityGap($user, 'fna_completed', (int) ($rules['no_fna_days'] ?? 14), 'no_fna', 'No FNA activity', $alerts);
        $this->checkActivityGap($user, 'followups_completed', (int) ($rules['no_followups_days'] ?? 7), 'no_followups', 'No follow-ups completed', $alerts);

        Goal::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'off_track'])
            ->get()
            ->each(function (Goal $goal) use ($user, $alerts, $rules): void {
                $forecast = $this->forecasting->forecastGoal($goal);
                $threshold = (int) ($rules['pace_behind_percent'] ?? 80);

                if ($forecast['projected_percent'] < $threshold) {
                    $alerts->push($this->persistAlert($user, $goal, 'pace_behind', 'warning', 'Goal behind pace', "At current pace you will finish {$forecast['projected_percent']}% of \"{$goal->name}\"."));
                    $this->persistRecommendation($user, $goal, 'pace_correction', $forecast['recommended_actions'][0] ?? 'Increase weekly activity.');
                }

                if ($goal->status === 'off_track' || $goal->isOffTrack()) {
                    $alerts->push($this->persistAlert($user, $goal, 'off_track', 'critical', 'Goal off track', "\"{$goal->name}\" is off track. Review your activity funnel."));
                }

                if ($goal->deadline_at && $goal->deadline_at->lte(now()->addDays(7)) && $goal->progressPercent() < 100) {
                    $alerts->push($this->persistAlert($user, $goal, 'deadline_approaching', 'warning', 'Deadline approaching', "\"{$goal->name}\" deadline is within 7 days."));
                }
            });

        return $alerts->unique('id')->values();
    }

    private function checkActivityGap(User $user, string $metricKey, int $days, string $type, string $title, Collection $alerts): void
    {
        $recent = $this->metricResolver->resolve($user, $metricKey, now()->subDays($days), now());

        if ($recent > 0) {
            return;
        }

        $alerts->push($this->persistAlert($user, null, $type, 'warning', $title, "No {$metricKey} activity recorded in the last {$days} days."));
    }

    private function persistAlert(User $user, ?Goal $goal, string $type, string $severity, string $title, string $message): GoalAlert
    {
        return GoalAlert::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'goal_id' => $goal?->id,
                'alert_type' => $type,
                'resolved_at' => null,
            ],
            [
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
                'triggered_at' => now(),
            ],
        );
    }

    private function persistRecommendation(User $user, Goal $goal, string $type, string $message): void
    {
        GoalRecommendation::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'goal_id' => $goal->id,
                'recommendation_type' => $type,
                'dismissed_at' => null,
            ],
            [
                'priority' => 'high',
                'message' => $message,
            ],
        );
    }
}
