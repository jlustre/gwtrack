<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesDownlineVisibility;

class MemberVisibilityPolicy
{
    use AuthorizesDownlineVisibility;

    public function view(User $viewer, User $member): bool
    {
        return $this->canViewMember($viewer, $member);
    }

    public function viewSensitive(User $viewer, User $member): bool
    {
        return $viewer->id === $member->id
            || $viewer->hasAnyPermission(['view sensitive profile data', 'view all teams'])
            || $viewer->teamVisibilityGrants()
                ->where('visible_user_id', $member->id)
                ->where('can_view_sensitive_data', true)
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->exists();
    }
}
