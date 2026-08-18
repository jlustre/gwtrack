<?php

namespace App\Policies;

use App\Models\ProspectShare;
use App\Models\User;

class ProspectSharePolicy
{
    public function create(User $user, ProspectShare $share): bool
    {
        return (int) $share->prospect->owner_id === $user->id || $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function revoke(User $user, ProspectShare $share): bool
    {
        return (int) $share->prospect->owner_id === $user->id
            || (int) $share->granted_by === $user->id
            || $user->hasAnyRole(['super-admin', 'admin']);
    }
}
