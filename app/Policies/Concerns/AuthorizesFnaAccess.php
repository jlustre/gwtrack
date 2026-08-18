<?php

namespace App\Policies\Concerns;

use App\Models\FnaPermission;
use App\Models\FnaRecord;
use App\Models\MentorAssignment;
use App\Models\User;

trait AuthorizesFnaAccess
{
    protected function canAccessFna(User $user, FnaRecord $fna, ?string $permissionFlag = null): bool
    {
        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        if ((int) $fna->owner_user_id === $user->id) {
            return true;
        }

        if ($user->can('review trainee fna records') && $this->isAssignedCfm($user, $fna)) {
            if ($fna->status === 'draft' && $permissionFlag === null) {
                return false;
            }

            if (in_array($fna->status, config('fna.cfm_visible_statuses', []), true)) {
                return true;
            }
        }

        $grant = $fna->permissions()->activeFor($user)->first();

        if ($grant instanceof FnaPermission && $grant->allows($permissionFlag)) {
            return true;
        }

        return false;
    }

    protected function isAssignedCfm(User $user, FnaRecord $fna): bool
    {
        return MentorAssignment::query()
            ->where('mentor_id', $user->id)
            ->where('apprentice_id', $fna->owner_user_id)
            ->where('status', 'active')
            ->exists();
    }
}
