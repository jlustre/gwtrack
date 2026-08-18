<?php

namespace App\Notifications\Goals;

use App\Models\Goal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GoalReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Goal $goal,
        private readonly string $message,
        private readonly bool $forCoach = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trigger' => 'goal_reminder',
            'category' => 'Goals & Performance',
            'title' => $this->forCoach ? 'Trainee goal check-in' : 'Goal reminder',
            'message' => $this->forCoach
                ? "{$this->goal->user?->name}: {$this->message}"
                : $this->message,
            'goal_id' => $this->goal->id,
            'goal_name' => $this->goal->name,
            'action_route' => $this->forCoach ? 'goals.coaching' : 'goals.index',
            'action_url' => route($this->forCoach ? 'goals.coaching' : 'goals.index', [], false),
        ];
    }
}
