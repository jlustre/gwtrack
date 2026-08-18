<?php

namespace App\Services;

use App\Models\MemberProductionEntry;
use App\Models\User;
use App\Services\Goals\GoalProductionService;
use App\Support\MemberDisplayName;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AgencyProductionDashboardService
{
    public function __construct(
        private readonly DownlineHierarchyService $hierarchy,
        private readonly GoalProductionService $goalProduction,
        private readonly MemberProfileTabsService $memberProfileTabs,
    ) {}

    public function resolveMember(User $viewer, ?int $memberId = null): User
    {
        if ($memberId === null || $memberId === $viewer->id) {
            return $viewer->loadMissing(['rank', 'profile']);
        }

        $member = User::query()
            ->with(['rank', 'profile'])
            ->findOrFail($memberId);

        abort_unless($this->canViewMember($viewer, $member), 403);

        return $member;
    }

    public function canViewMember(User $viewer, User $member): bool
    {
        if ($viewer->id === $member->id) {
            return true;
        }

        if ($this->canViewTeamDashboard($viewer)) {
            return $this->hierarchy->canViewMember($viewer, $member);
        }

        return false;
    }

    public function canViewTeamDashboard(User $viewer): bool
    {
        return $viewer->can('view own team');
    }

    /**
     * @return array<string, mixed>
     */
    public function dashboardFor(User $viewer, User $member, string $period = 'ytd', ?int $year = null): array
    {
        [$start, $end] = $this->resolvePeriodRange($period, $year);
        $isLeadership = $this->canViewTeamDashboard($viewer);
        $isSelf = $viewer->id === $member->id;
        $showTeamScope = $isLeadership && $isSelf;

        $personalProduction = $this->goalProduction->totalForUser($member, $start, $end);
        $personalEntries = $this->entryCountForUser($member, $start, $end);
        $creditedProduction = $this->memberProfileTabs->annualPremiumTotal($member);

        $teamProduction = null;
        $teamEntries = null;
        $activeProducers = null;
        $topProducers = [];
        $memberBreakdown = [];
        $teamMemberOptions = [];

        if ($showTeamScope) {
            $teamProduction = $this->goalProduction->teamTotalForUser($viewer, $start, $end);
            $memberIds = $this->hierarchy->dashboardMembersQuery($viewer)->pluck('users.id');
            $teamEntries = $this->entryCountForMembers($memberIds, $start, $end);
            $activeProducers = $this->activeProducerCount($memberIds, $start, $end);
            $topProducers = $this->topProducers($memberIds, $start, $end);
            $memberBreakdown = $this->memberBreakdown($viewer, $memberIds, $start, $end);
            $teamMemberOptions = $this->teamMemberOptions($viewer);
        }

        return [
            'member' => [
                'id' => $member->id,
                'name' => MemberDisplayName::for($member),
                'rank' => $member->rank?->name,
            ],
            'is_self' => $isSelf,
            'is_leadership_view' => $isLeadership,
            'show_team_scope' => $showTeamScope,
            'period' => $period,
            'period_label' => config("production-dashboard.periods.{$period}", 'Year to date'),
            'year' => $year ?? now()->year,
            'range' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'label' => $start->format('M j, Y').' – '.$end->format('M j, Y'),
            ],
            'stats' => [
                'personal_production' => round($personalProduction, 2),
                'personal_production_formatted' => $this->formatCurrency($personalProduction),
                'team_production' => $teamProduction !== null ? round($teamProduction, 2) : null,
                'team_production_formatted' => $teamProduction !== null ? $this->formatCurrency($teamProduction) : null,
                'personal_entry_count' => $personalEntries,
                'team_entry_count' => $teamEntries,
                'active_producers' => $activeProducers,
                'credited_production' => $creditedProduction,
                'credited_production_formatted' => $this->formatCurrency($creditedProduction),
            ],
            'top_producers' => $topProducers,
            'monthly_trend' => $this->monthlyTrend(
                $showTeamScope ? $this->hierarchy->dashboardMembersQuery($viewer)->pluck('users.id') : collect([$member->id]),
                $end,
            ),
            'member_breakdown' => $memberBreakdown,
            'recent_entries' => $this->recentEntries(
                $showTeamScope ? $this->hierarchy->dashboardMembersQuery($viewer)->pluck('users.id') : collect([$member->id]),
                $start,
                $end,
            ),
            'team_member_options' => $teamMemberOptions,
            'periods' => config('production-dashboard.periods', []),
            'profile_url' => $isSelf
                ? route('profile.edit')
                : route('team.member.profile', $member),
            'team_dashboard_url' => route('team.index'),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolvePeriodRange(string $period, ?int $year = null): array
    {
        $year = $year ?? now()->year;

        return match ($period) {
            'month' => [now()->copy()->startOfMonth()->startOfDay(), now()->copy()->endOfMonth()->endOfDay()],
            'quarter' => [now()->copy()->startOfQuarter()->startOfDay(), now()->copy()->endOfQuarter()->endOfDay()],
            'year' => [
                Carbon::create($year)->startOfYear()->startOfDay(),
                Carbon::create($year)->endOfYear()->endOfDay(),
            ],
            default => [now()->copy()->startOfYear()->startOfDay(), now()->copy()->endOfDay()],
        };
    }

    private function postedWithinPeriod(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query
            ->whereDate('posted_at', '>=', $start)
            ->whereDate('posted_at', '<=', $end);
    }

    /**
     * @param  Collection<int, int|string>  $memberIds
     * @return list<array<string, mixed>>
     */
    private function topProducers(Collection $memberIds, Carbon $start, Carbon $end): array
    {
        if ($memberIds->isEmpty()) {
            return [];
        }

        $limit = (int) config('production-dashboard.top_producers_limit', 10);

        $rows = $this->postedWithinPeriod(
            MemberProductionEntry::query()
                ->select('user_id', DB::raw('SUM(annual_premium) as total_premium'), DB::raw('COUNT(*) as entry_count'))
                ->whereIn('user_id', $memberIds)
                ->where('status', 'posted'),
            $start,
            $end,
        )
            ->groupBy('user_id')
            ->orderByDesc('total_premium')
            ->limit($limit)
            ->get();

        $users = User::query()
            ->with('rank')
            ->whereIn('id', $rows->pluck('user_id'))
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($users): array {
            $user = $users->get($row->user_id);

            return [
                'user_id' => (int) $row->user_id,
                'name' => $user ? MemberDisplayName::for($user) : 'Unknown member',
                'rank' => $user?->rank?->name,
                'total' => round((float) $row->total_premium, 2),
                'total_formatted' => $this->formatCurrency((float) $row->total_premium),
                'entry_count' => (int) $row->entry_count,
                'profile_url' => $user ? route('team.member.profile', $user) : null,
                'drilldown_url' => $user ? route('team.production', ['member' => $user->id]) : null,
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, int|string>  $memberIds
     * @return list<array<string, mixed>>
     */
    private function memberBreakdown(User $viewer, Collection $memberIds, Carbon $start, Carbon $end): array
    {
        if ($memberIds->isEmpty()) {
            return [];
        }

        $limit = (int) config('production-dashboard.member_breakdown_limit', 50);

        $totals = $this->postedWithinPeriod(
            MemberProductionEntry::query()
                ->select('user_id', DB::raw('SUM(annual_premium) as total_premium'), DB::raw('COUNT(*) as entry_count'))
                ->whereIn('user_id', $memberIds)
                ->where('status', 'posted'),
            $start,
            $end,
        )
            ->groupBy('user_id')
            ->orderByDesc('total_premium')
            ->limit($limit)
            ->get()
            ->keyBy('user_id');

        $members = User::query()
            ->with(['rank', 'profile'])
            ->whereIn('id', $memberIds)
            ->orderBy('name')
            ->get();

        return $members
            ->map(function (User $member) use ($totals, $viewer): array {
                $row = $totals->get($member->id);
                $total = $row ? (float) $row->total_premium : 0.0;

                return [
                    'user_id' => $member->id,
                    'name' => MemberDisplayName::for($member),
                    'rank' => $member->rank?->name,
                    'depth' => $this->hierarchyDepth($viewer, $member),
                    'total' => round($total, 2),
                    'total_formatted' => $this->formatCurrency($total),
                    'entry_count' => $row ? (int) $row->entry_count : 0,
                    'credited_ytd' => $this->memberProfileTabs->annualPremiumTotal($member),
                    'credited_ytd_formatted' => $this->formatCurrency($this->memberProfileTabs->annualPremiumTotal($member)),
                    'profile_url' => route('team.member.profile', $member),
                    'drilldown_url' => route('team.production', ['member' => $member->id]),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, int|string>  $memberIds
     * @return list<array<string, mixed>>
     */
    private function monthlyTrend(Collection $memberIds, Carbon $end): array
    {
        $months = (int) config('production-dashboard.trend_months', 12);
        $start = $end->copy()->startOfMonth()->subMonths($months - 1);

        if ($memberIds->isEmpty()) {
            return [];
        }

        $entries = $this->postedWithinPeriod(
            MemberProductionEntry::query()
                ->whereIn('user_id', $memberIds)
                ->where('status', 'posted'),
            $start,
            $end,
        )
            ->get(['posted_at', 'annual_premium']);

        $grouped = $entries->groupBy(fn (MemberProductionEntry $entry): string => $entry->posted_at->format('Y-m'));

        $points = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $start->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $monthEntries = $grouped->get($key, collect());
            $total = (float) $monthEntries->sum('annual_premium');

            $points[] = [
                'month_key' => $key,
                'label' => $month->format('M Y'),
                'total' => round($total, 2),
                'total_formatted' => $this->formatCurrency($total),
                'entry_count' => $monthEntries->count(),
            ];
        }

        return $points;
    }

    /**
     * @param  Collection<int, int|string>  $memberIds
     * @return list<array<string, mixed>>
     */
    private function recentEntries(Collection $memberIds, Carbon $start, Carbon $end): array
    {
        if ($memberIds->isEmpty()) {
            return [];
        }

        $limit = (int) config('production-dashboard.recent_entries_limit', 15);

        return $this->postedWithinPeriod(
            MemberProductionEntry::query()
                ->with(['user.rank'])
                ->whereIn('user_id', $memberIds)
                ->where('status', 'posted'),
            $start,
            $end,
        )
            ->orderByDesc('posted_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (MemberProductionEntry $entry): array => [
                'id' => $entry->id,
                'member_name' => MemberDisplayName::for($entry->user),
                'member_id' => $entry->user_id,
                'description' => $entry->description ?: ($entry->policy_reference ?: 'Production entry'),
                'policy_reference' => $entry->policy_reference,
                'annual_premium' => round((float) $entry->annual_premium, 2),
                'annual_premium_formatted' => $this->formatCurrency((float) $entry->annual_premium),
                'posted_at' => $entry->posted_at?->format('M j, Y') ?? '—',
                'source' => $entry->source,
                'profile_url' => route('team.member.profile', $entry->user_id),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function teamMemberOptions(User $viewer): array
    {
        return $this->hierarchy->dashboardMembersQuery($viewer)
            ->with('profile')
            ->orderBy('users.name')
            ->get(['users.id', 'users.name'])
            ->map(fn (User $member): array => [
                'id' => $member->id,
                'name' => MemberDisplayName::for($member),
            ])
            ->values()
            ->all();
    }

    private function entryCountForUser(User $user, Carbon $start, Carbon $end): int
    {
        return $this->goalProduction->entryCountForUser($user, $start, $end);
    }

    /**
     * @param  Collection<int, int|string>  $memberIds
     */
    private function entryCountForMembers(Collection $memberIds, Carbon $start, Carbon $end): int
    {
        if ($memberIds->isEmpty()) {
            return 0;
        }

        return $this->postedWithinPeriod(
            MemberProductionEntry::query()
                ->whereIn('user_id', $memberIds)
                ->where('status', 'posted'),
            $start,
            $end,
        )->count();
    }

    /**
     * @param  Collection<int, int|string>  $memberIds
     */
    private function activeProducerCount(Collection $memberIds, Carbon $start, Carbon $end): int
    {
        if ($memberIds->isEmpty()) {
            return 0;
        }

        return $this->postedWithinPeriod(
            MemberProductionEntry::query()
                ->whereIn('user_id', $memberIds)
                ->where('status', 'posted'),
            $start,
            $end,
        )->distinct('user_id')->count('user_id');
    }

    private function hierarchyDepth(User $viewer, User $member): ?int
    {
        if ($viewer->id === $member->id) {
            return 0;
        }

        $path = DB::table('user_hierarchy_paths')
            ->where('ancestor_id', $viewer->id)
            ->where('descendant_id', $member->id)
            ->value('depth');

        return $path !== null ? (int) $path : null;
    }

    private function formatCurrency(float|int $amount): string
    {
        return '$'.number_format((float) $amount, 0);
    }
}
