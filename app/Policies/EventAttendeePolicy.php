<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

class EventAttendeePolicy
{
    public function invite(User $user, CalendarEvent $event): bool
    {
        return $event->organizer_id === $user->id
            || $user->hasAnyPermission(['invite attendees', 'manage team calendar', 'manage organization calendar']);
    }
}
