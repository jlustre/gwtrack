<?php

namespace App\Services\Fna;

use App\Models\FnaAnalyticsSnapshot;
use App\Models\FnaRecord;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FnaAnalyticsService
{
    public function __construct(
        private readonly DownlineHierarchyService $hierarchy,
    ) {}

    /**
     * @return array{
     *     total_fnas: int,
     *     draft_fnas: int,
     *     awaiting_review: int,
     *     approved_fnas: int,
     *     revision_requested: int,
     *     dime_completed: int,
     *     avg_protection_gap: float|null,
     *     meetings_this_week: int,
     *     conversion_after_fna: int,
     *     avg_cfm_review_hours: float|null
     * }
     */
    public function summaryFor(User $user): array
    {
        $owned = $this->ownedRecordsQuery($user);
        $total = (clone $owned)->count();

        $awaitingReview = $user->can('review trainee fna records')
            ? (clone $this->cfmReviewableQuery($user))->whereIn('status', $this->awaitingReviewStatuses())->count()
            : (clone $owned)->whereIn('status', $this->awaitingReviewStatuses())->count();

        $revisionRequested = $user->can('review trainee fna records')
            ? (clone $this->cfmReviewableQuery($user))->where('status', 'revision_requested')->count()
            : (clone $owned)->where('status', 'revision_requested')->count();

        $avgGap = (clone $owned)->whereNotNull('protection_gap')->avg('protection_gap');

        return [
            'total_fnas' => $total,
            'draft_fnas' => (clone $owned)->where('status', 'draft')->count(),
            'awaiting_review' => $awaitingReview,
            'approved_fnas' => (clone $owned)->whereIn('status', $this->approvedStatuses())->count(),
            'revision_requested' => $revisionRequested,
            'dime_completed' => (clone $owned)->where('dime_completed', true)->count(),
            'avg_protection_gap' => $avgGap !== null ? round((float) $avgGap, 2) : null,
            'meetings_this_week' => $this->meetingsThisWeek($user)->count(),
            'conversion_after_fna' => $this->conversionAfterFnaRate($user),
            'avg_cfm_review_hours' => $this->avgCfmReviewHours($user),
        ];
    }

    public function metricCountFor(User $user, string $metricKey, CarbonInterface $start, CarbonInterface $end): int
    {
        $query = $this->ownedRecordsQuery($user);

        return match ($metricKey) {
            'fna_approved' => (clone $query)
                ->whereIn('status', $this->approvedStatuses())
                ->whereNotNull('approved_at')
                ->whereBetween('approved_at', [$start, $end])
                ->count(),
            default => (clone $query)
                ->whereNotNull('submitted_at')
                ->whereBetween('submitted_at', [$start, $end])
                ->count(),
        };
    }

    public function awaitingReviewList(User $user, int $limit = 5): Collection
    {
        $query = $user->can('review trainee fna records')
            ? $this->cfmReviewableQuery($user)
            : $this->ownedRecordsQuery($user);

        return $query
            ->whereIn('status', $this->awaitingReviewStatuses())
            ->with(['owner:id,name', 'prospect:id,first_name,last_name,preferred_name'])
            ->latest('submitted_at')
            ->limit($limit)
            ->get();
    }

    public function revisionRequestedList(User $user, int $limit = 5): Collection
    {
        $query = $user->can('review trainee fna records')
            ? $this->cfmReviewableQuery($user)
            : $this->ownedRecordsQuery($user);

        return $query
            ->where('status', 'revision_requested')
            ->with(['owner:id,name', 'prospect:id,first_name,last_name,preferred_name'])
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    public function meetingsThisWeek(User $user): Collection
    {
        $fnaIds = $this->ownedRecordsQuery($user)->pluck('id');

        if ($fnaIds->isEmpty()) {
            return collect();
        }

        $start = now()->startOfWeek();
        $end = now()->endOfWeek();

        return DB::table('calendar_events')
            ->whereIn('related_fna_id', $fnaIds)
            ->whereNull('deleted_at')
            ->whereBetween('starts_at', [$start, $end])
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * @return array{segments: list<array{label: string, key: string, count: int}>, total: int}
     */
    public function completionProgress(User $user): array
    {
        $owned = $this->ownedRecordsQuery($user);
        $total = (clone $owned)->count();

        $segments = [
            ['key' => 'draft', 'label' => 'Draft', 'count' => (clone $owned)->where('status', 'draft')->count()],
            ['key' => 'in_review', 'label' => 'In Review', 'count' => (clone $owned)->whereIn('status', $this->awaitingReviewStatuses())->count()],
            ['key' => 'revision', 'label' => 'Revision', 'count' => (clone $owned)->where('status', 'revision_requested')->count()],
            ['key' => 'approved', 'label' => 'Approved+', 'count' => (clone $owned)->whereIn('status', $this->approvedStatuses())->count()],
        ];

        return [
            'segments' => $segments,
            'total' => $total,
        ];
    }

    /**
     * @return array{
     *     visible: bool,
     *     member_count?: int,
     *     by_associate?: list<array{user_id: int, name: string, created: int, submitted: int, approved: int, avg_gap: float|null, avg_review_hours: float|null}>,
     *     by_cfm?: list<array{user_id: int, name: string, review_count: int, approval_rate: int, avg_turnaround_hours: float|null}>
     * }
     */
    public function agencyReportFor(User $viewer): array
    {
        $hasDownline = $this->hierarchy->descendantsQuery($viewer)->exists();
        $isManager = $viewer->hasAnyRole(['team-leader', 'agency-owner', 'admin', 'super-admin']);

        if (! $hasDownline && ! $isManager) {
            return ['visible' => false];
        }

        $memberIds = $this->hierarchy->dashboardMembersQuery($viewer)
            ->pluck('users.id');

        if ($memberIds->isEmpty()) {
            return ['visible' => false];
        }

        $byAssociate = $this->associateReportRows($memberIds);
        $byCfm = $this->cfmReportRows($memberIds);

        return [
            'visible' => true,
            'member_count' => $memberIds->count(),
            'by_associate' => $byAssociate,
            'by_cfm' => $byCfm,
        ];
    }

    /**
     * @return list<array{week: string, label: string, total_fnas: float, approved_fnas: float, submitted_fnas: float}>
     */
    public function trendLines(User $user, int $weeks = 12): array
    {
        $result = [];
        $metricKeys = ['total_fnas', 'approved_fnas', 'submitted_fnas'];

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();
            $weekLabel = $weekStart->format('M j');

            $snapshots = FnaAnalyticsSnapshot::query()
                ->where('user_id', $user->id)
                ->whereBetween('snapshot_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->get()
                ->groupBy('metric_key');

            $row = [
                'week' => $weekStart->format('Y-W'),
                'label' => $weekLabel,
                'total_fnas' => (float) ($snapshots->get('total_fnas')?->avg('value') ?? 0),
                'approved_fnas' => (float) ($snapshots->get('approved_fnas')?->avg('value') ?? 0),
                'submitted_fnas' => (float) ($snapshots->get('submitted_fnas')?->avg('value') ?? 0),
            ];

            $result[] = $row;
        }

        return $result;
    }

    public function rollupForUser(User $user, ?Carbon $date = null): void
    {
        $snapshotDate = ($date ? Carbon::parse($date) : now())->toDateString();
        $owned = $this->ownedRecordsQuery($user);

        $metrics = [
            'total_fnas' => (clone $owned)->count(),
            'draft_fnas' => (clone $owned)->where('status', 'draft')->count(),
            'submitted_fnas' => (clone $owned)->whereNotNull('submitted_at')->count(),
            'approved_fnas' => (clone $owned)->whereIn('status', $this->approvedStatuses())->count(),
            'dime_completed' => (clone $owned)->where('dime_completed', true)->count(),
            'avg_protection_gap' => (clone $owned)->whereNotNull('protection_gap')->avg('protection_gap') ?? 0,
        ];

        foreach ($metrics as $metricKey => $value) {
            FnaAnalyticsSnapshot::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'snapshot_date' => $snapshotDate,
                    'metric_key' => $metricKey,
                ],
                ['value' => round((float) $value, 2)],
            );
        }
    }

    /**
     * @return list<string>
     */
    public function snapshotMetricKeys(): array
    {
        return array_keys(config('fna.analytics_metrics', []));
    }

    private function ownedRecordsQuery(User $user): Builder
    {
        return FnaRecord::query()
            ->where('owner_user_id', $user->id)
            ->whereNull('deleted_at');
    }

    private function cfmReviewableQuery(User $user): Builder
    {
        $apprenticeIds = MentorAssignment::query()
            ->where('mentor_id', $user->id)
            ->where('status', 'active')
            ->pluck('apprentice_id');

        return FnaRecord::query()
            ->where(function ($query) use ($user, $apprenticeIds): void {
                $query->where('cfm_user_id', $user->id)
                    ->orWhereIn('owner_user_id', $apprenticeIds);
            })
            ->whereIn('status', config('fna.cfm_visible_statuses', []))
            ->whereNull('deleted_at');
    }

    /**
     * @return list<string>
     */
    private function approvedStatuses(): array
    {
        return config('fna.approved_statuses', []);
    }

    /**
     * @return list<string>
     */
    private function awaitingReviewStatuses(): array
    {
        return config('fna.awaiting_review_statuses', []);
    }

    private function conversionAfterFnaRate(User $user): int
    {
        $approvedFnas = $this->ownedRecordsQuery($user)
            ->whereIn('status', $this->approvedStatuses())
            ->whereNotNull('approved_at')
            ->whereNotNull('prospect_id')
            ->get(['id', 'prospect_id', 'approved_at']);

        if ($approvedFnas->isEmpty()) {
            return 0;
        }

        $applicationStageId = DB::table('pipeline_stages')
            ->where('slug', 'application-submitted')
            ->value('id');

        if (! $applicationStageId) {
            return 0;
        }

        $converted = 0;

        foreach ($approvedFnas as $fna) {
            $hasConversion = DB::table('prospect_stage_history')
                ->where('prospect_id', $fna->prospect_id)
                ->where('to_stage_id', $applicationStageId)
                ->whereBetween('created_at', [
                    $fna->approved_at,
                    Carbon::parse($fna->approved_at)->addDays(30),
                ])
                ->exists();

            if ($hasConversion) {
                $converted++;
            }
        }

        return (int) round(($converted / $approvedFnas->count()) * 100);
    }

    private function avgCfmReviewHours(User $user): ?float
    {
        if (! $user->can('review trainee fna records')) {
            return null;
        }

        $reviewed = $this->cfmReviewableQuery($user)
            ->whereNotNull('submitted_at')
            ->whereNotNull('approved_at')
            ->get(['submitted_at', 'approved_at']);

        if ($reviewed->isEmpty()) {
            return null;
        }

        $totalHours = $reviewed->sum(function (FnaRecord $fna): float {
            return Carbon::parse($fna->submitted_at)->diffInMinutes(Carbon::parse($fna->approved_at)) / 60;
        });

        return round($totalHours / $reviewed->count(), 1);
    }

    /**
     * @param  Collection<int, int>  $memberIds
     * @return list<array{user_id: int, name: string, created: int, submitted: int, approved: int, avg_gap: float|null, avg_review_hours: float|null}>
     */
    private function associateReportRows(Collection $memberIds): array
    {
        $users = User::query()
            ->whereIn('id', $memberIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $rows = [];

        foreach ($users as $member) {
            $base = FnaRecord::query()
                ->where('owner_user_id', $member->id)
                ->whereNull('deleted_at');

            $reviewed = (clone $base)
                ->whereNotNull('submitted_at')
                ->whereNotNull('approved_at')
                ->get(['submitted_at', 'approved_at']);

            $avgReviewHours = null;

            if ($reviewed->isNotEmpty()) {
                $avgReviewHours = round($reviewed->avg(function (FnaRecord $fna): float {
                    return Carbon::parse($fna->submitted_at)->diffInMinutes(Carbon::parse($fna->approved_at)) / 60;
                }), 1);
            }

            $avgGap = (clone $base)->whereNotNull('protection_gap')->avg('protection_gap');

            $rows[] = [
                'user_id' => $member->id,
                'name' => $member->name,
                'created' => (clone $base)->count(),
                'submitted' => (clone $base)->whereNotNull('submitted_at')->count(),
                'approved' => (clone $base)->whereIn('status', $this->approvedStatuses())->count(),
                'avg_gap' => $avgGap !== null ? round((float) $avgGap, 0) : null,
                'avg_review_hours' => $avgReviewHours,
            ];
        }

        return collect($rows)->sortByDesc('created')->values()->all();
    }

    /**
     * @param  Collection<int, int>  $memberIds
     * @return list<array{user_id: int, name: string, review_count: int, approval_rate: int, avg_turnaround_hours: float|null}>
     */
    private function cfmReportRows(Collection $memberIds): array
    {
        $cfmIds = FnaRecord::query()
            ->whereIn('owner_user_id', $memberIds)
            ->whereNotNull('cfm_user_id')
            ->whereNull('deleted_at')
            ->distinct()
            ->pluck('cfm_user_id');

        if ($cfmIds->isEmpty()) {
            return [];
        }

        $users = User::query()
            ->whereIn('id', $cfmIds)
            ->orderBy('name')
            ->get(['id', 'name']);

        $rows = [];

        foreach ($users as $cfm) {
            $base = FnaRecord::query()
                ->where('cfm_user_id', $cfm->id)
                ->whereIn('owner_user_id', $memberIds)
                ->whereNull('deleted_at');

            $reviewCount = (clone $base)->whereNotNull('submitted_at')->count();
            $approvedCount = (clone $base)->whereIn('status', $this->approvedStatuses())->count();
            $approvalRate = $reviewCount > 0 ? (int) round(($approvedCount / $reviewCount) * 100) : 0;

            $reviewed = (clone $base)
                ->whereNotNull('submitted_at')
                ->whereNotNull('approved_at')
                ->get(['submitted_at', 'approved_at']);

            $avgTurnaround = null;

            if ($reviewed->isNotEmpty()) {
                $avgTurnaround = round($reviewed->avg(function (FnaRecord $fna): float {
                    return Carbon::parse($fna->submitted_at)->diffInMinutes(Carbon::parse($fna->approved_at)) / 60;
                }), 1);
            }

            $rows[] = [
                'user_id' => $cfm->id,
                'name' => $cfm->name,
                'review_count' => $reviewCount,
                'approval_rate' => $approvalRate,
                'avg_turnaround_hours' => $avgTurnaround,
            ];
        }

        return collect($rows)->sortByDesc('review_count')->values()->all();
    }
}
