<?php

namespace App\Services\CfmEffectiveness;

use App\Models\Booking;
use App\Models\CfmCoachingSession;
use App\Models\CfmEffectiveness\CfmPerformanceMetric;
use App\Models\CfmMeeting;
use App\Models\CfmNote;
use App\Models\CfmPromotion;
use App\Models\CfmTask;
use App\Models\Goal;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\ChecklistService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CfmEffectivenessMetricsService
{
    public function __construct(
        private readonly ChecklistService $checklists,
    ) {}

    /**
     * @return array<string, array{value: float, score: float, label: string, meta?: array<string, mixed>}>
     */
    public function calculateFor(User $cfm, ?Carbon $periodStart = null, ?Carbon $periodEnd = null): array
    {
        $periodStart ??= now()->startOfMonth();
        $periodEnd ??= now()->endOfMonth();

        $metrics = [
            'retention_rate' => $this->retentionRate($cfm),
            'fap_completion_rate' => $this->fapCompletionRate($cfm),
            'licensing_completion_rate' => $this->licensingCompletionRate($cfm),
            'meeting_completion_rate' => $this->meetingCompletionRate($cfm, $periodStart, $periodEnd),
            'responsiveness_score' => $this->responsivenessScore($cfm),
            'coaching_activity_score' => $this->coachingActivityScore($cfm, $periodStart, $periodEnd),
            'goal_influence_score' => $this->goalInfluenceScore($cfm),
            'promotion_development_score' => $this->promotionDevelopmentScore($cfm),
        ];

        return collect($metrics)->mapWithKeys(function (array $metric, string $key): array {
            $config = config("cfm-effectiveness.objective_metrics.{$key}", ['label' => $key]);

            return [$key => array_merge($metric, ['label' => $config['label']])];
        })->all();
    }

    public function weightedObjectiveScore(array $metrics): float
    {
        $weights = config('cfm-effectiveness.objective_metrics', []);
        $totalWeight = 0.0;
        $weighted = 0.0;

        foreach ($metrics as $key => $metric) {
            $weight = (float) ($weights[$key]['weight'] ?? 0);
            if ($weight <= 0) {
                continue;
            }

            $totalWeight += $weight;
            $weighted += $weight * (float) ($metric['score'] ?? 0);
        }

        if ($totalWeight <= 0) {
            return 0.0;
        }

        return round($weighted / $totalWeight, 2);
    }

    public function persistMetrics(User $cfm, array $metrics, Carbon $periodStart, Carbon $periodEnd): void
    {
        foreach ($metrics as $key => $metric) {
            CfmPerformanceMetric::query()->updateOrCreate(
                [
                    'cfm_id' => $cfm->id,
                    'metric_key' => $key,
                    'period_start' => $periodStart->toDateString(),
                    'period_end' => $periodEnd->toDateString(),
                ],
                [
                    'value' => $metric['value'],
                    'score' => $metric['score'],
                    'meta' => $metric['meta'] ?? null,
                ],
            );
        }
    }

    /**
     * @return array{value: float, score: float, meta?: array<string, mixed>}
     */
    private function retentionRate(User $cfm): array
    {
        $assigned = MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->whereIn('status', ['active', 'completed'])
            ->count();

        $active = MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->where('status', 'active')
            ->whereHas('apprentice', fn ($q) => $q->where('is_active', true))
            ->count();

        $rate = $assigned > 0 ? round(($active / $assigned) * 100, 2) : 0.0;

        return [
            'value' => $rate,
            'score' => min(100, $rate),
            'meta' => ['assigned' => $assigned, 'active' => $active],
        ];
    }

    /**
     * @return array{value: float, score: float, meta?: array<string, mixed>}
     */
    private function fapCompletionRate(User $cfm): array
    {
        $traineeIds = $this->traineeIds($cfm);
        if ($traineeIds->isEmpty()) {
            return ['value' => 0, 'score' => 0, 'meta' => ['completed' => 0, 'total' => 0]];
        }

        $fapIds = $this->checklists->activeChecklistIdsForType('fap');
        $completed = 0;

        foreach ($traineeIds as $traineeId) {
            if ($this->checklists->checklistPercent($fapIds, $traineeId) >= 100) {
                $completed++;
            }
        }

        $total = $traineeIds->count();
        $rate = round(($completed / $total) * 100, 2);

        return [
            'value' => $rate,
            'score' => $rate,
            'meta' => ['completed' => $completed, 'total' => $total],
        ];
    }

    /**
     * @return array{value: float, score: float, meta?: array<string, mixed>}
     */
    private function licensingCompletionRate(User $cfm): array
    {
        $traineeIds = $this->traineeIds($cfm);
        if ($traineeIds->isEmpty()) {
            return ['value' => 0, 'score' => 0, 'meta' => ['licensed' => 0, 'total' => 0]];
        }

        $licensingIds = $this->checklists->activeChecklistIdsForType('licensing');
        $licensed = 0;

        foreach ($traineeIds as $traineeId) {
            if ($this->checklists->checklistPercent($licensingIds, $traineeId) >= 100) {
                $licensed++;
            }
        }

        $total = $traineeIds->count();
        $rate = round(($licensed / $total) * 100, 2);

        return [
            'value' => $rate,
            'score' => $rate,
            'meta' => ['licensed' => $licensed, 'total' => $total],
        ];
    }

    /**
     * @return array{value: float, score: float, meta?: array<string, mixed>}
     */
    private function meetingCompletionRate(User $cfm, Carbon $periodStart, Carbon $periodEnd): array
    {
        $scheduled = Booking::query()
            ->where('cfm_id', $cfm->id)
            ->whereBetween('starts_at', [$periodStart, $periodEnd])
            ->whereNull('cancelled_at')
            ->count();

        $completedBookings = Booking::query()
            ->where('cfm_id', $cfm->id)
            ->whereBetween('starts_at', [$periodStart, $periodEnd])
            ->where('status', 'completed')
            ->count();

        $completedMeetings = CfmMeeting::query()
            ->where('cfm_id', $cfm->id)
            ->where('status', 'completed')
            ->whereBetween('starts_at', [$periodStart, $periodEnd])
            ->count();

        $completed = max($completedBookings, $completedMeetings);
        $total = max($scheduled, $completed);

        if ($total === 0) {
            return ['value' => 0, 'score' => 0, 'meta' => ['scheduled' => 0, 'completed' => 0]];
        }

        $rate = round(($completed / $total) * 100, 2);

        return [
            'value' => $rate,
            'score' => $rate,
            'meta' => ['scheduled' => $total, 'completed' => $completed],
        ];
    }

    /**
     * @return array{value: float, score: float, meta?: array<string, mixed>}
     */
    private function responsivenessScore(User $cfm): array
    {
        $assignments = MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->whereNotNull('started_at')
            ->latest('id')
            ->limit(20)
            ->get();

        $hours = $assignments
            ->filter(fn (MentorAssignment $a) => $a->confirmed_at && $a->started_at)
            ->map(fn (MentorAssignment $a) => Carbon::parse($a->started_at)->diffInHours($a->confirmed_at))
            ->filter(fn ($h) => $h >= 0);

        if ($hours->isEmpty()) {
            return [
                'value' => 0,
                'score' => 0,
                'meta' => [
                    'average_hours' => null,
                    'band' => 'No data',
                    'samples' => 0,
                ],
            ];
        }

        $avgHours = round($hours->avg(), 2);
        $score = $this->responseTimeScore($avgHours);

        return [
            'value' => $avgHours,
            'score' => $score,
            'meta' => [
                'average_hours' => $avgHours,
                'band' => $this->responseTimeLabel($avgHours),
                'samples' => $hours->count(),
            ],
        ];
    }

    /**
     * @return array{value: float, score: float, meta?: array<string, mixed>}
     */
    private function coachingActivityScore(User $cfm, Carbon $periodStart, Carbon $periodEnd): array
    {
        $notes = CfmNote::query()
            ->where('cfm_id', $cfm->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $tasks = CfmTask::query()
            ->where('cfm_id', $cfm->id)
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->count();

        $completedTasks = CfmTask::query()
            ->where('cfm_id', $cfm->id)
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$periodStart, $periodEnd])
            ->count();

        $sessions = CfmCoachingSession::query()
            ->where('cfm_id', $cfm->id)
            ->whereBetween('session_at', [$periodStart, $periodEnd])
            ->count();

        $meetings = CfmMeeting::query()
            ->where('cfm_id', $cfm->id)
            ->where('status', 'completed')
            ->whereBetween('starts_at', [$periodStart, $periodEnd])
            ->count();

        $activityPoints = ($notes * 2) + $tasks + ($completedTasks * 2) + ($sessions * 3) + ($meetings * 2);
        $score = (int) min(100, round($activityPoints * 2.5));

        return [
            'value' => (float) $activityPoints,
            'score' => (float) $score,
            'meta' => compact('notes', 'tasks', 'completedTasks', 'sessions', 'meetings'),
        ];
    }

    /**
     * @return array{value: float, score: float, meta?: array<string, mixed>}
     */
    private function goalInfluenceScore(User $cfm): array
    {
        $traineeIds = $this->traineeIds($cfm);
        if ($traineeIds->isEmpty()) {
            return ['value' => 0, 'score' => 0, 'meta' => ['completed' => 0, 'total' => 0]];
        }

        $goals = Goal::query()
            ->whereIn('user_id', $traineeIds)
            ->get(['id', 'status']);

        $total = $goals->count();
        $completed = $goals->where('status', 'completed')->count();

        if ($total === 0) {
            return ['value' => 0, 'score' => 0, 'meta' => ['completed' => 0, 'total' => 0]];
        }

        $rate = round(($completed / $total) * 100, 2);

        return [
            'value' => $rate,
            'score' => $rate,
            'meta' => ['completed' => $completed, 'total' => $total],
        ];
    }

    /**
     * @return array{value: float, score: float, meta?: array<string, mixed>}
     */
    private function promotionDevelopmentScore(User $cfm): array
    {
        $promoted = CfmPromotion::query()
            ->where('cfm_id', $cfm->id)
            ->whereIn('status', ['ready', 'nominated'])
            ->count();

        $tracking = CfmPromotion::query()
            ->where('cfm_id', $cfm->id)
            ->count();

        $traineeCount = max(1, $this->traineeIds($cfm)->count());
        $rate = round(($promoted / $traineeCount) * 100, 2);
        $score = min(100, $rate + min(40, $tracking * 5));

        return [
            'value' => (float) $promoted,
            'score' => (float) $score,
            'meta' => ['promoted' => $promoted, 'tracking' => $tracking],
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function traineeIds(User $cfm): Collection
    {
        return User::query()
            ->where('mentor_id', $cfm->id)
            ->whereKeyNot($cfm->id)
            ->pluck('id');
    }

    private function responseTimeScore(float $hours): float
    {
        foreach (config('cfm-effectiveness.response_time_bands', []) as $band) {
            if ($band['max_hours'] === null || $hours <= $band['max_hours']) {
                return (float) $band['score'];
            }
        }

        return 35.0;
    }

    private function responseTimeLabel(float $hours): string
    {
        foreach (config('cfm-effectiveness.response_time_bands', []) as $band) {
            if ($band['max_hours'] === null || $hours <= $band['max_hours']) {
                return $band['label'];
            }
        }

        return 'Poor';
    }
}
