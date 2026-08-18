<?php

namespace App\Policies;

use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Models\User;
use App\Policies\Concerns\AuthorizesProspectAccess;

class ProspectActivityPolicy
{
    use AuthorizesProspectAccess;

    public function viewAny(User $user, Prospect $prospect): bool
    {
        return $this->canAccessProspect($user, $prospect);
    }

    public function create(User $user, Prospect $prospect): bool
    {
        if ((int) $prospect->owner_id === $user->id || $user->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        return $this->canAccessProspect($user, $prospect, 'can_add_communications');
    }

    public function update(User $user, ProspectActivity $activity): bool
    {
        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        if ((int) $activity->prospect->owner_id === $user->id) {
            return true;
        }

        return (int) $activity->user_id === $user->id
            && $this->canAccessProspect($user, $activity->prospect, 'can_add_communications');
    }

    public function delete(User $user, ProspectActivity $activity): bool
    {
        return $this->update($user, $activity);
    }
}
