<?php

namespace App\Services\Teams;

use App\Models\Team;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateMyTeamService
{
    public function __construct(
        private readonly DownlineHierarchyService $hierarchy,
    ) {}

    public function userMayCreate(User $user): bool
    {
        if (
            ! $user->can('view own team')
            && ! $user->can('manage team')
            && ! $user->can('create own team')
        ) {
            return false;
        }

        return ! $this->leadsActiveTeam($user);
    }

    public function leadsActiveTeam(User $user): bool
    {
        return Team::query()
            ->where('leader_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    public function hierarchyMemberCount(User $user): int
    {
        $this->hierarchy->ensureSelfHierarchyPath($user);

        return $this->hierarchy->descendantsQuery($user, includeSelf: false)->count();
    }

    /**
     * @return array{team: Team, members_moved: int}
     */
    public function create(User $leader, string $name, ?string $description = null): array
    {
        if (! $this->userMayCreate($leader)) {
            throw ValidationException::withMessages([
                'name' => $this->leadsActiveTeam($leader)
                    ? 'You already lead an active team. Update that team instead of creating another.'
                    : 'You do not have permission to create a team.',
            ]);
        }

        $name = trim($name);

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Enter a team name.',
            ]);
        }

        $this->hierarchy->ensureSelfHierarchyPath($leader);

        return DB::transaction(function () use ($leader, $name, $description): array {
            $team = Team::query()->create([
                'name' => $name,
                'description' => filled($description) ? trim($description) : null,
                'leader_id' => $leader->id,
                'owner_id' => $this->resolveOwnerId($leader),
                'parent_id' => $leader->team_id,
                'is_active' => true,
            ]);

            $memberIds = $this->hierarchy
                ->descendantsQuery($leader, includeSelf: true)
                ->pluck('users.id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $membersMoved = 0;

            if ($memberIds !== []) {
                $membersMoved = User::query()
                    ->whereIn('id', $memberIds)
                    ->update(['team_id' => $team->id]);
            }

            $leader->refresh();

            return [
                'team' => $team->fresh(['leader', 'owner']),
                'members_moved' => max(0, $membersMoved - 1),
            ];
        });
    }

    private function resolveOwnerId(User $leader): int
    {
        foreach ($this->hierarchy->uplineUserIds($leader) as $ancestorId) {
            $ancestor = User::query()->find($ancestorId);

            if ($ancestor?->hasRole('agency-owner')) {
                return (int) $ancestor->id;
            }
        }

        if ($leader->hasRole('agency-owner')) {
            return (int) $leader->id;
        }

        return (int) $leader->id;
    }
}
