<?php

namespace App\Services;

use App\Models\User;

class MemberUplineService
{
    public function contextFor(User $user): array
    {
        $user->loadMissing(['profile', 'rank', 'team', 'sponsor', 'mentor']);

        return [
            'sponsor' => $user->sponsor?->name ?? '—',
            'agencyOwner' => $this->agencyOwnerName($user),
            'mentor' => $user->mentor?->name ?? '—',
            'team' => $user->team?->name ?? '—',
            'rank' => $user->rank?->code ?? '—',
            'role' => $user->getRoleNames()->first() ?? 'member',
            'joinedAt' => $user->joined_at?->format('M j, Y g:i A') ?? '—',
            'lastLoginAt' => $user->last_login_at?->format('M j, Y g:i A') ?? '—',
            'lastLoginIp' => $user->last_login_ip ?? '—',
            'email' => $user->email,
        ];
    }

    public function agencyOwnerName(User $user): string
    {
        return $this->agencyOwner($user)?->name ?? '—';
    }

    public function agencyOwner(User $user): ?User
    {
        return $this->resolveAgencyOwner($user);
    }

    private function resolveAgencyOwner(User $user): ?User
    {
        if ($user->hasRole('agency-owner')) {
            return $user;
        }

        $visited = [$user->id];
        $sponsor = $user->sponsor;

        while ($sponsor) {
            if ($sponsor->hasRole('agency-owner')) {
                return $sponsor;
            }

            if (in_array($sponsor->id, $visited, true) || ! $sponsor->sponsor_id) {
                break;
            }

            $visited[] = $sponsor->id;
            $sponsor = User::query()->whereKey($sponsor->sponsor_id)->first();
        }

        $teamOwner = $user->team?->owner;

        if ($teamOwner?->hasRole('agency-owner')) {
            return $teamOwner;
        }

        return $teamOwner;
    }
}
