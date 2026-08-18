<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    public function view(User $user, Booking $booking): bool
    {
        return $user->hasRole('super-admin')
            || $user->hasPermissionTo('manage team bookings')
            || $booking->cfm_id === $user->id
            || $booking->trainee_id === $user->id;
    }

    public function approve(User $user, Booking $booking): bool
    {
        return $booking->cfm_id === $user->id && $user->hasPermissionTo('approve booking requests');
    }

    public function decline(User $user, Booking $booking): bool
    {
        return $booking->cfm_id === $user->id && $user->hasPermissionTo('decline booking requests');
    }

    public function reschedule(User $user, Booking $booking): bool
    {
        return $user->hasPermissionTo('reschedule bookings')
            && ($booking->cfm_id === $user->id || $booking->trainee_id === $user->id);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $user->hasPermissionTo('cancel bookings')
            && ($booking->cfm_id === $user->id || $booking->trainee_id === $user->id);
    }
}
