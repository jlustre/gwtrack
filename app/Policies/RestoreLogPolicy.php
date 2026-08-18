<?php

namespace App\Policies;

use App\Models\RestoreLog;
use App\Models\User;

class RestoreLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin') && $user->can('view backups');
    }

    public function view(User $user, RestoreLog $restoreLog): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin') && $user->can('restore backups');
    }
}
