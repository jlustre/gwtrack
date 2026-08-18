<?php

namespace App\Policies;

use App\Models\User;

class TeamPolicy
{
    public function view(User $viewer): bool
    {
        return $viewer->hasAnyPermission(['view own team', 'view team', 'view direct downline', 'view full downline', 'view all teams']);
    }

    public function assignMentor(User $viewer): bool
    {
        return $viewer->hasPermissionTo('assign mentors')
            || $viewer->hasAnyRole(['super-admin', 'admin', 'agency-owner']);
    }

    public function manage(User $viewer): bool
    {
        return $viewer->hasAnyPermission(['manage team members', 'manage team', 'view all teams']);
    }

    public function create(User $viewer): bool
    {
        return app(\App\Services\Teams\CreateMyTeamService::class)->userMayCreate($viewer);
    }
}
