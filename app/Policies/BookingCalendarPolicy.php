<?php

namespace App\Policies;

use App\Models\User;

class BookingCalendarPolicy
{
    public function view(User $user): bool
    {
        return $user->hasAnyPermission(['view own bookings', 'view apprentice bookings', 'manage team bookings']);
    }

    public function manageSettings(User $user): bool
    {
        return $user->hasPermissionTo('manage booking settings');
    }
}
