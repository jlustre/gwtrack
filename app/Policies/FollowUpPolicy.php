<?php

namespace App\Policies;

use App\Models\ProspectFollowUp;
use App\Models\User;
use App\Policies\Concerns\AuthorizesProspectAccess;

class FollowUpPolicy
{
    use AuthorizesProspectAccess;

    public function view(User $user, ProspectFollowUp $followUp): bool
    {
        return $this->canAccessProspect($user, $followUp->prospect);
    }

    public function create(User $user, ProspectFollowUp $followUp): bool
    {
        return $this->canAccessProspect($user, $followUp->prospect, 'can_schedule_followups');
    }

    public function update(User $user, ProspectFollowUp $followUp): bool
    {
        return (int) $followUp->assigned_user_id === $user->id
            || $this->canAccessProspect($user, $followUp->prospect, 'can_schedule_followups');
    }
}
