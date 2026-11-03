<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\CfmManagementService;
use App\Services\ChecklistService;
use App\Services\DownlineHierarchyService;
use App\Services\MemberProfileTabsService;
use App\Support\GreatwayRankCatalog;
use App\Support\MemberDisplayName;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DownlineController extends Controller
{
    public function __construct(
        private readonly DownlineHierarchyService $hierarchy,
        private readonly MemberProfileTabsService $memberProfileTabs,
        private readonly ChecklistService $checklists,
        private readonly CfmManagementService $cfmManagement,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $members = $this->filteredMembers($request, $this->hierarchy->visibleMembersQuery($user));

        return view('team.downline.index', [
            'stats' => $this->stats($user, $user),
            'rankDistribution' => $this->rankDistribution($user, $user),
            'countryDistribution' => $this->countryDistribution($user, $user),
            'members' => $members->paginate(10)->withQueryString(),
            'filters' => $this->filterOptions($user),
        ]);
    }

    public function tree(Request $request, ?User $user = null): View
    {
        $viewer = $request->user();
        $root = $user ?? $viewer;
        abort_unless($this->hierarchy->canViewMember($viewer, $root), 403);

        $children = $this->hierarchy->directRecruitsQuery($root)
            ->with(['profile', 'rank', 'sponsor', 'mentor'])
            ->whereIn('users.id', $this->hierarchy->visibleMembersQuery($viewer)->select('users.id'))
            ->withCount([
                'sponsoredMembers as direct_recruits_count' => fn (Builder $query) => $query
                    ->whereIn('users.id', $this->hierarchy->visibleMembersQuery($viewer)->select('users.id')),
                'prospects',
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (User $member) => $this->memberCard($member, $viewer));

        return view('team.downline.tree', [
            'root' => $this->memberCard($root->loadMissing(['profile', 'rank', 'sponsor', 'mentor']), $viewer),
            'children' => $children,
            'filters' => $this->filterOptions($viewer),
        ]);
    }

    public function treeBranch(Request $request, User $user): JsonResponse
    {
        $viewer = $request->user();
        abort_unless($this->hierarchy->canViewMember($viewer, $user), 403);

        $children = $this->hierarchy->directRecruitsQuery($user)
            ->whereIn('users.id', $this->hierarchy->visibleMembersQuery($viewer)->select('users.id'))
            ->with(['profile', 'rank', 'sponsor', 'mentor'])
            ->withCount([
                'sponsoredMembers as direct_recruits_count' => fn (Builder $query) => $query
                    ->whereIn('users.id', $this->hierarchy->visibleMembersQuery($viewer)->select('users.id')),
                'prospects',
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (User $member) => $this->memberCard($member, $viewer));

        return response()->json([
            'html' => view('team.downline.partials.tree-branch-children', compact('children'))->render(),
        ]);
    }

    public function treeSearch(Request $request): JsonResponse
    {
        $viewer = $request->user();
        $term = trim((string) $request->string('q'));

        if (mb_strlen($term) < 3) {
            return response()->json(['members' => []]);
        }

        $likeTerm = '%'.addcslashes($term, '%_\\').'%';

        $members = $this->hierarchy->visibleMembersQuery($viewer)
            ->where(function (Builder $query) use ($likeTerm): void {
                $query->where('users.name', 'like', $likeTerm)
                    ->orWhere('users.email', 'like', $likeTerm);
            })
            ->with('profile')
            ->orderBy('users.name')
            ->limit(25)
            ->get()
            ->map(fn (User $member): array => $this->treeSearchMemberPayload($member, $viewer))
            ->values()
            ->all();

        return response()->json(['members' => $members]);
    }

    /**
     * @return array{id: int, name: string, tree_top_url: string}
     */
    private function treeSearchMemberPayload(User $member, User $viewer): array
    {
        return [
            'id' => $member->id,
            'name' => MemberDisplayName::for($member),
            'tree_top_url' => $member->id === $viewer->id
                ? route('team.tree')
                : route('team.member.tree', $member),
        ];
    }

    public function orgChart(Request $request, ?User $user = null): View
    {
        $viewer = $request->user();
        $root = $user ?? $viewer;
        abort_unless($this->hierarchy->canViewMember($viewer, $root), 403);

        $root->loadMissing(['profile', 'rank', 'roles', 'mentor', 'sponsor', 'team']);

        $leaders = $this->hierarchy->directRecruitsQuery($root)
            ->with(['profile', 'rank', 'roles', 'mentor', 'sponsor', 'team'])
            ->whereIn('users.id', $this->hierarchy->visibleMembersQuery($viewer)->select('users.id'))
            ->withCount([
                'sponsoredMembers as direct_recruits_count' => fn (Builder $query) => $query
                    ->whereIn('users.id', $this->hierarchy->visibleMembersQuery($viewer)->select('users.id')),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (User $member): array => $this->orgChartProfilePayload($member, $viewer))
            ->values();

        return view('team.downline.org-chart', [
            'root' => $this->orgChartProfilePayload($root, $viewer),
            'leaders' => $leaders,
            'branchSummary' => $this->stats($root, $viewer),
        ]);
    }

    public function table(Request $request): View
    {
        $user = $request->user();
        $members = $this->filteredMembers($request, $this->hierarchy->visibleMembersQuery($user));

        return view('team.downline.table', [
            'stats' => $this->downlineStats($user),
            'members' => $members->paginate(15)->withQueryString(),
            'filters' => $this->filterOptions($user),
            'cfmCandidates' => $user->canAccessCfmManagement()
                ? $this->cfmManagement->accessibleCfms($user)->map(fn (array $cfm) => [
                    'id' => $cfm['id'],
                    'name' => $cfm['name'],
                    'rank' => $cfm['rank'] ?? null,
                ])->all()
                : [],
        ]);
    }

    public function directs(Request $request): View
    {
        $user = $request->user();
        $query = $this->hierarchy->directRecruitsQuery($user)
            ->whereIn('users.id', $this->hierarchy->visibleMembersQuery($user)->select('users.id'));

        return view('team.directs', [
            'stats' => $this->directStats($user),
            'members' => $this->filteredMembers($request, $query)->paginate(15)->withQueryString(),
            'filters' => $this->filterOptions($user),
        ]);
    }

    public function hierarchyTable(Request $request, ?User $user = null): View
    {
        $viewer = $request->user();
        $treeRoot = ($user && $user->id !== $viewer->id) ? $user : $viewer;
        abort_unless($this->hierarchy->canViewMember($viewer, $treeRoot), 403);
        $treeRoot->loadMissing('sponsor');

        $membersQuery = $this->hierarchy->descendantsQuery($treeRoot, includeSelf: true)
            ->addSelect('user_hierarchy_paths.depth as branch_depth')
            ->whereIn('users.id', $this->hierarchy->visibleMembersQuery($viewer)->select('users.id'));

        $members = $membersQuery
            ->get()
            ->loadMissing(['profile', 'rank', 'roles', 'mentor', 'sponsor', 'team']);

        $membersById = $members->keyBy('id');
        $rows = collect($this->hierarchy->hierarchyTableRows($treeRoot, $members))
            ->map(function (array $row) use ($membersById, $viewer, $treeRoot): array {
                /** @var User $member */
                $member = $membersById->get($row['id']);
                $uplineHierarchyUrl = null;
                $isCurrentTop = $member->id === $treeRoot->id;

                // Down arrow: on the topmost row when they have a viewable direct upline.
                // If that upline is the logged-in user, return to own hierarchy; otherwise move up one branch.
                if ($isCurrentTop && $treeRoot->sponsor_id && $treeRoot->sponsor_id !== $treeRoot->id) {
                    if ($treeRoot->sponsor_id === $viewer->id) {
                        $uplineHierarchyUrl = route('team.hierarchy');
                    } elseif ($this->hierarchy->canViewMember($viewer, $treeRoot->sponsor)) {
                        $uplineHierarchyUrl = route('team.member.hierarchy', $treeRoot->sponsor_id);
                    }
                }

                return [
                    ...$row,
                    'profile' => $this->hierarchyProfileSummary($member, $viewer),
                    // Up arrow: this member becomes topmost (hidden when already the current top row).
                    'make_top_url' => $isCurrentTop
                        ? null
                        : route('team.member.hierarchy', $member),
                    'hierarchy_top_url' => $member->id === $viewer->id
                        ? route('team.hierarchy')
                        : route('team.member.hierarchy', $member),
                    'upline_hierarchy_url' => $uplineHierarchyUrl,
                ];
            })
            ->values()
            ->all();

        $searchMembers = $this->hierarchy->visibleMembersQuery($viewer)
            ->with('profile')
            ->orderBy('name')
            ->get()
            ->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => MemberDisplayName::for($member),
                'hierarchy_top_url' => $member->id === $viewer->id
                    ? route('team.hierarchy')
                    : route('team.member.hierarchy', $member),
            ])
            ->values()
            ->all();

        return view('team.downline.hierarchy-table', [
            'root' => $treeRoot->loadMissing(['profile', 'rank']),
            'viewer' => $viewer,
            'isBranchView' => $treeRoot->id !== $viewer->id,
            'rows' => $rows,
            'searchMembers' => $searchMembers,
        ]);
    }

    private function hierarchyProfileSummary(User $member, User $viewer): array
    {
        $role = $member->roles->pluck('name')->first() ?? 'member';

        return [
            ...$this->memberCard($member, $viewer),
            'role_label' => str($role)->replace('-', ' ')->title()->toString(),
            'province' => $member->profile?->province ?? 'Not set',
            'team' => $member->team?->name ?? 'Unassigned',
            'phone' => $member->profile?->phone ?? 'Not set',
            'license_number' => $member->profile?->license_number ?? 'Not licensed yet',
            'can_see_sensitive' => $viewer->can('viewSensitive', $member),
            'member_url' => route('team.member', $member),
            'hierarchy_url' => route('team.member.hierarchy', $member),
            'tree_url' => route('team.member.tree', $member),
        ];
    }

    public function member(Request $request, User $user): View
    {
        abort_unless($this->hierarchy->canViewMember($request->user(), $user), 403);

        $user->load(['profile', 'rank', 'team', 'sponsor', 'mentor', 'roles']);

        return view('team.downline.member', [
            'member' => $user,
            'metrics' => $this->hierarchy->visibleMemberMetrics($request->user(), $user),
            'progress' => $this->progressSummary($user),
            'canSeeSensitive' => $request->user()->can('viewSensitive', $user),
            'checklistTypePanel' => $this->checklists->checklistTypeManagementPanel($user),
            'canStartChecklists' => $this->checklists->canStartChecklistTypesFor($request->user(), $user),
        ]);
    }

    public function export(Request $request): Response
    {
        abort_unless($request->user()->hasAnyPermission(['export team data', 'view all teams']), 403);

        $rows = $this->filteredMembers($request, $this->hierarchy->visibleMembersQuery($request->user()))
            ->with(['profile', 'rank', 'sponsor', 'mentor'])
            ->limit(1000)
            ->get();

        $viewer = $request->user();
        $csv = collect([
            ['Name', 'Email', 'Rank', 'Sponsor', 'CFM', 'Country', 'City', 'Status', 'Joined At', 'Direct Recruits', 'Total Downline'],
        ])->merge($rows->map(function (User $member) use ($viewer): array {
            $metrics = $this->hierarchy->visibleMemberMetrics($viewer, $member);

            return [
                $member->name,
                $member->email,
                $member->rank?->code,
                $member->sponsor?->name,
                $member->mentor?->name,
                $member->profile?->country,
                $member->profile?->city,
                $member->is_active ? 'Active' : 'Inactive',
                $member->joined_at?->toDateString(),
                $metrics['direct_recruits'],
                $metrics['total_downline'],
            ];
        }))->map(fn (array $row): string => collect($row)->map(fn ($value): string => '"'.str_replace('"', '""', (string) $value).'"')->implode(','))
            ->implode("\n");

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="gwtracks-downline.csv"',
        ]);
    }

    private function filteredMembers(Request $request, Builder $query): Builder
    {
        return $query
            ->with(['profile', 'rank', 'sponsor', 'mentor'])
            ->withCount([
                'sponsoredMembers as direct_recruits_count' => fn (Builder $query) => $query
                    ->whereIn('users.id', $this->hierarchy->visibleMembersQuery($request->user())->select('users.id')),
                'prospects',
            ])
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = trim((string) $request->string('search'));

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%")
                        ->orWhereHas('profile', fn (Builder $query) => $query->where('phone', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('rank_id'), fn (Builder $query) => $query->where('users.rank_id', $request->integer('rank_id')))
            ->when($request->filled('country'), fn (Builder $query) => $query->whereHas(
                'profile.countryRecord',
                fn (Builder $query) => $query->where('name', $request->string('country'))
            ))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('users.is_active', $request->string('status') === 'active'))
            ->when($request->filled('joined_from'), fn (Builder $query) => $query->whereDate('users.joined_at', '>=', $request->date('joined_from')))
            ->when($request->filled('joined_to'), fn (Builder $query) => $query->whereDate('users.joined_at', '<=', $request->date('joined_to')))
            ->when($request->filled('mentor'), function (Builder $query) use ($request): void {
                match ($request->string('mentor')->toString()) {
                    'assigned' => $query->whereNotNull('users.mentor_id'),
                    'unassigned' => $query->whereNull('users.mentor_id'),
                    default => null,
                };
            })
            ->orderBy('users.name');
    }

    /**
     * @return array{total: int, active: int, licensed: int, cfm_assigned: int, new_this_month: int, with_downline: int}
     */
    private function directStats(User $user): array
    {
        return $this->memberSetStats($this->hierarchy->directRecruitsQuery($user));
    }

    /**
     * @return array{total: int, active: int, licensed: int, cfm_assigned: int, new_this_month: int, with_downline: int}
     */
    private function downlineStats(User $user): array
    {
        return $this->memberSetStats(
            $this->hierarchy->visibleMembersQuery($user)->where('users.id', '!=', $user->id)
        );
    }

    /**
     * @return array{total: int, active: int, licensed: int, cfm_assigned: int, new_this_month: int, with_downline: int}
     */
    private function memberSetStats(Builder $members): array
    {
        return [
            'total' => (clone $members)->count(),
            'active' => (clone $members)->where('users.is_active', true)->count(),
            'licensed' => (clone $members)->whereHas('profile', fn (Builder $query) => $query->whereNotNull('license_number'))->count(),
            'cfm_assigned' => (clone $members)->whereNotNull('users.mentor_id')->count(),
            'new_this_month' => (clone $members)
                ->whereMonth('users.joined_at', now()->month)
                ->whereYear('users.joined_at', now()->year)
                ->count(),
            'with_downline' => (clone $members)->has('sponsoredMembers')->count(),
        ];
    }

    private function stats(User $root, User $viewer): array
    {
        $visibleMemberIds = $this->hierarchy->visibleMembersQuery($viewer)->select('users.id');
        $members = $this->hierarchy->descendantsQuery($root, includeSelf: false)
            ->whereIn('users.id', $visibleMemberIds);
        $total = (clone $members)->count();
        $directRecruits = $this->hierarchy->directRecruitsQuery($root)
            ->whereIn('users.id', $visibleMemberIds)
            ->count();

        return [
            'total_team' => $total,
            'direct_recruits' => $directRecruits,
            'active_associates' => (clone $members)->where('users.is_active', true)->count(),
            'licensed_associates' => (clone $members)->whereHas('profile', fn (Builder $query) => $query->whereNotNull('license_number'))->count(),
            'new_this_month' => (clone $members)->whereMonth('users.joined_at', now()->month)->whereYear('users.joined_at', now()->year)->count(),
            'pending_licensing' => (clone $members)->whereDoesntHave('profile', fn (Builder $query) => $query->whereNotNull('license_number'))->count(),
            'cfm_assigned' => (clone $members)->whereNotNull('users.mentor_id')->count(),
            'cfm_unassigned' => (clone $members)->whereNull('users.mentor_id')->count(),
            'training_average' => $total > 0 ? round((clone $members)->get()->avg(fn (User $member) => $this->progressSummary($member)['training'])) : 0,
        ];
    }

    private function orgChartProfilePayload(User $member, User $viewer): array
    {
        $member->loadMissing(['profile', 'rank', 'roles', 'mentor', 'sponsor', 'team']);
        $role = $member->roles->pluck('name')->first() ?? 'member';

        return [
            ...$this->memberCard($member, $viewer),
            'role' => $role,
            'role_label' => str($role)->replace('-', ' ')->title()->toString(),
            'province' => $member->profile?->province ?? 'Not set',
            'team' => $member->team?->name ?? 'Unassigned',
            'phone' => $member->profile?->phone ?? 'Not set',
            'license_number' => $member->profile?->license_number ?? 'Not licensed yet',
            'can_see_sensitive' => $viewer->can('viewSensitive', $member),
            'active_associates' => $this->visibleDescendantsQuery($viewer, $member)->where('users.is_active', true)->count(),
            'licensed_associates' => $this->visibleDescendantsQuery($viewer, $member)
                ->whereHas('profile', fn (Builder $query) => $query->whereNotNull('license_number'))
                ->count(),
            'pending_licensing' => $this->visibleDescendantsQuery($viewer, $member)
                ->whereDoesntHave('profile', fn (Builder $query) => $query->whereNotNull('license_number'))
                ->count(),
            'member_url' => route('team.member', $member),
            'org_chart_url' => route('team.member.org-chart', $member),
            'tree_url' => route('team.member.tree', $member),
        ];
    }

    private function memberCard(User $member, ?User $viewer = null): array
    {
        $metrics = $viewer
            ? $this->hierarchy->visibleMemberMetrics($viewer, $member)
            : $this->hierarchy->memberMetrics($member);
        $production = $this->memberProfileTabs->annualPremiumTotal($member);
        $metrics['production'] = $production;
        $metrics['production_formatted'] = '$'.number_format($production);
        $progress = $this->progressSummary($member);
        $uplineTreeUrl = null;

        if ($viewer && $member->sponsor_id && $member->sponsor) {
            $uplineTreeUrl = $this->hierarchy->canViewMember($viewer, $member->sponsor)
                ? route('team.member.tree', $member->sponsor_id)
                : null;
        }

        return [
            'id' => $member->id,
            'sponsor_id' => $member->sponsor_id,
            'name' => MemberDisplayName::for($member),
            'email' => $member->email,
            'avatar' => $member->initials(),
            'profile_photo_url' => $member->profilePhotoUrl(),
            'rank' => $member->rank?->code ?? GreatwayRankCatalog::entryCode(),
            'rank_name' => $member->rank?->name ?? GreatwayRankCatalog::entryName(),
            'sponsor' => $member->sponsor?->name ?? 'None',
            'mentor' => $member->mentor?->name ?? 'Unassigned',
            'country' => $member->profile?->country ?? 'Global',
            'country_flag' => $this->countryFlag($member->profile?->country),
            'city' => $member->profile?->city ?? 'Not set',
            'timezone' => $member->profile?->timezone ?? 'Not set',
            'joined_at' => $member->joined_at?->format('M j, Y') ?? 'Not set',
            'status' => $member->is_active ? 'Active' : 'Inactive',
            'last_activity' => $member->last_login_at?->diffForHumans() ?? 'No activity yet',
            'upline_tree_url' => $uplineTreeUrl,
            'metrics' => $metrics,
            'progress' => $progress,
            'has_children' => (int) ($member->direct_recruits_count ?? 0) > 0,
        ];
    }

    private function progressSummary(User $member): array
    {
        return $this->hierarchy->progressSummary($member);
    }

    private function countryFlag(?string $country): string
    {
        return match (strtolower((string) $country)) {
            'canada', 'ca' => 'CA',
            'united states', 'usa', 'us', 'u.s.', 'u.s.a.' => 'US',
            'philippines', 'ph' => 'PH',
            'global', '' => 'GL',
            default => strtoupper(str($country)->substr(0, 2)->value() ?: 'GL'),
        };
    }

    private function visibleDescendantsQuery(User $viewer, User $root): Builder
    {
        return $this->hierarchy->descendantsQuery($root)
            ->whereIn('users.id', $this->hierarchy->visibleMembersQuery($viewer)->select('users.id'));
    }

    private function rankDistribution(User $root, User $viewer): array
    {
        return \DB::table('user_hierarchy_paths')
            ->join('users', 'users.id', '=', 'user_hierarchy_paths.descendant_id')
            ->leftJoin('ranks', 'users.rank_id', '=', 'ranks.id')
            ->where('user_hierarchy_paths.ancestor_id', $root->id)
            ->where('user_hierarchy_paths.depth', '>', 0)
            ->whereIn('user_hierarchy_paths.descendant_id', $this->hierarchy->visibleMembersQuery($viewer)->select('users.id'))
            ->whereNull('users.deleted_at')
            ->selectRaw('COALESCE(ranks.code, "TA") as label, COUNT(users.id) as total')
            ->groupBy('label')
            ->orderByDesc('total')
            ->pluck('total', 'label')
            ->all();
    }

    private function countryDistribution(User $root, User $viewer): array
    {
        return \DB::table('user_hierarchy_paths')
            ->join('users', 'users.id', '=', 'user_hierarchy_paths.descendant_id')
            ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
            ->leftJoin('countries', 'countries.id', '=', 'profiles.country_id')
            ->where('user_hierarchy_paths.ancestor_id', $root->id)
            ->where('user_hierarchy_paths.depth', '>', 0)
            ->whereIn('user_hierarchy_paths.descendant_id', $this->hierarchy->visibleMembersQuery($viewer)->select('users.id'))
            ->whereNull('users.deleted_at')
            ->selectRaw('COALESCE(countries.name, "Global") as label, COUNT(users.id) as total')
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(6)
            ->pluck('total', 'label')
            ->all();
    }

    private function filterOptions(User $viewer): array
    {
        $countryQuery = $viewer->hasAnyRole(['super-admin', 'admin']) || $viewer->hasPermissionTo('view all teams')
            ? \DB::table('users')
            : \DB::table('user_hierarchy_paths')
                ->join('users', 'users.id', '=', 'user_hierarchy_paths.descendant_id')
                ->where('user_hierarchy_paths.ancestor_id', $viewer->id)
                ->when(
                    $viewer->hasPermissionTo('view direct downline') && ! $viewer->hasPermissionTo('view full downline'),
                    fn ($query) => $query->where('user_hierarchy_paths.depth', '<=', 1)
                );

        return [
            'ranks' => \DB::table('ranks')->whereNull('deleted_at')->where('is_active', true)->orderBy('sort_order')->get(['id', 'code', 'name']),
            'countries' => $countryQuery
                ->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
                ->leftJoin('countries', 'countries.id', '=', 'profiles.country_id')
                ->whereNotNull('profiles.country_id')
                ->whereNull('users.deleted_at')
                ->distinct()
                ->orderBy('countries.name')
                ->pluck('countries.name'),
        ];
    }
}
