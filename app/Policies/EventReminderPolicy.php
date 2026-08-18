<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

class EventReminderPolicy
{
    public function manage(User $user, CalendarEvent $event): bool
    {
        return $event->organizer_id === $user->id
            || $event->attendees()->where('user_id', $user->id)->exists()
            || $user->hasPermissionTo('manage organization calendar');
    }
}
