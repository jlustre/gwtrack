<?php

namespace App\Services\Prospects;

use App\Models\Prospect;
use App\Models\ProspectGoal;
use App\Models\ProspectGoalSnapshot;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProspectAnalyticsService
{
    public function __construct(
        private readonly DownlineHierarchyService $hierarchy,
    ) {}

    /**
     * @return array{
     *     total: int,
     *     new_30d: int,
     *     hot: int,
     *     followups_due: int,
     *     appointments_upcoming: int,
     *     conversion_rate: int,
     *     insurance_count: int,
     *     recruiting_count: int
     * }
     */
    /**
     * @return array{
     *     total: int,
     *     hot: int,
     *     followups_due: int,
     *     appointments: int,
     *     shared_with_me: int,
     *     shared_by_me: int,
     *     converted: int,
     *     conversion_rate: int
     * }
     */
    public function dashboardStatsFor(User $user): array
    {
        $ownProspects = Prospect::query()
            ->where('owner_id', $user->id)
            ->whereNull('deleted_at')
            ->forSalesPipeline();

        $activeProspects = (clone $ownProspects)
            ->where('status', 'active')
            ->where('is_archived', false);

        $allOwned = (clone $ownProspects)->count();
        $converted = (clone $ownProspects)->whereNotNull('converted_to')->count();

        return [
            'total' => $activeProspects->count(),
            'hot' => (clone $activeProspects)->where('interest_level', 'hot')->count(),
            'followups_due' => DB::table('prospect_followups')
                ->join('prospects', 'prospects.id', '=', 'prospect_followups.prospect_id')
                ->where('prospect_followups.assigned_user_id', $user->id)
                ->whereIn('prospects.funnel_type', config('prospects.sales_funnel_types', ['insurance', 'both']))
                ->whereIn('prospect_followups.status', ['pending', 'overdue'])
                ->whereDate('prospect_followups.due_at', '<=', now()->toDateString())
                ->whereNull('prospect_followups.deleted_at')
                ->whereNull('prospects.deleted_at')
                ->count(),
            'appointments' => DB::table('prospect_appointments')
                ->join('prospects', 'prospects.id', '=', 'prospect_appointments.prospect_id')
                ->where('prospect_appointments.owner_id', $user->id)
                ->whereIn('prospects.funnel_type', config('prospects.sales_funnel_types', ['insurance', 'both']))
                ->where('prospect_appointments.status', 'scheduled')
                ->where('prospect_appointments.scheduled_at', '>=', now())
                ->whereNull('prospect_appointments.deleted_at')
                ->whereNull('prospects.deleted_at')
                ->count(),
            'shared_with_me' => $this->activeSharesWithUserCount($user),
            'shared_by_me' => DB::table('prospect_shares')
                ->where('granted_by', $user->id)
                ->where('status', 'active')
                ->whereNull('revoked_at')
                ->whereNull('deleted_at')
                ->count(),
            'converted' => $converted,
            'conversion_rate' => $allOwned > 0 ? (int) round(($converted / $allOwned) * 100) : 0,
        ];
    }

    public function summaryFor(User $user): array
    {
        $active = $this->activeProspectsQuery($user);
        $total = (clone $active)->count();
        $allOwned = Prospect::query()
            ->where('owner_id', $user->id)
            ->whereNull('deleted_at');

        $converted = (clone $allOwned)->whereNotNull('converted_to')->count();

        return [
            'total' => $total,
            'new_30d' => (clone $allOwned)->where('created_at', '>=', now()->subDays(30))->count(),
            'hot' => (clone $active)->where('interest_level', 'hot')->count(),
            'followups_due' => $this->followupsDueCount($user),
            'appointments_upcoming' => DB::table('prospect_appointments')
                ->where('owner_id', $user->id)
                ->where('status', 'scheduled')
                ->where('scheduled_at', '>=', now())
                ->whereNull('deleted_at')
                ->count(),
            'conversion_rate' => $total > 0 ? (int) round(($converted / $total) * 100) : 0,
            'insurance_count' => (clone $active)->whereIn('funnel_type', ['insurance', 'both'])->count(),
            'recruiting_count' => (clone $active)->whereIn('funnel_type', ['recruiting', 'both'])->count(),
        ];
    }

    /**
     * @return array{funnel: string, stages: list<array{stage_id: int, name: string, slug: string, count: int, drop_off: int}>, max_count: int}
     */
    public function funnelConversion(User $user, ?string $funnelType = null): array
    {
        $funnelKey = $funnelType ?? 'insurance';
        $funnelId = DB::table('prospect_funnels')->where('key', $funnelKey)->value('id');

        if (! $funnelId) {
            return ['funnel' => $funnelKey, 'stages' => [], 'max_count' => 1];
        }

        $stages = DB::table('prospect_funnel_stages')
            ->join('pipeline_stages', 'pipeline_stages.id', '=', 'prospect_funnel_stages.pipeline_stage_id')
            ->where('prospect_funnel_stages.prospect_funnel_id', $funnelId)
            ->orderBy('prospect_funnel_stages.sort_order')
            ->get([
                'pipeline_stages.id',
                'pipeline_stages.name',
                'pipeline_stages.slug',
            ]);

        $prospectCounts = DB::table('prospects')
            ->where('owner_id', $user->id)
            ->where('prospect_funnel_id', $funnelId)
            ->where('status', 'active')
            ->where('is_archived', false)
            ->whereNull('deleted_at')
            ->when($funnelType !== null, fn ($query) => $query->where('funnel_type', $funnelType))
            ->groupBy('pipeline_stage_id')
            ->select('pipeline_stage_id', DB::raw('COUNT(*) as count'))
            ->pluck('count', 'pipeline_stage_id');

        $result = [];
        $previousCount = null;

        foreach ($stages as $stage) {
            $count = (int) ($prospectCounts[$stage->id] ?? 0);
            $dropOff = 0;

            if ($previousCount !== null && $previousCount > 0) {
                $dropOff = (int) max(0, round((($previousCount - $count) / $previousCount) * 100));
            }

            $result[] = [
                'stage_id' => (int) $stage->id,
                'name' => $stage->name,
                'slug' => $stage->slug,
                'count' => $count,
                'drop_off' => $dropOff,
            ];

            $previousCount = $count;
        }

        return [
            'funnel' => $funnelKey,
            'stages' => $result,
            'max_count' => max(1, collect($result)->max('count') ?? 1),
        ];
    }

    /**
     * @return list<array{source: string, count: int}>
     */
    public function leadSourceBreakdown(User $user): array
    {
        $rows = DB::table('prospect_sources')
            ->leftJoin('prospects', function ($join) use ($user): void {
                $join->on('prospects.prospect_source_id', '=', 'prospect_sources.id')
                    ->where('prospects.owner_id', '=', $user->id)
                    ->whereNull('prospects.deleted_at');
            })
            ->where('prospect_sources.is_active', true)
            ->groupBy('prospect_sources.id', 'prospect_sources.name')
            ->orderByDesc(DB::raw('COUNT(prospects.id)'))
            ->get([
                'prospect_sources.name as source',
                DB::raw('COUNT(prospects.id) as count'),
            ]);

        return $rows->map(fn ($row) => [
            'source' => $row->source,
            'count' => (int) $row->count,
        ])->all();
    }

    /**
     * @return list<array{month: string, label: string, communications: int, activities: int, appointments: int, total: int}>
     */
    public function monthlyActivityTrend(User $user, int $months = 6): array
    {
        $prospectIds = $this->ownedProspectIds($user);
        $result = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = now()->subMonths($i)->endOfMonth();

            $communications = $this->countCommunications($prospectIds, $user->id, $start, $end);
            $activities = $this->countActivities($prospectIds, $user->id, $start, $end);
            $appointments = $this->countAppointments($user->id, $start, $end);

            $result[] = [
                'month' => $start->format('Y-m'),
                'label' => $start->format('M Y'),
                'communications' => $communications,
                'activities' => $activities,
                'appointments' => $appointments,
                'total' => $communications + $activities + $appointments,
            ];
        }

        return $result;
    }

    /**
     * @return list<array{month: string, label: string, new_count: int, cumulative: int}>
     */
    public function prospectGrowth(User $user, int $months = 6): array
    {
        $result = [];
        $rangeStart = now()->subMonths($months - 1)->startOfMonth();
        $cumulative = Prospect::query()
            ->where('owner_id', $user->id)
            ->where('created_at', '<', $rangeStart)
            ->whereNull('deleted_at')
            ->count();

        for ($i = $months - 1; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = now()->subMonths($i)->endOfMonth();
            $newCount = Prospect::query()
                ->where('owner_id', $user->id)
                ->whereBetween('created_at', [$start, $end])
                ->whereNull('deleted_at')
                ->count();
            $cumulative += $newCount;

            $result[] = [
                'month' => $start->format('Y-m'),
                'label' => $start->format('M Y'),
                'new_count' => $newCount,
                'cumulative' => $cumulative,
            ];
        }

        return $result;
    }

    /**
     * @return array{
     *     insurance: list<array{stage: string, count: int}>,
     *     recruiting: list<array{stage: string, count: int}>,
     *     max_count: int
     * }
     */
    public function dualPipelineComparison(User $user): array
    {
        $insurance = $this->funnelConversion($user, 'insurance');
        $recruiting = $this->funnelConversion($user, 'recruiting');

        $insuranceStages = collect($insurance['stages'])->map(fn (array $stage) => [
            'stage' => $stage['name'],
            'count' => $stage['count'],
        ])->all();

        $recruitingStages = collect($recruiting['stages'])->map(fn (array $stage) => [
            'stage' => $stage['name'],
            'count' => $stage['count'],
        ])->all();

        $maxCount = max(
            1,
            collect($insuranceStages)->max('count') ?? 0,
            collect($recruitingStages)->max('count') ?? 0,
        );

        return [
            'insurance' => $insuranceStages,
            'recruiting' => $recruitingStages,
            'max_count' => $maxCount,
        ];
    }

    /**
     * @return array{
     *     appointment: array<string, int>,
     *     follow_up_sla: array<string, int>,
     *     source_conversion: list<array{source: string, prospects: int, converted: int, conversion_rate: int}>,
     *     team_rollup: list<array{user_id: int, name: string, prospects: int, conversions: int, appointments: int, completed_followups: int}>
     * }
     */
    public function performanceReport(User $user): array
    {
        return [
            'appointment' => $this->appointmentOutcomeReport($user),
            'follow_up_sla' => $this->followUpSlaReport($user),
            'source_conversion' => $this->leadSourceConversionReport($user),
            'team_rollup' => $this->teamRollupReport($user),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function appointmentOutcomeReport(User $user): array
    {
        $rows = DB::table('prospect_appointments')
            ->leftJoin('calendar_events', 'calendar_events.id', '=', 'prospect_appointments.calendar_event_id')
            ->where('prospect_appointments.owner_id', $user->id)
            ->whereNull('prospect_appointments.deleted_at')
            ->where('prospect_appointments.scheduled_at', '>=', now()->subDays(90))
            ->selectRaw('prospect_appointments.status, COUNT(*) as total')
            ->groupBy('prospect_appointments.status')
            ->pluck('total', 'status');

        $total = (int) $rows->sum();
        $completed = (int) ($rows['completed'] ?? 0);
        $cancelled = (int) ($rows['cancelled'] ?? 0);
        $noShow = (int) ($rows['no_show'] ?? 0);
        $scheduled = (int) ($rows['scheduled'] ?? 0);
        $linked = DB::table('prospect_appointments')
            ->where('owner_id', $user->id)
            ->whereNotNull('calendar_event_id')
            ->whereNull('deleted_at')
            ->where('scheduled_at', '>=', now()->subDays(90))
            ->count();

        $attendanceBase = $completed + $noShow;

        return [
            'total' => $total,
            'scheduled' => $scheduled,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'no_show' => $noShow,
            'calendar_linked' => (int) $linked,
            'completion_rate' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
            'attendance_rate' => $attendanceBase > 0 ? (int) round(($completed / $attendanceBase) * 100) : 0,
            'cancellation_rate' => $total > 0 ? (int) round(($cancelled / $total) * 100) : 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function followUpSlaReport(User $user): array
    {
        $base = DB::table('prospect_followups')
            ->where('assigned_user_id', $user->id)
            ->whereNull('deleted_at')
            ->where('due_at', '>=', now()->subDays(90));

        $total = (clone $base)->count();
        $completed = (clone $base)->where('status', 'completed')->count();
        $onTime = (clone $base)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereColumn('completed_at', '<=', 'due_at')
            ->count();
        $late = (clone $base)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->whereColumn('completed_at', '>', 'due_at')
            ->count();
        $overdueOpen = (clone $base)
            ->whereIn('status', ['pending', 'overdue'])
            ->where('due_at', '<', now())
            ->count();
        $pendingFuture = (clone $base)
            ->whereIn('status', ['pending', 'overdue'])
            ->where('due_at', '>=', now())
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'on_time' => $onTime,
            'late' => $late,
            'overdue_open' => $overdueOpen,
            'pending_future' => $pendingFuture,
            'on_time_rate' => $completed > 0 ? (int) round(($onTime / $completed) * 100) : 0,
            'completion_rate' => $total > 0 ? (int) round(($completed / $total) * 100) : 0,
        ];
    }

    /**
     * @return list<array{source: string, prospects: int, converted: int, conversion_rate: int}>
     */
    public function leadSourceConversionReport(User $user): array
    {
        return DB::table('prospect_sources')
            ->leftJoin('prospects', function ($join) use ($user): void {
                $join->on('prospects.prospect_source_id', '=', 'prospect_sources.id')
                    ->where('prospects.owner_id', '=', $user->id)
                    ->whereNull('prospects.deleted_at');
            })
            ->where('prospect_sources.is_active', true)
            ->groupBy('prospect_sources.id', 'prospect_sources.name')
            ->orderByDesc(DB::raw('COUNT(prospects.id)'))
            ->limit(8)
            ->get([
                'prospect_sources.name as source',
                DB::raw('COUNT(prospects.id) as prospects'),
                DB::raw('SUM(CASE WHEN prospects.converted_to IS NOT NULL THEN 1 ELSE 0 END) as converted'),
            ])
            ->map(function ($row): array {
                $prospects = (int) $row->prospects;
                $converted = (int) $row->converted;

                return [
                    'source' => $row->source,
                    'prospects' => $prospects,
                    'converted' => $converted,
                    'conversion_rate' => $prospects > 0 ? (int) round(($converted / $prospects) * 100) : 0,
                ];
            })
            ->all();
    }

    /**
     * @return list<array{user_id: int, name: string, prospects: int, conversions: int, appointments: int, completed_followups: int}>
     */
    public function teamRollupReport(User $viewer): array
    {
        $memberIds = $this->hierarchy->visibleMembersQuery($viewer)
            ->pluck('users.id')
            ->map(fn ($id): int => (int) $id);

        if ($memberIds->isEmpty()) {
            $memberIds = collect([(int) $viewer->id]);
        }

        $prospectCounts = DB::table('prospects')
            ->whereIn('owner_id', $memberIds)
            ->whereNull('deleted_at')
            ->groupBy('owner_id')
            ->select('owner_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'owner_id');

        $conversionCounts = DB::table('prospects')
            ->whereIn('owner_id', $memberIds)
            ->whereNotNull('converted_to')
            ->whereNull('deleted_at')
            ->groupBy('owner_id')
            ->select('owner_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'owner_id');

        $appointmentCounts = DB::table('prospect_appointments')
            ->whereIn('owner_id', $memberIds)
            ->whereNull('deleted_at')
            ->where('scheduled_at', '>=', now()->subDays(90))
            ->groupBy('owner_id')
            ->select('owner_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'owner_id');

        $followUpCounts = DB::table('prospect_followups')
            ->whereIn('assigned_user_id', $memberIds)
            ->where('status', 'completed')
            ->whereNull('deleted_at')
            ->where('due_at', '>=', now()->subDays(90))
            ->groupBy('assigned_user_id')
            ->select('assigned_user_id', DB::raw('COUNT(*) as total'))
            ->pluck('total', 'assigned_user_id');

        return User::query()
            ->whereIn('id', $memberIds)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $member): array => [
                'user_id' => (int) $member->id,
                'name' => $member->name,
                'prospects' => (int) ($prospectCounts[$member->id] ?? 0),
                'conversions' => (int) ($conversionCounts[$member->id] ?? 0),
                'appointments' => (int) ($appointmentCounts[$member->id] ?? 0),
                'completed_followups' => (int) ($followUpCounts[$member->id] ?? 0),
            ])
            ->sortByDesc(fn (array $row): int => $row['prospects'] + $row['appointments'] + $row['completed_followups'])
            ->take(6)
            ->values()
            ->all();
    }

    public function refreshGoalActuals(User $user, ?ProspectGoal $goal = null): void
    {
        $goals = $goal !== null
            ? collect([$goal])
            : ProspectGoal::query()->where('user_id', $user->id)->get();

        foreach ($goals as $periodGoal) {
            $actual = $this->computeMetricValue(
                $user,
                $periodGoal->metric_key,
                $periodGoal->period_start->startOfDay(),
                $periodGoal->period_end->endOfDay(),
            );

            $periodGoal->update(['actual_value' => $actual]);
        }
    }

    /**
     * @return array{
     *     visible: bool,
     *     member_count?: int,
     *     total_prospects?: int,
     *     hot_prospects?: int,
     *     followups_due?: int,
     *     avg_conversion_rate?: int
     * }
     */
    public function teamAggregates(User $viewer): array
    {
        $hasDownline = $this->hierarchy->descendantsQuery($viewer)->exists();
        $isManager = $viewer->hasAnyRole(['team-leader', 'agency-owner', 'admin', 'super-admin']);

        if (! $hasDownline && ! $isManager) {
            return ['visible' => false];
        }

        $memberIds = $this->hierarchy->visibleMembersQuery($viewer)
            ->where('users.id', '!=', $viewer->id)
            ->pluck('users.id');

        if ($memberIds->isEmpty()) {
            return ['visible' => false];
        }

        $totalProspects = DB::table('prospects')
            ->whereIn('owner_id', $memberIds)
            ->where('status', 'active')
            ->where('is_archived', false)
            ->whereNull('deleted_at')
            ->count();

        $hotProspects = DB::table('prospects')
            ->whereIn('owner_id', $memberIds)
            ->where('status', 'active')
            ->where('is_archived', false)
            ->where('interest_level', 'hot')
            ->whereNull('deleted_at')
            ->count();

        $followupsDue = DB::table('prospect_followups')
            ->whereIn('assigned_user_id', $memberIds)
            ->whereIn('status', ['pending', 'overdue'])
            ->whereDate('due_at', '<=', now()->toDateString())
            ->whereNull('deleted_at')
            ->count();

        $totalOwned = DB::table('prospects')
            ->whereIn('owner_id', $memberIds)
            ->whereNull('deleted_at')
            ->count();

        $converted = DB::table('prospects')
            ->whereIn('owner_id', $memberIds)
            ->whereNotNull('converted_to')
            ->whereNull('deleted_at')
            ->count();

        return [
            'visible' => true,
            'member_count' => $memberIds->count(),
            'total_prospects' => $totalProspects,
            'hot_prospects' => $hotProspects,
            'followups_due' => $followupsDue,
            'avg_conversion_rate' => $totalOwned > 0 ? (int) round(($converted / $totalOwned) * 100) : 0,
        ];
    }

    public function followupsDueCount(User $user): int
    {
        return DB::table('prospect_followups')
            ->where('assigned_user_id', $user->id)
            ->whereIn('status', ['pending', 'overdue'])
            ->whereDate('due_at', '<=', now()->toDateString())
            ->whereNull('deleted_at')
            ->count();
    }

    public function hotProspectCount(User $user): int
    {
        return $this->activeProspectsQuery($user)
            ->where('interest_level', 'hot')
            ->count();
    }

    public function prospectConversionRate(User $user): int
    {
        $active = $this->activeProspectsQuery($user)->count();
        $converted = Prospect::query()
            ->where('owner_id', $user->id)
            ->whereNotNull('converted_to')
            ->whereNull('deleted_at')
            ->count();

        return $active > 0 ? (int) round(($converted / $active) * 100) : 0;
    }

    /**
     * @return array{start: CarbonInterface, end: CarbonInterface}
     */
    public function periodBounds(string $periodType, ?CarbonInterface $reference = null): array
    {
        $ref = $reference ? Carbon::parse($reference) : now();

        return match ($periodType) {
            'weekly' => [
                'start' => $ref->copy()->startOfWeek(),
                'end' => $ref->copy()->endOfWeek(),
            ],
            'quarterly' => [
                'start' => $ref->copy()->firstOfQuarter(),
                'end' => $ref->copy()->lastOfQuarter(),
            ],
            default => [
                'start' => $ref->copy()->startOfMonth(),
                'end' => $ref->copy()->endOfMonth(),
            ],
        };
    }

    /**
     * @return list<string>
     */
    public function snapshotMetricKeys(): array
    {
        return array_keys(config('prospects.goal_metrics', []));
    }

    public function writeDailySnapshots(User $user, ?CarbonInterface $date = null): void
    {
        $snapshotDate = ($date ? Carbon::parse($date) : now())->toDateString();
        $start = Carbon::parse($snapshotDate)->startOfDay();
        $end = Carbon::parse($snapshotDate)->endOfDay();

        foreach ($this->snapshotMetricKeys() as $metricKey) {
            $value = $this->computeMetricValue($user, $metricKey, $start, $end);

            ProspectGoalSnapshot::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'snapshot_date' => $snapshotDate,
                    'metric_key' => $metricKey,
                ],
                ['value' => $value],
            );
        }
    }

    public function computeMetricValue(User $user, string $metricKey, CarbonInterface $start, CarbonInterface $end): int
    {
        $prospectIds = $this->ownedProspectIds($user);
        $manual = $this->manualActivityTally($user, $metricKey, $start, $end);

        $system = match ($metricKey) {
            'contacts' => $this->countCommunications($prospectIds, $user->id, $start, $end)
                + $this->countActivities($prospectIds, $user->id, $start, $end),
            'appointments' => $this->countAppointments($user->id, $start, $end),
            'presentations' => $this->countPresentations($prospectIds, $user->id, $start, $end),
            'applications' => $this->countApplications($prospectIds, $start, $end),
            'recruits' => $this->countRecruits($prospectIds, $start, $end),
            'registrations' => $this->countRegistrations($user, $prospectIds, $start, $end),
            'invitations_sent' => $this->countInvitationsSent($user, $start, $end),
            'new_prospects' => $this->countNewProspects($user, $start, $end),
            'followups_completed' => $this->countCompletedFollowups($user->id, $start, $end),
            'presentation_invitations', 'fna_started' => 0,
            default => 0,
        };

        return max(0, $system + $manual);
    }

    private function manualActivityTally(User $user, string $metricKey, CarbonInterface $start, CarbonInterface $end): int
    {
        if (! in_array($metricKey, [
            'contacts',
            'followups_completed',
            'presentation_invitations',
            'presentations',
            'fna_started',
        ], true)) {
            return 0;
        }

        return (int) round((float) DB::table('goal_activity_logs')
            ->where('user_id', $user->id)
            ->where('activity_key', $metricKey)
            ->where('source', 'manual')
            ->whereDate('logged_for_date', '>=', $start->toDateString())
            ->whereDate('logged_for_date', '<=', $end->toDateString())
            ->sum('value'));
    }

    private function countCompletedFollowups(int $userId, CarbonInterface $start, CarbonInterface $end): int
    {
        return (int) DB::table('prospect_followups')
            ->where('assigned_user_id', $userId)
            ->where('status', 'completed')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($start, $end): void {
                $query
                    ->whereBetween('completed_at', [$start, $end])
                    ->orWhere(function ($query) use ($start, $end): void {
                        $query
                            ->whereNull('completed_at')
                            ->whereBetween('updated_at', [$start, $end]);
                    });
            })
            ->count();
    }

    private function countNewProspects(User $user, CarbonInterface $start, CarbonInterface $end): int
    {
        if ($start->isSameDay($end)) {
            return Prospect::query()
                ->where('owner_id', $user->id)
                ->whereDate('created_at', $start->toDateString())
                ->whereNull('deleted_at')
                ->count();
        }

        return Prospect::query()
            ->where('owner_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('deleted_at')
            ->count();
    }

    private function activeProspectsQuery(User $user): Builder
    {
        return Prospect::query()
            ->where('owner_id', $user->id)
            ->where('status', 'active')
            ->where('is_archived', false)
            ->whereNull('deleted_at');
    }

    private function activeSharesWithUserCount(User $user): int
    {
        return DB::table('prospect_shares')
            ->where('shared_with', $user->id)
            ->where('status', 'active')
            ->whereNull('revoked_at')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();
    }

    /**
     * @return Collection<int, string>
     */
    private function ownedProspectIds(User $user): Collection
    {
        return Prospect::query()
            ->where('owner_id', $user->id)
            ->whereNull('deleted_at')
            ->pluck('id');
    }

    /**
     * @param  Collection<int, string>  $prospectIds
     */
    private function countCommunications(Collection $prospectIds, int $userId, CarbonInterface $start, CarbonInterface $end): int
    {
        if ($prospectIds->isEmpty()) {
            return 0;
        }

        return DB::table('prospect_communications')
            ->whereIn('prospect_id', $prospectIds)
            ->where('user_id', $userId)
            ->whereBetween('contacted_at', [$start, $end])
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * @param  Collection<int, string>  $prospectIds
     */
    private function countActivities(Collection $prospectIds, int $userId, CarbonInterface $start, CarbonInterface $end): int
    {
        if ($prospectIds->isEmpty()) {
            return 0;
        }

        return DB::table('prospect_activities')
            ->whereIn('prospect_id', $prospectIds)
            ->where('user_id', $userId)
            ->whereBetween('occurred_at', [$start, $end])
            ->whereNull('deleted_at')
            ->count();
    }

    private function countAppointments(int $userId, CarbonInterface $start, CarbonInterface $end): int
    {
        return DB::table('prospect_appointments')
            ->where('owner_id', $userId)
            ->whereIn('status', ['scheduled', 'completed'])
            ->whereBetween('scheduled_at', [$start, $end])
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * @param  Collection<int, string>  $prospectIds
     */
    private function countPresentations(Collection $prospectIds, int $userId, CarbonInterface $start, CarbonInterface $end): int
    {
        $activityCount = 0;

        if ($prospectIds->isNotEmpty()) {
            $activityCount = DB::table('prospect_activities')
                ->whereIn('prospect_id', $prospectIds)
                ->where('user_id', $userId)
                ->where('activity_type', 'presentation')
                ->whereBetween('occurred_at', [$start, $end])
                ->whereNull('deleted_at')
                ->count();
        }

        $presentationStageId = DB::table('pipeline_stages')
            ->where('slug', 'presentation-completed')
            ->value('id');

        $stageCount = 0;

        if ($presentationStageId && $prospectIds->isNotEmpty()) {
            $stageCount = DB::table('prospect_stage_history')
                ->whereIn('prospect_id', $prospectIds)
                ->where('to_stage_id', $presentationStageId)
                ->whereBetween('created_at', [$start, $end])
                ->count();
        }

        return $activityCount + $stageCount;
    }

    /**
     * @param  Collection<int, string>  $prospectIds
     */
    private function countApplications(Collection $prospectIds, CarbonInterface $start, CarbonInterface $end): int
    {
        if ($prospectIds->isEmpty()) {
            return 0;
        }

        $applicationStageId = DB::table('pipeline_stages')
            ->where('slug', 'application-submitted')
            ->value('id');

        $stageCount = 0;

        if ($applicationStageId) {
            $stageCount = DB::table('prospect_stage_history')
                ->whereIn('prospect_id', $prospectIds)
                ->where('to_stage_id', $applicationStageId)
                ->whereBetween('created_at', [$start, $end])
                ->count();
        }

        $conversionCount = DB::table('prospect_conversions')
            ->whereIn('prospect_id', $prospectIds)
            ->where('conversion_type', 'client')
            ->whereBetween('converted_at', [$start, $end])
            ->count();

        return $stageCount + $conversionCount;
    }

    /**
     * @param  Collection<int, string>  $prospectIds
     */
    private function countRecruits(Collection $prospectIds, CarbonInterface $start, CarbonInterface $end): int
    {
        $conversionCount = 0;

        if ($prospectIds->isNotEmpty()) {
            $conversionCount = DB::table('prospect_conversions')
                ->whereIn('prospect_id', $prospectIds)
                ->where('conversion_type', 'associate')
                ->whereBetween('converted_at', [$start, $end])
                ->count();
        }

        $terminalStageId = DB::table('pipeline_stages')
            ->where('slug', 'became-associate')
            ->value('id');

        $stageCount = 0;

        if ($terminalStageId && $prospectIds->isNotEmpty()) {
            $stageCount = DB::table('prospect_stage_history')
                ->whereIn('prospect_id', $prospectIds)
                ->where('to_stage_id', $terminalStageId)
                ->whereBetween('created_at', [$start, $end])
                ->count();
        }

        return $conversionCount + $stageCount;
    }

    /**
     * @param  Collection<int, string>  $prospectIds
     */
    private function countRegistrations(User $user, Collection $prospectIds, CarbonInterface $start, CarbonInterface $end): int
    {
        if ($prospectIds->isEmpty()) {
            return 0;
        }

        return DB::table('prospect_conversions')
            ->whereIn('prospect_id', $prospectIds)
            ->where('conversion_type', 'associate')
            ->whereNotNull('created_user_id')
            ->whereBetween('converted_at', [$start, $end])
            ->count();
    }

    private function countInvitationsSent(User $user, CarbonInterface $start, CarbonInterface $end): int
    {
        return DB::table('registration_invitations')
            ->where('sponsor_id', $user->id)
            ->whereNotNull('prospect_id')
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('deleted_at')
            ->count();
    }
}
