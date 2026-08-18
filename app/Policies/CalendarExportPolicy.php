<?php

namespace App\Policies;

use App\Models\User;

class CalendarExportPolicy
{
    public function export(User $user): bool
    {
        return $user->hasAnyPermission(['view calendar', 'manage team calendar', 'manage organization calendar']);
    }
}
