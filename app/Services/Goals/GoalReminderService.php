<?php

namespace App\Services\Goals;

use App\Models\Goal;
use App\Models\GoalReminder;
use App\Models\User;
use App\Services\Notifications\NotificationOrchestrator;

class GoalReminderService
{
    public function __construct(
        private readonly NotificationOrchestrator $notifications,
    ) {}

    public function processDueReminders(): void
    {
        GoalReminder::query()
            ->where('is_active', true)
            ->whereNull('sent_at')
            ->where('remind_at', '<=', now())
            ->with(['goal.coaches', 'user'])
            ->each(function (GoalReminder $reminder): void {
                $goal = $reminder->goal;

                if (! $goal) {
                    $reminder->update(['is_active' => false]);

                    return;
                }

                if ($reminder->user) {
                    $this->dispatchGoalReminder($goal, $reminder->user, $reminder->message, forCoach: false);
                }

                $coachIds = $goal->coaches()
                    ->where('receives_alerts', true)
                    ->pluck('coach_user_id');

                if ($coachIds->isNotEmpty()) {
                    User::query()->whereIn('id', $coachIds)->get()->each(
                        fn (User $coach) => $this->dispatchGoalReminder($goal, $coach, $reminder->message, forCoach: true),
                    );
                }

                $reminder->update(['sent_at' => now()]);

                if (str_contains($reminder->message, 'Weekly check-in')) {
                    GoalReminder::query()->create([
                        'goal_id' => $goal->id,
                        'user_id' => $goal->user_id,
                        'remind_at' => now()->addWeek(),
                        'channel' => $reminder->channel,
                        'message' => $reminder->message,
                        'is_active' => true,
                    ]);
                }
            });
    }

    public function notifyOffTrackGoals(User $user): void
    {
        Goal::query()
            ->where('user_id', $user->id)
            ->where('status', 'off_track')
            ->each(function (Goal $goal) use ($user): void {
                $this->dispatchGoalOffTrack($goal, $user, forCoach: false);

                $coachIds = $goal->coaches()->where('receives_alerts', true)->pluck('coach_user_id');

                if ($coachIds->isNotEmpty()) {
                    User::query()->whereIn('id', $coachIds)->get()->each(
                        fn (User $coach) => $this->dispatchGoalOffTrack($goal, $coach, forCoach: true),
                    );
                }
            });
    }

    private function dispatchGoalReminder(Goal $goal, User $recipient, string $message, bool $forCoach): void
    {
        $this->notifications->dispatch('goal_reminder', [
            'queue' => true,
            'recipients' => [$recipient->id],
            'module' => 'goal',
            'priority' => 'medium',
            'related' => ['type' => Goal::class, 'id' => $goal->id],
            'related_user_id' => $goal->user_id,
            'title' => $forCoach ? 'Trainee goal check-in' : 'Goal reminder',
            'message' => $forCoach
                ? "{$goal->user?->name}: {$message}"
                : $message,
            'action_link' => [
                'route' => $forCoach ? 'goals.coaching' : 'goals.index',
                'params' => [],
                'label' => $forCoach ? 'Open coaching' : 'View goals',
            ],
            'payload' => [
                'goal_id' => $goal->id,
                'goal_name' => $goal->name,
                'for_coach' => $forCoach,
            ],
        ]);
    }

    private function dispatchGoalOffTrack(Goal $goal, User $recipient, bool $forCoach): void
    {
        $this->notifications->dispatch('goal_off_track', [
            'queue' => true,
            'recipients' => [$recipient->id],
            'module' => 'goal',
            'priority' => 'high',
            'related' => ['type' => Goal::class, 'id' => $goal->id],
            'related_user_id' => $goal->user_id,
            'title' => $forCoach ? 'Trainee goal off track' : 'Goal off track',
            'message' => $forCoach
                ? "{$goal->user?->name}'s goal \"{$goal->name}\" needs coaching attention."
                : "Your goal \"{$goal->name}\" is behind schedule ({$goal->progressPercent()}% complete).",
            'action_link' => [
                'route' => $forCoach ? 'goals.coaching' : 'goals.index',
                'params' => [],
                'label' => $forCoach ? 'Open coaching' : 'View goals',
            ],
            'payload' => [
                'goal_id' => $goal->id,
                'goal_name' => $goal->name,
                'for_coach' => $forCoach,
            ],
        ]);
    }
}
