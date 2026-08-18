<?php

namespace App\Policies;

use App\Models\BookingEventType;
use App\Models\User;

class BookingEventTypePolicy
{
    public function view(User $user, BookingEventType $eventType): bool
    {
        return $eventType->owner_id === $user->id || $user->hasPermissionTo('manage team bookings');
    }

    public function manage(User $user, BookingEventType $eventType): bool
    {
        return $eventType->owner_id === $user->id && $user->hasPermissionTo('manage own booking event types');
    }
}
