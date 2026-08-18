<?php

namespace App\Policies;

use App\Models\BookingLink;
use App\Models\User;

class BookingLinkPolicy
{
    public function view(User $user, BookingLink $link): bool
    {
        return $link->owner_id === $user->id || $user->hasPermissionTo('manage team bookings');
    }

    public function manage(User $user, BookingLink $link): bool
    {
        return $link->owner_id === $user->id && $user->hasPermissionTo('create booking links');
    }
}
