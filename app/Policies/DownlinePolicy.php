<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesDownlineVisibility;

class DownlinePolicy
{
    use AuthorizesDownlineVisibility;

    public function viewTree(User $viewer): bool
    {
        return $viewer->hasAnyPermission(['view own team', 'view team tree', 'view full downline', 'view all teams']);
    }

    public function viewOrgChart(User $viewer): bool
    {
        return $viewer->hasAnyPermission(['view org chart', 'view full downline', 'view all teams']);
    }

    public function viewTable(User $viewer): bool
    {
        return $viewer->hasAnyPermission(['view team table', 'view direct downline', 'view full downline', 'view all teams']);
    }

    public function viewMember(User $viewer, User $member): bool
    {
        return $this->canViewMember($viewer, $member);
    }
}
