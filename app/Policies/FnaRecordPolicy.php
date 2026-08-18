<?php

namespace App\Policies;

use App\Models\FnaRecord;
use App\Models\User;
use App\Policies\Concerns\AuthorizesFnaAccess;

class FnaRecordPolicy
{
    use AuthorizesFnaAccess;

    public function viewAny(User $user): bool
    {
        return $user->can('manage fna records')
            || $user->can('view shared fna records')
            || $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function view(User $user, FnaRecord $fna): bool
    {
        return $this->canAccessFna($user, $fna);
    }

    public function viewFinancialDetails(User $user, FnaRecord $fna): bool
    {
        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        if ((int) $fna->owner_user_id === $user->id && $user->can('view fna financial details')) {
            return true;
        }

        return $this->canAccessFna($user, $fna, 'can_view_financial_details')
            && $user->can('view fna financial details');
    }

    public function create(User $user): bool
    {
        return $user->can('manage fna records') || $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function update(User $user, FnaRecord $fna): bool
    {
        if ($user->hasAnyRole(['super-admin', 'admin'])) {
            return true;
        }

        return (int) $fna->owner_user_id === $user->id
            && $fna->isEditableByOwner()
            && $user->can('manage fna records');
    }

    public function submit(User $user, FnaRecord $fna): bool
    {
        return (int) $fna->owner_user_id === $user->id
            && $user->can('submit fna for review');
    }

    public function review(User $user, FnaRecord $fna): bool
    {
        if (! $user->can('review trainee fna records')) {
            return false;
        }

        return $this->isAssignedCfm($user, $fna)
            || $user->hasAnyRole(['super-admin', 'admin', 'team-leader', 'agency-owner']);
    }

    public function delete(User $user, FnaRecord $fna): bool
    {
        return (int) $fna->owner_user_id === $user->id
            || $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function export(User $user, FnaRecord $fna): bool
    {
        return $user->can('export fna records') && $this->canAccessFna($user, $fna);
    }
}
