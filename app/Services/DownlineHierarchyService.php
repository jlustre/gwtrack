<?php

namespace App\Services;

use App\Models\User;
use App\Support\GreatwayRankCatalog;
use App\Support\MemberDisplayName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DownlineHierarchyService
{
    public function __construct(private readonly ChecklistService $checklists) {}

    public function rebuild(): void
    {
        DB::transaction(function (): void {
            DB::table('user_hierarchy_paths')->delete();
            DB::table('sponsor_relationships')->whereNull('deleted_at')->delete();

            User::query()
                ->select(['id', 'sponsor_id'])
                ->orderBy('id')
                ->chunkById(100, function (Collection $users): void {
                    foreach ($users as $user) {
                        DB::table('user_hierarchy_paths')->updateOrInsert(
                            ['ancestor_id' => $user->id, 'descendant_id' => $user->id],
                            ['depth' => 0, 'created_at' => now(), 'updated_at' => now()]
                        );

                        if (! $user->sponsor_id) {
                            continue;
                        }

                        DB::table('sponsor_relationships')->updateOrInsert(
                            [
                                'sponsor_id' => $user->sponsor_id,
                                'member_id' => $user->id,
                                'status' => 'active',
                            ],
                            [
                                'started_at' => $user->joined_at ?? now(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                    }
                });

            User::query()
                ->whereNotNull('sponsor_id')
                ->select(['id', 'sponsor_id'])
                ->orderBy('id')
                ->chunkById(100, function (Collection $users): void {
                    foreach ($users as $user) {
                        $ancestorPaths = DB::table('user_hierarchy_paths')
                            ->where('descendant_id', $user->sponsor_id)
                            ->get(['ancestor_id', 'depth']);

                        foreach ($ancestorPaths as $path) {
                            DB::table('user_hierarchy_paths')->updateOrInsert(
                                ['ancestor_id' => $path->ancestor_id, 'descendant_id' => $user->id],
                                ['depth' => $path->depth + 1, 'created_at' => now(), 'updated_at' => now()]
                            );
                        }
                    }
                });
        });
    }

    public function descendantsQuery(User $root, bool $includeSelf = false): Builder
    {
        return User::query()
            ->select('users.*')
            ->join('user_hierarchy_paths', 'users.id', '=', 'user_hierarchy_paths.descendant_id')
            ->where('user_hierarchy_paths.ancestor_id', $root->id)
            ->when(! $includeSelf, fn (Builder $query) => $query->where('user_hierarchy_paths.depth', '>', 0));
    }

    /**
     * @return Collection<int, int>
     */
    public function uplineUserIds(User $member): Collection
    {
        return DB::table('user_hierarchy_paths')
            ->where('descendant_id', $member->id)
            ->where('depth', '>', 0)
            ->orderBy('depth')
            ->pluck('ancestor_id')
            ->map(fn ($id) => (int) $id);
    }

    public function isUplineOf(User $ancestor, User $descendant): bool
    {
        if ($ancestor->id === $descendant->id) {
            return false;
        }

        return DB::table('user_hierarchy_paths')
            ->where('ancestor_id', $ancestor->id)
            ->where('descendant_id', $descendant->id)
            ->where('depth', '>', 0)
            ->exists();
    }

    public function directRecruitsQuery(User $root): Builder
    {
        return User::query()->where('sponsor_id', $root->id);
    }

    public function visibleMembersQuery(User $viewer): Builder
    {
        if ($viewer->hasAnyRole(['super-admin', 'admin']) || $viewer->hasPermissionTo('view all teams')) {
            return User::query();
        }

        $hierarchyMemberIds = $this->hierarchyMembersQuery($viewer)->select('users.id');
        $activeApprenticeIds = DB::table('mentor_assignments')
            ->where('mentor_id', $viewer->id)
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->select('apprentice_id');
        $grantedMemberIds = DB::table('team_visibility_permissions')
            ->where('viewer_id', $viewer->id)
            ->whereNull('deleted_at')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->select('visible_user_id');
        $ownedTeamIds = $viewer->hasRole('agency-owner')
            ? DB::table('teams')
                ->where('owner_id', $viewer->id)
                ->whereNull('deleted_at')
                ->select('id')
            : null;

        return User::query()->where(function (Builder $query) use ($viewer, $hierarchyMemberIds, $activeApprenticeIds, $grantedMemberIds, $ownedTeamIds): void {
            $query->whereKey($viewer->id)
                ->orWhereIn('users.id', $hierarchyMemberIds)
                ->orWhereIn('users.id', $activeApprenticeIds)
                ->orWhereIn('users.id', $grantedMemberIds);

            if ($ownedTeamIds !== null) {
                $query->orWhereIn('users.team_id', $ownedTeamIds);
            }
        });
    }

    /**
     * Members included in dashboard stat cards and detail modals, using the same scope as member access checks.
     */
    public function dashboardMembersQuery(User $viewer): Builder
    {
        $this->ensureSelfHierarchyPath($viewer);

        return $this->visibleMembersQuery($viewer);
    }

    public function ensureSelfHierarchyPath(User $user): void
    {
        DB::table('user_hierarchy_paths')->updateOrInsert(
            ['ancestor_id' => $user->id, 'descendant_id' => $user->id],
            ['depth' => 0, 'created_at' => now(), 'updated_at' => now()]
        );
    }

    private function hierarchyMembersQuery(User $viewer): Builder
    {
        $query = $this->descendantsQuery($viewer, includeSelf: true);

        if ($viewer->hasPermissionTo('view full downline')) {
            return $query;
        }

        if ($viewer->hasAnyPermission([
            'view team',
            'view own team',
            'view direct downline',
            'view team tree',
            'view team table',
            'view org chart',
        ])) {
            return $query->where('user_hierarchy_paths.depth', '<=', 1);
        }

        return $query->where('user_hierarchy_paths.depth', '=', 0);
    }

    public function canViewMember(User $viewer, User $member): bool
    {
        return $this->visibleMembersQuery($viewer)
            ->whereKey($member->id)
            ->exists();
    }

    public function canEnterProductionFor(User $viewer, User $member): bool
    {
        if ($viewer->id === $member->id) {
            return true;
        }

        if (! $this->canViewMember($viewer, $member)) {
            return false;
        }

        if ($viewer->hasAnyRole(['super-admin', 'admin', 'agency-owner'])) {
            return true;
        }

        if ($viewer->hasRole('certified-field-mentor')) {
            return $viewer->mentorAssignments()
                ->where('apprentice_id', $member->id)
                ->where('status', 'active')
                ->exists();
        }

        return false;
    }

    public function memberMetrics(User $member): array
    {
        $totalDownline = DB::table('user_hierarchy_paths')
            ->where('ancestor_id', $member->id)
            ->where('depth', '>', 0)
            ->count();

        return [
            'direct_recruits' => User::where('sponsor_id', $member->id)->count(),
            'total_downline' => $totalDownline,
            'prospects' => DB::table('prospects')->where('owner_id', $member->id)->whereNull('deleted_at')->count(),
        ];
    }

    public function visibleMemberMetrics(User $viewer, User $member): array
    {
        $visibleMemberIds = $this->visibleMembersQuery($viewer)->select('users.id');

        return [
            'direct_recruits' => $this->directRecruitsQuery($member)
                ->whereIn('users.id', $visibleMemberIds)
                ->count(),
            'total_downline' => $this->descendantsQuery($member)
                ->whereIn('users.id', $visibleMemberIds)
                ->count(),
            'prospects' => DB::table('prospects')
                ->where('owner_id', $member->id)
                ->whereNull('deleted_at')
                ->count(),
        ];
    }

    public function progressSummary(User $member): array
    {
        return [
            'licensing' => $this->checklistTypeProgressEntry($member, 'licensing'),
            'onboarding' => $this->checklistTypeProgressEntry($member, 'onboarding'),
            'training' => $this->percentComplete('training_progress', $member->id),
            'apprenticeship' => $this->checklistTypeProgressEntry($member, 'fap'),
            'rank' => $this->percentComplete('user_rank_progress', $member->id),
        ];
    }

    /**
     * @return array{started: bool, percent: int}
     */
    private function checklistTypeProgressEntry(User $member, string $typeCode): array
    {
        $started = $this->checklists->hasTypeStarted($member, $typeCode);

        return [
            'started' => $started,
            'percent' => $started ? $this->checklistTypePercent($typeCode, $member->id) : 0,
        ];
    }

    public function checklistTypePercent(string $typeCode, int $userId): int
    {
        return $this->checklists->checklistPercent(
            $this->checklists->activeChecklistIdsForType($typeCode),
            $userId,
        );
    }

    public function percentComplete(string $table, int $userId): int
    {
        $total = DB::table($table)->where('user_id', $userId)->count();

        if ($total === 0) {
            return 0;
        }

        $complete = DB::table($table)
            ->where('user_id', $userId)
            ->whereIn('status', ['completed', 'confirmed', 'approved'])
            ->count();

        return (int) round(($complete / $total) * 100);
    }

    /**
     * @return list<array{
     *     id: int,
     *     parent_id: int|null,
     *     depth: int,
     *     name: string,
     *     email: string,
     *     rank: string,
     *     sponsor: string,
     *     country: string,
     *     direct_recruits: int,
     *     total_downline: int,
     *     is_active: bool,
     *     has_children: bool,
     *     member_url: string,
     *     branch_tree_url: string,
     * }>
     */
    public function hierarchyTableRows(User $root, Collection $members): array
    {
        $members = $members->keyBy('id');

        $root->loadMissing(['profile', 'rank', 'sponsor']);
        if (! $members->has($root->id)) {
            $root->setAttribute('branch_depth', 0);
            $members = $members->put($root->id, $root);
        }

        $childrenBySponsor = $members
            ->filter(fn (User $member): bool => $member->sponsor_id !== null && $members->has($member->sponsor_id))
            ->groupBy('sponsor_id')
            ->map(fn (Collection $group) => $group->sortBy('name')->values());

        $memberIds = $members->keys()->all();
        $totalDownlineByMember = $this->totalDownlineCountsFor($memberIds, $memberIds);
        $directRecruitsByMember = $this->directRecruitsCountsFor($memberIds, $memberIds);

        $rows = [];
        $walk = function (int $memberId, int $depth) use (&$walk, &$rows, $members, $childrenBySponsor, $root, $totalDownlineByMember, $directRecruitsByMember): void {
            /** @var User $member */
            $member = $members->get($memberId);
            if (! $member) {
                return;
            }

            $branchDepth = (int) ($member->branch_depth ?? $member->getAttribute('branch_depth') ?? $depth);
            $childGroup = $childrenBySponsor->get($memberId, collect());

            $rows[] = [
                'id' => $member->id,
                'parent_id' => $member->id === $root->id ? null : $member->sponsor_id,
                'depth' => $branchDepth,
                'name' => MemberDisplayName::for($member),
                'email' => $member->email,
                'rank' => $member->rank?->code ?? GreatwayRankCatalog::entryCode(),
                'sponsor' => $member->sponsor?->name ?? '—',
                'country' => $member->profile?->country ?? 'Global',
                'direct_recruits' => (int) ($directRecruitsByMember[$member->id] ?? 0),
                'total_downline' => (int) ($totalDownlineByMember[$member->id] ?? 0),
                'is_active' => (bool) $member->is_active,
                'has_children' => $childGroup->isNotEmpty(),
                'member_url' => route('team.member', $member),
                'branch_tree_url' => route('team.member.hierarchy', $member),
            ];

            foreach ($childGroup as $child) {
                $walk($child->id, $branchDepth + 1);
            }
        };

        $walk($root->id, 0);

        return $rows;
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, int>
     */
    private function totalDownlineCountsFor(array $userIds, array $visibleUserIds): array
    {
        if ($userIds === [] || $visibleUserIds === []) {
            return [];
        }

        return DB::table('user_hierarchy_paths')
            ->select('ancestor_id', DB::raw('COUNT(*) as count'))
            ->whereIn('ancestor_id', $userIds)
            ->whereIn('descendant_id', $visibleUserIds)
            ->where('depth', '>', 0)
            ->groupBy('ancestor_id')
            ->pluck('count', 'ancestor_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    /**
     * @param  list<int>  $userIds
     * @return array<int, int>
     */
    private function directRecruitsCountsFor(array $userIds, array $visibleUserIds): array
    {
        if ($userIds === [] || $visibleUserIds === []) {
            return [];
        }

        return User::query()
            ->select('sponsor_id', DB::raw('COUNT(*) as count'))
            ->whereIn('sponsor_id', $userIds)
            ->whereIn('id', $visibleUserIds)
            ->groupBy('sponsor_id')
            ->pluck('count', 'sponsor_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }
}
