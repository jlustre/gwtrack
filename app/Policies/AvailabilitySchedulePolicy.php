<?php

namespace App\Policies;

use App\Models\AvailabilitySchedule;
use App\Models\User;

class AvailabilitySchedulePolicy
{
    public function view(User $user, AvailabilitySchedule $schedule): bool
    {
        return $schedule->user_id === $user->id || $user->hasPermissionTo('manage team bookings');
    }

    public function manage(User $user, AvailabilitySchedule $schedule): bool
    {
        return $schedule->user_id === $user->id && $user->hasPermissionTo('manage own availability');
    }
}
