<?php

namespace App\Policies;

use App\Models\Backup;
use App\Models\User;

class BackupPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin') && $user->can('view backups');
    }

    public function view(User $user, Backup $backup): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin') && $user->can('create backups');
    }

    public function download(User $user, Backup $backup): bool
    {
        return $user->hasRole('super-admin') && $user->can('download backups');
    }

    public function delete(User $user, Backup $backup): bool
    {
        return $user->hasRole('super-admin') && $user->can('delete backups');
    }

    public function restore(User $user, Backup $backup): bool
    {
        return $user->hasRole('super-admin') && $user->can('restore backups');
    }
}
