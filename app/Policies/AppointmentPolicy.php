<?php

namespace App\Policies;

use App\Models\ProspectAppointment;
use App\Models\User;
use App\Policies\Concerns\AuthorizesProspectAccess;

class AppointmentPolicy
{
    use AuthorizesProspectAccess;

    public function view(User $user, ProspectAppointment $appointment): bool
    {
        return $this->canAccessProspect($user, $appointment->prospect);
    }

    public function create(User $user, ProspectAppointment $appointment): bool
    {
        return $this->canAccessProspect($user, $appointment->prospect, 'can_schedule_appointments');
    }

    public function update(User $user, ProspectAppointment $appointment): bool
    {
        return (int) $appointment->owner_id === $user->id
            || $this->canAccessProspect($user, $appointment->prospect, 'can_schedule_appointments');
    }
}
