<?php

namespace App\Services\Communication;

use App\Models\MessageCenterAnnouncement;
use App\Models\User;
use Illuminate\Support\Collection;

class AnnouncementAudienceResolver
{
    /**
     * @return list<int>
     */
    public function resolveUserIds(MessageCenterAnnouncement $announcement): array
    {
        return $this->resolve($announcement->audience_type, $announcement->audience_config ?? [])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function userCanSee(User $user, MessageCenterAnnouncement $announcement): bool
    {
        if ($announcement->created_by === $user->id) {
            return true;
        }

        if ($user->can('publish announcements') || $user->can('manage announcements')) {
            return true;
        }

        $type = $this->normalizeAudienceType($announcement->audience_type);
        $config = $announcement->audience_config ?? [];

        return match ($type) {
            'all', 'organization' => true,
            'roles' => $user->hasAnyRole($config['roles'] ?? []),
            'teams' => in_array((int) $user->team_id, array_map('intval', $config['team_ids'] ?? []), true),
            'ranks' => in_array((int) $user->rank_id, array_map('intval', $config['rank_ids'] ?? []), true),
            'users' => in_array($user->id, array_map('intval', $config['user_ids'] ?? []), true),
            default => true,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @return Collection<int, User>
     */
    public function resolve(string $audienceType, array $config = []): Collection
    {
        $type = $this->normalizeAudienceType($audienceType);

        return match ($type) {
            'all', 'organization' => User::query()->whereNull('deleted_at')->where('is_active', true)->get(),
            'roles' => User::query()
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->role($config['roles'] ?? [])
                ->get(),
            'teams' => User::query()
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->whereIn('team_id', array_map('intval', $config['team_ids'] ?? []))
                ->get(),
            'ranks' => User::query()
                ->whereNull('deleted_at')
                ->where('is_active', true)
                ->whereIn('rank_id', array_map('intval', $config['rank_ids'] ?? []))
                ->get(),
            'users' => User::query()
                ->whereNull('deleted_at')
                ->whereIn('id', array_map('intval', $config['user_ids'] ?? []))
                ->get(),
            default => User::query()->whereNull('deleted_at')->where('is_active', true)->get(),
        };
    }

    private function normalizeAudienceType(string $audienceType): string
    {
        $aliases = config('communication-hub.legacy_audience_aliases', []);

        return $aliases[$audienceType] ?? $audienceType;
    }
}
