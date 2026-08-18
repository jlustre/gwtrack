<?php

namespace App\Jobs\Notifications;

use App\Services\Notifications\CalendarReminderDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DispatchCalendarRemindersJob implements ShouldQueue
{
    use Queueable;

    public function handle(CalendarReminderDispatcher $dispatcher): void
    {
        $dispatcher->dispatchDueReminders();
    }
}
