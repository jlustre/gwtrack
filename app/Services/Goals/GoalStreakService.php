<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\User;
use Carbon\Carbon;

class GoalStreakService
{
    public function refreshForUser(User $user): void
    {
        Goal::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'off_track', 'completed'])
            ->each(fn (Goal $goal) => $this->refreshGoalStreak($goal));
    }

    public function refreshGoalStreak(Goal $goal): void
    {
        $entries = $goal->progressEntries()
            ->orderByDesc('recorded_at')
            ->limit(60)
            ->get(['recorded_at', 'value']);

        if ($entries->isEmpty()) {
            return;
        }

        $streak = 0;
        $best = (int) $goal->streak_days;
        $previousDate = null;
        $previousValue = null;

        foreach ($entries as $entry) {
            $date = Carbon::parse($entry->recorded_at)->toDateString();
            $value = (float) $entry->value;

            if ($previousDate === null) {
                if ($value > 0) {
                    $streak = 1;
                }
            } elseif ($date === $previousDate) {
                if ($value > ($previousValue ?? 0)) {
                    $streak = max(1, $streak);
                }
            } elseif (Carbon::parse($date)->addDay()->toDateString() === $previousDate) {
                if ($value >= ($previousValue ?? 0)) {
                    $streak++;
                } else {
                    break;
                }
            } else {
                break;
            }

            $previousDate = $date;
            $previousValue = $value;
            $best = max($best, $streak);
        }

        $goal->update([
            'current_streak' => $streak,
            'streak_days' => $best,
        ]);
    }
}
