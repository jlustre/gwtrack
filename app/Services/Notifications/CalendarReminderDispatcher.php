<?php

namespace App\Services\Notifications;

use App\Models\CalendarEventReminder;
use App\Models\User;
use Illuminate\Support\Collection;

class CalendarReminderDispatcher
{
    public function __construct(
        private readonly NotificationOrchestrator $notifications,
    ) {}

    public function dispatchDueReminders(): int
    {
        $sent = 0;

        CalendarEventReminder::query()
            ->with(['event.organizer', 'user'])
            ->whereNull('sent_at')
            ->whereHas('event', fn ($query) => $query->where('starts_at', '>', now()))
            ->chunkById(50, function ($reminders) use (&$sent): void {
                foreach ($reminders as $reminder) {
                    if ($this->dispatchReminder($reminder)) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    private function dispatchReminder(CalendarEventReminder $reminder): bool
    {
        $event = $reminder->event;

        if (! $event || ! $event->starts_at) {
            return false;
        }

        $fireAt = $event->starts_at->copy()->subMinutes((int) $reminder->minutes_before);

        if (now()->lt($fireAt)) {
            return false;
        }

        $recipient = $reminder->user ?? $event->organizer;

        if (! $recipient instanceof User) {
            return false;
        }

        $this->notifications->dispatch('calendar_event_reminder', [
            'queue' => true,
            'recipients' => [$recipient->id],
            'module' => 'calendar',
            'priority' => 'medium',
            'related' => ['type' => $event::class, 'id' => $event->id],
            'template_data' => [
                'event_title' => $event->title,
                'session_time' => $event->starts_at->format('M j, Y g:i A'),
                'organizer_name' => $event->organizer?->name ?? config('app.name'),
            ],
            'action_link' => [
                'route' => 'calendar.index',
                'label' => 'View calendar',
            ],
        ]);

        $reminder->update(['sent_at' => now()]);

        return true;
    }
}
