<?php

namespace App\Notifications\Goals;

use App\Models\GoalBadge;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GoalAchievementNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly GoalBadge $badge,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'trigger' => 'goal_achievement',
            'category' => 'Goals & Performance',
            'title' => 'Achievement unlocked: '.$this->badge->name,
            'message' => $this->badge->description,
            'badge_slug' => $this->badge->slug,
            'badge_level' => $this->badge->level,
            'action_route' => 'goals.index',
            'action_url' => route('goals.index', [], false),
        ];
    }
}
