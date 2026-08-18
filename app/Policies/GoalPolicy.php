<?php

namespace App\Policies;

use App\Models\Goal;
use App\Models\User;
use App\Services\DownlineHierarchyService;

class GoalPolicy
{
    public function __construct(
        private readonly DownlineHierarchyService $hierarchy,
    ) {}

    public function viewAny(User $user): bool
    {
        return $user->can('manage goals');
    }

    public function view(User $user, Goal $goal): bool
    {
        if ($goal->user_id === $user->id) {
            return true;
        }

        if ($user->can('view team goals') && $this->hierarchy->canViewMember($user, $goal->user)) {
            return true;
        }

        return $goal->coaches()->where('coach_user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('manage goals');
    }

    public function update(User $user, Goal $goal): bool
    {
        if ($goal->user_id === $user->id) {
            return true;
        }

        return $goal->coaches()
            ->where('coach_user_id', $user->id)
            ->where('can_edit', true)
            ->exists();
    }

    public function delete(User $user, Goal $goal): bool
    {
        return $goal->user_id === $user->id;
    }

    public function coach(User $user): bool
    {
        return $user->can('coach goals') || $user->hasRole('certified-field-mentor');
    }
}
