<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class UserRankRoleLabel
{
    private const ROLE_ABBREVIATIONS = [
        'super-admin' => 'SA',
        'admin' => 'Admin',
        'agency-owner' => 'AO',
        'team-leader' => 'TL',
        'certified-field-mentor' => 'CFM',
        'trainer' => 'Trainer',
        'associate' => 'Assoc',
        'member' => 'Member',
        'new-recruit' => 'NR',
    ];

    private const ROLE_PRIORITY = [
        'super-admin',
        'admin',
        'agency-owner',
        'team-leader',
        'certified-field-mentor',
        'trainer',
        'associate',
        'member',
        'new-recruit',
    ];

    private const GENERIC_ROLES = [
        'member',
        'associate',
        'new-recruit',
    ];

    public static function for(User $user, string $fallback = 'Portal User'): string
    {
        $user->loadMissing('rank');

        $rankCode = filled($user->rank?->code) ? $user->rank->code : null;
        $roleName = self::primaryRoleName($user);
        $roleAbbrev = $roleName ? self::abbreviateRole($roleName) : null;

        $parts = [];

        if ($rankCode) {
            $parts[] = $rankCode;
        }

        if ($roleAbbrev && (! $rankCode || ! in_array($roleName, self::GENERIC_ROLES, true))) {
            $parts[] = $roleAbbrev;
        }

        return $parts !== [] ? implode('/', $parts) : $fallback;
    }

    public static function abbreviateRole(string $roleName): string
    {
        if (isset(self::ROLE_ABBREVIATIONS[$roleName])) {
            return self::ROLE_ABBREVIATIONS[$roleName];
        }

        return Str::of($roleName)
            ->replace('-', ' ')
            ->title()
            ->toString();
    }

    private static function primaryRoleName(User $user): ?string
    {
        $roles = $user->getRoleNames();

        foreach (self::ROLE_PRIORITY as $role) {
            if ($roles->contains($role)) {
                return $role;
            }
        }

        return $roles->first();
    }
}
