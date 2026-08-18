<?php

namespace App\Notifications\Goals;

use App\Models\Goal;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GoalOffTrackNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Goal $goal,
        private readonly bool $forCoach = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trigger' => 'goal_off_track',
            'category' => 'Goals & Performance',
            'title' => $this->forCoach ? 'Trainee goal off track' : 'Goal off track',
            'message' => $this->forCoach
                ? "{$this->goal->user?->name}'s goal \"{$this->goal->name}\" needs coaching attention."
                : "Your goal \"{$this->goal->name}\" is behind schedule ({$this->goal->progressPercent()}% complete).",
            'goal_id' => $this->goal->id,
            'goal_name' => $this->goal->name,
            'action_route' => $this->forCoach ? 'goals.coaching' : 'goals.index',
            'action_url' => route($this->forCoach ? 'goals.coaching' : 'goals.index', [], false),
        ];
    }
}
