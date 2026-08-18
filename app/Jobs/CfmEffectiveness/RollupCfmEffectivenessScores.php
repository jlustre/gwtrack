<?php

namespace App\Jobs\CfmEffectiveness;

use App\Models\CfmEffectiveness\CfmEffectivenessScore;
use App\Models\CfmEffectiveness\CfmLeaderboard;
use App\Models\User;
use App\Services\CfmEffectiveness\CfmEffectivenessMetricsService;
use App\Services\CfmEffectiveness\CfmEffectivenessRiskService;
use App\Services\CfmEffectiveness\CfmEffectivenessScoreService;
use App\Services\CfmEffectiveness\CfmRecognitionAwardService;
use App\Support\MemberDisplayName;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RollupCfmEffectivenessScores implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $cadence = 'daily',
    ) {}

    public function handle(
        CfmEffectivenessScoreService $scores,
        CfmEffectivenessRiskService $risks,
        CfmEffectivenessMetricsService $metrics,
        CfmRecognitionAwardService $recognition,
    ): void {
        [$periodStart, $periodEnd, $periodType, $refreshLeaderboard] = $this->resolvePeriod();

        User::role('certified-field-mentor')->each(function (User $cfm) use ($scores, $risks, $periodStart, $periodEnd, $periodType): void {
            $scores->calculateAndStore($cfm, $periodStart, $periodEnd, $periodType);
            $risks->detectAndStore($cfm);
        });

        if (! $refreshLeaderboard) {
            return;
        }

        foreach (array_keys(config('cfm-effectiveness.leaderboard_metrics', [])) as $metricKey) {
            foreach ($this->leaderboardEntries($metricKey, $periodStart, $periodEnd, $metrics) as $entry) {
                CfmLeaderboard::query()->updateOrCreate(
                    [
                        'metric_key' => $metricKey,
                        'period_start' => $periodStart->toDateString(),
                        'cfm_id' => $entry['cfm_id'],
                    ],
                    [
                        'period_end' => $periodEnd->toDateString(),
                        'rank_position' => $entry['rank'],
                        'score' => $entry['score'],
                    ],
                );
            }
        }

        $recognition->awardFromLeaderboard($periodStart, $periodEnd);
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string, 3: bool}
     */
    private function resolvePeriod(): array
    {
        if ($this->cadence === 'monthly') {
            $periodStart = now()->subMonth()->startOfMonth();
            $periodEnd = now()->subMonth()->endOfMonth();

            return [$periodStart, $periodEnd, 'monthly', true];
        }

        return [now()->startOfMonth(), now()->endOfMonth(), 'monthly', false];
    }

    /**
     * @return Collection<int, array{cfm_id: int, name: string, score: float, rank: int}>
     */
    private function leaderboardEntries(
        string $metricKey,
        Carbon $periodStart,
        Carbon $periodEnd,
        CfmEffectivenessMetricsService $metrics,
    ): Collection {
        return User::role('certified-field-mentor')
            ->get()
            ->map(function (User $cfm) use ($metricKey, $periodStart, $periodEnd, $metrics): array {
                if ($metricKey === 'overall_effectiveness') {
                    $score = (float) (CfmEffectivenessScore::query()
                        ->where('cfm_id', $cfm->id)
                        ->whereDate('period_start', $periodStart)
                        ->value('overall_score') ?? 0);
                } else {
                    $metricData = $metrics->calculateFor($cfm, $periodStart, $periodEnd);

                    $score = (float) ($metricData[$metricKey]['score'] ?? 0);
                }

                return [
                    'cfm_id' => $cfm->id,
                    'name' => MemberDisplayName::for($cfm),
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->values()
            ->map(function (array $row, int $index): array {
                $row['rank'] = $index + 1;

                return $row;
            });
    }
}
