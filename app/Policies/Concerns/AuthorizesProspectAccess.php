<?php

namespace App\Policies\Concerns;

use App\Models\Prospect;
use App\Models\User;

trait AuthorizesProspectAccess
{
    protected function canAccessProspect(User $user, Prospect $prospect, ?string $permissionFlag = null): bool
    {
        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        if ((int) $prospect->owner_id === $user->id) {
            return true;
        }

        $share = $prospect->shares()
            ->with('permission')
            ->where('shared_with', $user->id)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('granted_at')
            ->first();

        if (! $share) {
            return false;
        }

        if (! $permissionFlag) {
            return true;
        }

        return (bool) $share->permission?->{$permissionFlag} || (bool) $share->permission?->can_collaborate_fully;
    }
}
