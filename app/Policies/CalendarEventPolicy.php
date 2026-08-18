<?php

namespace App\Policies;

use App\Models\CalendarEvent;
use App\Models\User;
use App\Services\CalendarShareService;

class CalendarEventPolicy
{
    public function __construct(private readonly CalendarShareService $calendarShare) {}
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['view calendar', 'view shared calendar events', 'manage organization calendar']);
    }

    public function view(User $user, CalendarEvent $event): bool
    {
        if ($event->organizer_id === $user->id || $user->hasAnyPermission(['manage organization calendar', 'view private events'])) {
            return true;
        }

        if ($event->organizer_id !== $user->id) {
            $organizer = $event->organizer;
            if ($organizer && $this->calendarShare->canViewCfmCalendar($user, $organizer)) {
                return $event->visibility !== 'private';
            }
        }

        if ($event->visibility === 'private') {
            return false;
        }

        if ($event->attendees()->where('user_id', $user->id)->exists()) {
            return true;
        }

        if ($event->visibility === 'public_organization' && $user->hasPermissionTo('view shared calendar events')) {
            return true;
        }

        if ($event->visibility === 'shared_team' && $event->visibilityRules()->where('team_id', $user->team_id)->exists()) {
            return true;
        }

        return $event->visibilityRules()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create calendar events');
    }

    public function update(User $user, CalendarEvent $event): bool
    {
        return ($event->organizer_id === $user->id && $user->hasPermissionTo('edit own calendar events'))
            || $user->hasAnyPermission(['manage organization calendar', 'manage team calendar']);
    }

    public function delete(User $user, CalendarEvent $event): bool
    {
        return ($event->organizer_id === $user->id && $user->hasPermissionTo('delete own calendar events'))
            || $user->hasPermissionTo('manage organization calendar');
    }
}
