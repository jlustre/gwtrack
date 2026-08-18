<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\GoalActivityLog;
use App\Models\User;

class GoalBridgeService
{
    public function __construct(
        private readonly GoalMetricResolver $metricResolver,
        private readonly GoalAchievementService $achievements,
    ) {}

    /**
     * @param  list<string>  $metricKeys
     */
    public function syncMetrics(User $user, array $metricKeys): void
    {
        if ($metricKeys === []) {
            return;
        }

        Goal::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'off_track'])
            ->whereIn('metric_key', $metricKeys)
            ->each(fn (Goal $goal) => $this->metricResolver->refreshGoal($goal));

        $this->achievements->evaluateForUser($user);
    }

    public function logActivity(
        User $user,
        string $activityKey,
        float $value = 1,
        ?string $notes = null,
        string $source = 'bridge',
    ): void {
        GoalActivityLog::query()->create([
            'user_id' => $user->id,
            'goal_id' => $this->matchingGoalId($user, $activityKey),
            'activity_key' => $activityKey,
            'value' => $value,
            'logged_for_date' => now()->toDateString(),
            'source' => $source,
            'notes' => $notes,
        ]);
    }

    private function matchingGoalId(User $user, string $activityKey): ?int
    {
        return Goal::query()
            ->where('user_id', $user->id)
            ->where('funnel_stage_key', $activityKey)
            ->whereIn('status', ['active', 'off_track'])
            ->value('id');
    }
}
