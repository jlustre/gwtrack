<?php

namespace App\Services\Prospects;

use App\Models\Prospect;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProspectActivityLogSummaryService
{
    /** @var array<string, int|null> */
    private array $stageIds = [];

    /** @var int|null */
    private ?int $callCommunicationTypeId = null;

    /**
     * @return array{
     *     start: CarbonInterface,
     *     end: CarbonInterface,
     *     grouping: string,
     *     totals: array<string, int>,
     *     buckets: list<array{key: string, label: string, start: string, end: string, metrics: array<string, int>}>
     * }
     */
    public function summarize(
        User $user,
        CarbonInterface $start,
        CarbonInterface $end,
        string $grouping = 'daily',
    ): array {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();
        $grouping = in_array($grouping, ['daily', 'weekly', 'monthly'], true) ? $grouping : 'daily';

        $prospectIds = $this->ownedProspectIds($user);
        $metricKeys = array_keys(config('prospects.activity_log_summary_metrics', []));

        $totals = [];
        foreach ($metricKeys as $metricKey) {
            $totals[$metricKey] = $this->computeMetric($user, $prospectIds, $metricKey, $start, $end);
        }

        $buckets = [];
        foreach ($this->bucketRanges($start, $end, $grouping) as $bucket) {
            $metrics = [];
            foreach ($metricKeys as $metricKey) {
                $metrics[$metricKey] = $this->computeMetric(
                    $user,
                    $prospectIds,
                    $metricKey,
                    $bucket['start'],
                    $bucket['end'],
                );
            }

            $buckets[] = [
                'key' => $bucket['key'],
                'label' => $bucket['label'],
                'start' => $bucket['start']->toDateString(),
                'end' => $bucket['end']->toDateString(),
                'metrics' => $metrics,
            ];
        }

        return [
            'start' => $start,
            'end' => $end,
            'grouping' => $grouping,
            'totals' => $totals,
            'buckets' => $buckets,
        ];
    }

    public function totalForLast30Days(User $user): int
    {
        return $this->totalForPeriod(
            $user,
            now()->subDays(29)->startOfDay(),
            now()->endOfDay(),
        );
    }

    public function totalForPeriod(User $user, CarbonInterface $start, CarbonInterface $end): int
    {
        return (int) array_sum($this->summarize($user, $start, $end, 'daily')['totals']);
    }

    /**
     * @return list<array{key: string, label: string, start: CarbonInterface, end: CarbonInterface}>
     */
    public function bucketRanges(CarbonInterface $start, CarbonInterface $end, string $grouping): array
    {
        $ranges = [];

        if ($grouping === 'monthly') {
            $cursor = $start->copy()->startOfMonth();
            $last = $end->copy()->startOfMonth();

            while ($cursor->lte($last)) {
                $bucketStart = $cursor->copy()->startOfMonth()->max($start);
                $bucketEnd = $cursor->copy()->endOfMonth()->min($end);

                $ranges[] = [
                    'key' => $cursor->format('Y-m'),
                    'label' => $cursor->format('M Y'),
                    'start' => $bucketStart->copy()->startOfDay(),
                    'end' => $bucketEnd->copy()->endOfDay(),
                ];

                $cursor->addMonth();
            }

            return $ranges;
        }

        if ($grouping === 'weekly') {
            $cursor = $start->copy()->startOfWeek();
            $last = $end->copy()->startOfWeek();

            while ($cursor->lte($last)) {
                $bucketStart = $cursor->copy()->startOfWeek()->max($start);
                $bucketEnd = $cursor->copy()->endOfWeek()->min($end);

                $ranges[] = [
                    'key' => $cursor->format('Y-\\WW'),
                    'label' => $bucketStart->format('M j').' – '.$bucketEnd->format('M j'),
                    'start' => $bucketStart->copy()->startOfDay(),
                    'end' => $bucketEnd->copy()->endOfDay(),
                ];

                $cursor->addWeek();
            }

            return $ranges;
        }

        foreach (CarbonPeriod::create($start->toDateString(), $end->toDateString()) as $day) {
            $ranges[] = [
                'key' => $day->format('Y-m-d'),
                'label' => $day->format('D, M j'),
                'start' => $day->copy()->startOfDay(),
                'end' => $day->copy()->endOfDay(),
            ];
        }

        return $ranges;
    }

    /**
     * @param  Collection<int, string>  $prospectIds
     */
    private function computeMetric(
        User $user,
        Collection $prospectIds,
        string $metricKey,
        CarbonInterface $start,
        CarbonInterface $end,
    ): int {
        return match ($metricKey) {
            'phone_calls_attempted' => $this->countPhoneCallsAttempted($prospectIds, $user->id, $start, $end),
            'contacted' => $this->countContacted($prospectIds, $user->id, $start, $end),
            'invitation_success' => $this->countStageTransitions($prospectIds, ['invitation-sent', 'registration-link-sent', 'registered'], $start, $end),
            'presentations' => $this->countPresentations($prospectIds, $user->id, $start, $end),
            'fna_filled' => $this->countFnaFilled($user, $prospectIds, $start, $end),
            'became_client' => $this->countConversions($prospectIds, 'client', 'became-client', $start, $end),
            'became_associate' => $this->countConversions($prospectIds, 'associate', 'became-associate', $start, $end),
            default => 0,
        };
    }

    /**
     * @param  Collection<int, string>  $prospectIds
     */
    private function countPhoneCallsAttempted(Collection $prospectIds, int $userId, CarbonInterface $start, CarbonInterface $end): int
    {
        $activityCount = 0;

        if ($prospectIds->isNotEmpty()) {
            $activityCount = DB::table('prospect_activities')
                ->whereIn('prospect_id', $prospectIds)
                ->where('user_id', $userId)
                ->where('activity_type', 'phone_call')
                ->whereBetween('occurred_at', [$start, $end])
                ->whereNull('deleted_at')
                ->count();
        }

        $callTypeId = $this->callCommunicationTypeId();

        if ($callTypeId === null || $prospectIds->isEmpty()) {
            return $activityCount;
        }

        $communicationCount = DB::table('prospect_communications')
            ->whereIn('prospect_id', $prospectIds)
            ->where('user_id', $userId)
            ->where('communication_type_id', $callTypeId)
            ->whereBetween('contacted_at', [$start, $end])
            ->whereNull('deleted_at')
            ->count();

        return $activityCount + $communicationCount;
    }

    /**
     * @param  Collection<int, string>  $prospectIds
     */
    private function countContacted(Collection $prospectIds, int $userId, CarbonInterface $start, CarbonInterface $end): int
    {
        $contactedOutcomes = config('prospects.contacted_outcomes', []);
        $count = 0;

        if ($prospectIds->isNotEmpty()) {
            $count += DB::table('prospect_activities')
                ->whereIn('prospect_id', $prospectIds)
                ->where('user_id', $userId)
                ->where('activity_type', 'phone_call')
                ->whereIn('outcome', $contactedOutcomes)
                ->whereBetween('occurred_at', [$start, $end])
                ->whereNull('deleted_at')
                ->count();

            $callTypeId = $this->callCommunicationTypeId();

            if ($callTypeId !== null) {
                $count += DB::table('prospect_communications')
                    ->whereIn('prospect_id', $prospectIds)
                    ->where('user_id', $userId)
                    ->where('communication_type_id', $callTypeId)
                    ->whereIn('outcome', $contactedOutcomes)
                    ->whereBetween('contacted_at', [$start, $end])
                    ->whereNull('deleted_at')
                    ->count();
            }

            $count += $this->countStageTransitions($prospectIds, ['contacted', 'contact-made'], $start, $end);
        }

        return $count;
    }

    /**
     * @param  Collection<int, string>  $prospectIds
     * @param  list<string>  $stageSlugs
     */
    private function countStageTransitions(
        Collection $prospectIds,
        array $stageSlugs,
        CarbonInterface $start,
        CarbonInterface $end,
    ): int {
        if ($prospectIds->isEmpty()) {
            return 0;
        }

        $stageIds = collect($stageSlugs)
            ->map(fn (string $slug) => $this->stageId($slug))
            ->filter()
            ->values()
            ->all();

        if ($stageIds === []) {
            return 0;
        }

        return DB::table('prospect_stage_history')
            ->whereIn('prospect_id', $prospectIds)
            ->whereIn('to_stage_id', $stageIds)
            ->whereBetween('created_at', [$start, $end])
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
                ->whereIn('activity_type', ['presentation', 'zoom_meeting', 'in_person_meeting'])
                ->whereBetween('occurred_at', [$start, $end])
                ->whereNull('deleted_at')
                ->count();
        }

        return $activityCount + $this->countStageTransitions($prospectIds, ['presentation-completed'], $start, $end);
    }

    /**
     * @param  Collection<int, string>  $prospectIds
     */
    private function countFnaFilled(User $user, Collection $prospectIds, CarbonInterface $start, CarbonInterface $end): int
    {
        $count = 0;

        if ($prospectIds->isNotEmpty()) {
            $count += DB::table('prospect_activities')
                ->whereIn('prospect_id', $prospectIds)
                ->where('user_id', $user->id)
                ->where('activity_type', 'financial_review')
                ->whereBetween('occurred_at', [$start, $end])
                ->whereNull('deleted_at')
                ->count();

            $count += DB::table('prospects')
                ->whereIn('id', $prospectIds)
                ->where('fna_status', 'completed')
                ->whereBetween('updated_at', [$start, $end])
                ->whereNull('deleted_at')
                ->count();

            $count += $this->countStageTransitions($prospectIds, ['financial-review'], $start, $end);
        }

        return $count;
    }

    /**
     * @param  Collection<int, string>  $prospectIds
     */
    private function countConversions(
        Collection $prospectIds,
        string $conversionType,
        string $terminalStageSlug,
        CarbonInterface $start,
        CarbonInterface $end,
    ): int {
        $conversionCount = 0;

        if ($prospectIds->isNotEmpty()) {
            $conversionCount = DB::table('prospect_conversions')
                ->whereIn('prospect_id', $prospectIds)
                ->where('conversion_type', $conversionType)
                ->whereBetween('converted_at', [$start, $end])
                ->count();
        }

        return $conversionCount + $this->countStageTransitions($prospectIds, [$terminalStageSlug], $start, $end);
    }

    private function stageId(string $slug): ?int
    {
        if (! array_key_exists($slug, $this->stageIds)) {
            $this->stageIds[$slug] = DB::table('pipeline_stages')
                ->where('slug', $slug)
                ->value('id');
        }

        return $this->stageIds[$slug] !== null ? (int) $this->stageIds[$slug] : null;
    }

    private function callCommunicationTypeId(): ?int
    {
        if ($this->callCommunicationTypeId === null) {
            $this->callCommunicationTypeId = DB::table('communication_types')
                ->where('name', 'Call')
                ->value('id');
        }

        return $this->callCommunicationTypeId !== null ? (int) $this->callCommunicationTypeId : null;
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
}
