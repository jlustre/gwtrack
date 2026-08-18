<?php

namespace App\Jobs\Goals;

use App\Services\Goals\GoalReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchGoalReminders implements ShouldQueue
{
    use Queueable;

    public function handle(GoalReminderService $reminders): void
    {
        $reminders->processDueReminders();
    }
}
