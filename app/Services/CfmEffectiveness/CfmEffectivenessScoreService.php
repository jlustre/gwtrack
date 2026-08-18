<?php

namespace App\Services\CfmEffectiveness;

use App\Models\CfmEffectiveness\CfmAoEvaluation;
use App\Models\CfmEffectiveness\CfmEffectivenessScore;
use App\Models\CfmEffectiveness\CfmReview;
use App\Models\User;
use Carbon\Carbon;

class CfmEffectivenessScoreService
{
    public function __construct(
        private readonly CfmEffectivenessMetricsService $metrics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function calculateAndStore(User $cfm, ?Carbon $periodStart = null, ?Carbon $periodEnd = null, string $periodType = 'monthly'): array
    {
        $periodStart ??= now()->startOfMonth();
        $periodEnd ??= now()->endOfMonth();

        $objectiveMetrics = $this->metrics->calculateFor($cfm, $periodStart, $periodEnd);
        $this->metrics->persistMetrics($cfm, $objectiveMetrics, $periodStart, $periodEnd);

        $objectiveScore = $this->metrics->weightedObjectiveScore($objectiveMetrics);
        $feedbackScore = $this->feedbackScoreFor($cfm, $periodStart, $periodEnd);
        $aoScore = $this->aoScoreFor($cfm, $periodStart, $periodEnd);

        $weights = config('cfm-effectiveness.scoring');
        $overall = round(
            ($objectiveScore * $weights['objective_weight'])
            + ($feedbackScore * $weights['feedback_weight'])
            + ($aoScore * $weights['ao_weight']),
            2,
        );

        $record = CfmEffectivenessScore::query()->updateOrCreate(
            [
                'cfm_id' => $cfm->id,
                'period_type' => $periodType,
                'period_start' => $periodStart->toDateString(),
            ],
            [
                'period_end' => $periodEnd->toDateString(),
                'objective_score' => $objectiveScore,
                'feedback_score' => $feedbackScore,
                'ao_score' => $aoScore,
                'overall_score' => $overall,
                'weights' => $weights,
                'metrics_snapshot' => $objectiveMetrics,
                'calculated_at' => now(),
            ],
        );

        return [
            'score' => $record,
            'objective_metrics' => $objectiveMetrics,
            'objective_score' => $objectiveScore,
            'feedback_score' => $feedbackScore,
            'ao_score' => $aoScore,
            'overall_score' => $overall,
        ];
    }

    public function latestFor(User $cfm): ?CfmEffectivenessScore
    {
        return CfmEffectivenessScore::query()
            ->where('cfm_id', $cfm->id)
            ->latest('period_start')
            ->first();
    }

    public function feedbackScoreFor(User $cfm, Carbon $periodStart, Carbon $periodEnd): float
    {
        $avgRating = CfmReview::query()
            ->where('cfm_id', $cfm->id)
            ->where('status', 'submitted')
            ->whereBetween('submitted_at', [$periodStart, $periodEnd])
            ->avg('average_rating');

        if ($avgRating === null) {
            return 0.0;
        }

        return round(((float) $avgRating / 5) * 100, 2);
    }

    public function aoScoreFor(User $cfm, Carbon $periodStart, Carbon $periodEnd): float
    {
        $evaluation = CfmAoEvaluation::query()
            ->where('cfm_id', $cfm->id)
            ->where('status', 'submitted')
            ->where('period_start', '<=', $periodEnd)
            ->where('period_end', '>=', $periodStart)
            ->latest('submitted_at')
            ->first();

        return (float) ($evaluation?->overall_score ?? 0);
    }
}
