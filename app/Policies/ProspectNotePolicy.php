<?php

namespace App\Policies;

use App\Models\Prospect;
use App\Models\ProspectNote;
use App\Models\User;
use App\Policies\Concerns\AuthorizesProspectAccess;

class ProspectNotePolicy
{
    use AuthorizesProspectAccess;

    public function view(User $user, ProspectNote $note): bool
    {
        return $this->canAccessProspect($user, $note->prospect);
    }

    public function create(User $user, Prospect $prospect): bool
    {
        return $this->canAccessProspect($user, $prospect, 'can_add_notes');
    }
}
