<?php

namespace App\Services\CfmEffectiveness;

use App\Models\ChecklistProgress;
use App\Models\MemberProductionEntry;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\ChecklistService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CfmTraineeSuccessAnalyticsService
{
    public function __construct(
        private readonly ChecklistService $checklists,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summaryFor(User $cfm): array
    {
        $traineeMetrics = $this->traineeMetricsFor($cfm);
        $cfmAverages = $this->averagesFrom($traineeMetrics);
        $agencyAverages = $this->agencyAverages();
        $topCfmAverages = $this->topCfmAverages();

        return [
            'trainee_count' => $traineeMetrics->count(),
            'avg_time_to_license_days' => $cfmAverages['time_to_license'],
            'avg_time_to_fap_days' => $cfmAverages['time_to_fap'],
            'avg_time_to_first_recruit_days' => $cfmAverages['time_to_first_recruit'],
            'avg_time_to_first_sale_days' => $cfmAverages['time_to_first_sale'],
            'avg_time_to_promotion_days' => null,
            'sample_sizes' => [
                'licensed' => $traineeMetrics->whereNotNull('time_to_license_days')->count(),
                'fap_complete' => $traineeMetrics->whereNotNull('time_to_fap_days')->count(),
                'first_recruit' => $traineeMetrics->whereNotNull('time_to_first_recruit_days')->count(),
                'first_sale' => $traineeMetrics->whereNotNull('time_to_first_sale_days')->count(),
            ],
            'cfm_vs_agency' => [
                'time_to_license' => $this->comparisonRow(
                    $cfmAverages['time_to_license'],
                    $agencyAverages['time_to_license'],
                    $topCfmAverages['time_to_license'],
                ),
                'time_to_fap' => $this->comparisonRow(
                    $cfmAverages['time_to_fap'],
                    $agencyAverages['time_to_fap'],
                    $topCfmAverages['time_to_fap'],
                ),
                'time_to_first_sale' => $this->comparisonRow(
                    $cfmAverages['time_to_first_sale'],
                    $agencyAverages['time_to_first_sale'],
                    $topCfmAverages['time_to_first_sale'],
                ),
                'time_to_first_recruit' => $this->comparisonRow(
                    $cfmAverages['time_to_first_recruit'],
                    $agencyAverages['time_to_first_recruit'],
                    $topCfmAverages['time_to_first_recruit'],
                ),
            ],
            'trainees' => $traineeMetrics
                ->sortByDesc(fn (array $row) => $row['time_to_license_days'] ?? -1)
                ->take(8)
                ->values()
                ->all(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function traineeMetricsFor(User $cfm): Collection
    {
        $traineeIds = MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->pluck('apprentice_id')
            ->unique()
            ->values();

        if ($traineeIds->isEmpty()) {
            return collect();
        }

        $trainees = User::query()->whereIn('id', $traineeIds)->get()->keyBy('id');

        return $traineeIds->map(function (int $traineeId) use ($cfm, $trainees): array {
            $trainee = $trainees->get($traineeId);

            if (! $trainee) {
                return ['trainee_id' => $traineeId, 'name' => 'Unknown'];
            }

            $startDate = $this->traineeStartDate($trainee, $cfm->id);

            return [
                'trainee_id' => $trainee->id,
                'name' => $trainee->name,
                'start_date' => $startDate->toDateString(),
                'time_to_license_days' => $this->daysToMilestone($startDate, $this->checklistCompletedAt($trainee, 'licensing')),
                'time_to_fap_days' => $this->daysToMilestone($startDate, $this->checklistCompletedAt($trainee, 'fap')),
                'time_to_first_recruit_days' => $this->daysToMilestone($startDate, $this->firstRecruitAt($trainee)),
                'time_to_first_sale_days' => $this->daysToMilestone($startDate, $this->firstSaleAt($trainee)),
            ];
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $metrics
     * @return array<string, float|null>
     */
    private function averagesFrom(Collection $metrics): array
    {
        return [
            'time_to_license' => $this->averageDays($metrics->pluck('time_to_license_days')),
            'time_to_fap' => $this->averageDays($metrics->pluck('time_to_fap_days')),
            'time_to_first_recruit' => $this->averageDays($metrics->pluck('time_to_first_recruit_days')),
            'time_to_first_sale' => $this->averageDays($metrics->pluck('time_to_first_sale_days')),
        ];
    }

    /**
     * @return array<string, float|null>
     */
    private function agencyAverages(): array
    {
        $cfms = User::role('certified-field-mentor')->get(['id']);
        $allMetrics = $cfms->flatMap(fn (User $cfm) => $this->traineeMetricsFor($cfm));

        return $this->averagesFrom($allMetrics);
    }

    /**
     * @return array<string, float|null>
     */
    private function topCfmAverages(): array
    {
        $cfms = User::role('certified-field-mentor')->get(['id']);

        $cfmSummaries = $cfms->map(function (User $cfm): array {
            $metrics = $this->traineeMetricsFor($cfm);

            return $this->averagesFrom($metrics);
        });

        return [
            'time_to_license' => $this->bestAverage($cfmSummaries->pluck('time_to_license')),
            'time_to_fap' => $this->bestAverage($cfmSummaries->pluck('time_to_fap')),
            'time_to_first_recruit' => $this->bestAverage($cfmSummaries->pluck('time_to_first_recruit')),
            'time_to_first_sale' => $this->bestAverage($cfmSummaries->pluck('time_to_first_sale')),
        ];
    }

    private function traineeStartDate(User $trainee, int $cfmId): Carbon
    {
        $startedAt = MentorAssignment::query()
            ->where('mentor_id', $cfmId)
            ->where('apprentice_id', $trainee->id)
            ->whereNotNull('started_at')
            ->orderBy('started_at')
            ->value('started_at');

        if ($startedAt) {
            return Carbon::parse($startedAt)->startOfDay();
        }

        return ($trainee->joined_at ?? $trainee->created_at)->copy()->startOfDay();
    }

    private function checklistCompletedAt(User $trainee, string $typeCode): ?Carbon
    {
        $checklistIds = $this->checklists->activeChecklistIdsForType($typeCode);

        if ($checklistIds === [] || $this->checklists->checklistPercent($checklistIds, $trainee->id) < 100) {
            return null;
        }

        $completedAt = ChecklistProgress::query()
            ->where('user_id', $trainee->id)
            ->memberProgress()
            ->whereIn('checklist_id', $checklistIds)
            ->completed()
            ->max('completed_at');

        return $completedAt ? Carbon::parse($completedAt) : null;
    }

    private function firstSaleAt(User $trainee): ?Carbon
    {
        $postedAt = MemberProductionEntry::query()
            ->where('user_id', $trainee->id)
            ->where('status', 'posted')
            ->orderBy('posted_at')
            ->value('posted_at');

        return $postedAt ? Carbon::parse($postedAt)->startOfDay() : null;
    }

    private function firstRecruitAt(User $trainee): ?Carbon
    {
        $recruitedAt = User::query()
            ->where('sponsor_id', $trainee->id)
            ->orderBy('joined_at')
            ->orderBy('created_at')
            ->value('joined_at');

        if (! $recruitedAt) {
            $recruitedAt = User::query()
                ->where('sponsor_id', $trainee->id)
                ->orderBy('created_at')
                ->value('created_at');
        }

        return $recruitedAt ? Carbon::parse($recruitedAt)->startOfDay() : null;
    }

    private function daysToMilestone(Carbon $startDate, ?Carbon $milestoneDate): ?int
    {
        if ($milestoneDate === null || $milestoneDate->lt($startDate)) {
            return null;
        }

        return (int) $startDate->diffInDays($milestoneDate);
    }

    /**
     * @param  Collection<int, int|null>  $values
     */
    private function averageDays(Collection $values): ?float
    {
        $filtered = $values->filter(fn ($value) => $value !== null)->values();

        if ($filtered->isEmpty()) {
            return null;
        }

        return round($filtered->avg(), 1);
    }

    /**
     * @param  Collection<int, float|null>  $values
     */
    private function bestAverage(Collection $values): ?float
    {
        $filtered = $values->filter(fn ($value) => $value !== null);

        if ($filtered->isEmpty()) {
            return null;
        }

        return round($filtered->min(), 1);
    }

    /**
     * @return array{cfm: float|null, agency: float|null, top_cfm: float|null, unit: string, faster_than_agency: bool|null}
     */
    private function comparisonRow(?float $cfm, ?float $agency, ?float $topCfm): array
    {
        return [
            'cfm' => $cfm,
            'agency' => $agency,
            'top_cfm' => $topCfm,
            'unit' => 'days',
            'faster_than_agency' => ($cfm !== null && $agency !== null) ? $cfm < $agency : null,
        ];
    }
}
