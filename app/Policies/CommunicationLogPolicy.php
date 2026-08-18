<?php

namespace App\Policies;

use App\Models\ProspectCommunication;
use App\Models\User;
use App\Policies\Concerns\AuthorizesProspectAccess;

class CommunicationLogPolicy
{
    use AuthorizesProspectAccess;

    public function view(User $user, ProspectCommunication $communication): bool
    {
        return $this->canAccessProspect($user, $communication->prospect);
    }

    public function create(User $user, ProspectCommunication $communication): bool
    {
        return $this->canAccessProspect($user, $communication->prospect, 'can_add_communications');
    }
}
