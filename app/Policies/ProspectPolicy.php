<?php

namespace App\Policies;

use App\Models\Prospect;
use App\Models\User;
use App\Policies\Concerns\AuthorizesProspectAccess;
use App\Services\Fna\FnaClientInviteRecipientService;
use App\Services\Fna\FnaClientInviteService;

class ProspectPolicy
{
    use AuthorizesProspectAccess;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['manage prospects', 'view shared prospects']) || $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function view(User $user, Prospect $prospect): bool
    {
        return $this->canAccessProspect($user, $prospect);
    }

    public function create(User $user): bool
    {
        return $user->checkPermissionTo('manage prospects')
            || $user->hasAnyRole(['super-admin', 'admin', 'agency-owner', 'team-leader', 'certified-field-mentor', 'trainer', 'member', 'associate', 'new-recruit']);
    }

    public function update(User $user, Prospect $prospect): bool
    {
        return (int) $prospect->owner_id === $user->id
            || $user->hasAnyRole(['super-admin', 'admin'])
            || $this->canAccessProspect($user, $prospect, 'can_edit_limited_fields');
    }

    public function delete(User $user, Prospect $prospect): bool
    {
        return (int) $prospect->owner_id === $user->id || $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function share(User $user, Prospect $prospect): bool
    {
        if (! $user->can('share prospects') && ! $user->hasAnyRole(['super-admin', 'admin'])) {
            return false;
        }

        return (int) $prospect->owner_id === $user->id || $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function convert(User $user, Prospect $prospect): bool
    {
        return (int) $prospect->owner_id === $user->id || $user->hasAnyRole(['super-admin', 'admin']);
    }

    public function requestFnaClientPortal(User $user, Prospect $prospect): bool
    {
        if (! $this->canAccessProspect($user, $prospect)) {
            return false;
        }

        $inviteService = app(FnaClientInviteService::class);

        if ($inviteService->agentCanSendInvites($user)) {
            return app(FnaClientInviteRecipientService::class)->canInviteRecipient($user, $prospect, null);
        }

        return (int) $prospect->owner_id === $user->id;
    }
}
