<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesDownlineVisibility;
use App\Services\DownlineHierarchyService;

class UserPolicy
{
    use AuthorizesDownlineVisibility;

    public function view(User $viewer, User $member): bool
    {
        return $this->canViewMember($viewer, $member);
    }

    public function viewSensitive(User $viewer, User $member): bool
    {
        return app(MemberVisibilityPolicy::class)->viewSensitive($viewer, $member);
    }

    public function assignMentor(User $viewer, User $member): bool
    {
        return $this->canViewMember($viewer, $member)
            && ($viewer->hasPermissionTo('assign mentors') || $viewer->hasAnyRole(['super-admin', 'admin', 'agency-owner']));
    }

    public function enterProduction(User $viewer, User $member): bool
    {
        return app(DownlineHierarchyService::class)->canEnterProductionFor($viewer, $member);
    }
}
