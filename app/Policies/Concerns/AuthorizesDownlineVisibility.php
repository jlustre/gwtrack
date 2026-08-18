<?php

namespace App\Policies\Concerns;

use App\Models\User;
use App\Services\DownlineHierarchyService;

trait AuthorizesDownlineVisibility
{
    protected function canViewMember(User $viewer, User $member): bool
    {
        return app(DownlineHierarchyService::class)->canViewMember($viewer, $member);
    }
}
