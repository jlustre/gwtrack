<?php

namespace App\Services\CfmEffectiveness;

use App\Models\CfmEffectiveness\CfmEffectivenessScore;
use App\Models\CfmEffectiveness\CfmLeaderboard;
use App\Models\CfmEffectiveness\CfmRecognitionAward;
use App\Models\CfmEffectiveness\CfmRecognitionBadge;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CfmRecognitionAwardService
{
    /**
     * @return Collection<int, CfmRecognitionAward>
     */
    public function awardFromLeaderboard(Carbon $periodStart, Carbon $periodEnd): Collection
    {
        $awarded = collect();
        $badges = CfmRecognitionBadge::query()
            ->where('is_active', true)
            ->get()
            ->keyBy('code');

        foreach (config('cfm-effectiveness.recognition_award_rules', []) as $code => $rule) {
            $badge = $badges->get($code);

            if ($badge === null) {
                continue;
            }

            $cfmId = $this->resolveWinner($rule, $periodStart, $periodEnd);

            if ($cfmId === null) {
                continue;
            }

            $existing = CfmRecognitionAward::query()
                ->where('cfm_id', $cfmId)
                ->where('badge_id', $badge->id)
                ->whereDate('awarded_for_period', $periodStart)
                ->first();

            if ($existing !== null) {
                $existing->update([
                    'note' => $this->awardNote($badge, $rule, $periodStart, $periodEnd),
                ]);

                continue;
            }

            $award = CfmRecognitionAward::query()->create([
                'cfm_id' => $cfmId,
                'badge_id' => $badge->id,
                'awarded_for_period' => $periodStart->toDateString(),
                'awarded_by' => null,
                'note' => $this->awardNote($badge, $rule, $periodStart, $periodEnd),
            ]);

            $awarded->push($award);
        }

        return $awarded;
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function resolveWinner(array $rule, Carbon $periodStart, Carbon $periodEnd): ?int
    {
        return match ($rule['type'] ?? null) {
            'leaderboard_rank' => $this->winnerForRank(
                (string) ($rule['metric_key'] ?? ''),
                $periodStart,
                (int) ($rule['rank'] ?? 1),
            ),
            'most_improved' => $this->mostImprovedWinner(
                (string) ($rule['metric_key'] ?? 'overall_effectiveness'),
                $periodStart,
                (float) ($rule['min_improvement'] ?? 0),
            ),
            default => null,
        };
    }

    private function winnerForRank(string $metricKey, Carbon $periodStart, int $rank): ?int
    {
        if ($metricKey === '') {
            return null;
        }

        $cfmId = CfmLeaderboard::query()
            ->where('metric_key', $metricKey)
            ->whereDate('period_start', $periodStart)
            ->where('rank_position', $rank)
            ->value('cfm_id');

        return $cfmId ? (int) $cfmId : null;
    }

    private function mostImprovedWinner(string $metricKey, Carbon $periodStart, float $minImprovement): ?int
    {
        if ($metricKey !== 'overall_effectiveness') {
            return null;
        }

        $previousPeriodStart = $periodStart->copy()->subMonth()->startOfMonth();
        $bestCfmId = null;
        $bestDelta = 0.0;

        User::role('certified-field-mentor')
            ->pluck('id')
            ->each(function (int $cfmId) use ($periodStart, $previousPeriodStart, $minImprovement, &$bestCfmId, &$bestDelta): void {
                $current = CfmEffectivenessScore::query()
                    ->where('cfm_id', $cfmId)
                    ->whereDate('period_start', $periodStart)
                    ->value('overall_score');

                $previous = CfmEffectivenessScore::query()
                    ->where('cfm_id', $cfmId)
                    ->whereDate('period_start', $previousPeriodStart)
                    ->value('overall_score');

                if ($current === null || $previous === null) {
                    return;
                }

                $delta = (float) $current - (float) $previous;

                if ($delta < $minImprovement || $delta <= $bestDelta) {
                    return;
                }

                $bestDelta = $delta;
                $bestCfmId = $cfmId;
            });

        return $bestCfmId;
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function awardNote(CfmRecognitionBadge $badge, array $rule, Carbon $periodStart, Carbon $periodEnd): string
    {
        $periodLabel = $periodStart->format('F Y');

        return match ($rule['type'] ?? null) {
            'most_improved' => "{$badge->name} for {$periodLabel} — largest month-over-month effectiveness improvement.",
            'leaderboard_rank' => "{$badge->name} for {$periodLabel} — rank ".($rule['rank'] ?? 1).' on '.str_replace('_', ' ', (string) ($rule['metric_key'] ?? 'leaderboard')).'.',
            default => "{$badge->name} for {$periodLabel}.",
        };
    }
}
