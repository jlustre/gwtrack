<?php

namespace App\Jobs\Goals;

use App\Models\User;
use App\Services\Goals\GoalAchievementService;
use App\Services\Goals\GoalHierarchyRollupService;
use App\Services\Goals\GoalMetricResolver;
use App\Services\Goals\GoalReminderService;
use App\Services\Goals\GoalStreakService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RollupGoalProgress implements ShouldQueue
{
    use Queueable;

    public function handle(
        GoalMetricResolver $resolver,
        GoalHierarchyRollupService $rollup,
        GoalStreakService $streaks,
        GoalAchievementService $achievements,
        GoalReminderService $reminders,
    ): void {
        User::query()
            ->where('is_active', true)
            ->select('id')
            ->chunkById(100, function ($users) use ($resolver, $rollup, $streaks, $achievements, $reminders): void {
                foreach ($users as $user) {
                    $resolver->refreshUserGoals($user);
                    $rollup->rollupForUser($user);
                    $streaks->refreshForUser($user);
                    $achievements->evaluateForUser($user);
                    $reminders->notifyOffTrackGoals($user);
                }
            });
    }
}
