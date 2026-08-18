<?php

namespace App\Policies;

use App\Models\User;

class TeamExportPolicy
{
    public function export(User $viewer): bool
    {
        return $viewer->hasAnyPermission(['export team data', 'view all teams'])
            || $viewer->teamVisibilityGrants()
                ->where('can_export', true)
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->exists();
    }
}
