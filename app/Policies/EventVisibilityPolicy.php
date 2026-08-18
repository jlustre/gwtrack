<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;

class EventVisibilityPolicy
{
    public function manage(User $user, CalendarEvent $event): bool
    {
        return $event->organizer_id === $user->id
            || $user->hasAnyPermission(['manage event visibility', 'manage organization calendar']);
    }
}
