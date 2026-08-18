<?php

namespace App\Policies;

use App\Models\FnaClientInvite;
use App\Models\User;

class FnaClientInvitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage fna records') || $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function view(User $user, FnaClientInvite $invite): bool
    {
        return (int) $invite->sender_user_id === $user->id
            || $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function create(User $user): bool
    {
        return app(\App\Services\Fna\FnaClientInviteService::class)->agentCanSendInvites($user);
    }

    public function revoke(User $user, FnaClientInvite $invite): bool
    {
        return (int) $invite->sender_user_id === $user->id
            || $user->hasAnyRole(['super-admin', 'admin']);
    }
}
