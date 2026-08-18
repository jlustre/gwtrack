<?php

namespace App\Services\CfmEffectiveness;

use App\Models\CfmEffectiveness\CfmAoEvaluation;
use App\Models\CfmEffectiveness\CfmReviewHistory;
use App\Models\CfmEffectiveness\CfmScorecard;
use App\Models\User;
use Carbon\Carbon;

class CfmAoEvaluationService
{
    /**
     * @param  array<string, int>  $categoryScores
     */
    public function submitEvaluation(
        User $cfm,
        User $evaluator,
        array $categoryScores,
        array $comments,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): CfmAoEvaluation {
        $overall = count($categoryScores) > 0
            ? round(collect($categoryScores)->avg(), 2)
            : 0;

        $evaluation = CfmAoEvaluation::query()->updateOrCreate(
            [
                'cfm_id' => $cfm->id,
                'evaluator_id' => $evaluator->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ],
            [
                'status' => 'submitted',
                'overall_score' => $overall,
                'category_scores' => $categoryScores,
                'strengths' => $comments['strengths'] ?? null,
                'improvement_areas' => $comments['improvement_areas'] ?? null,
                'recommendations' => $comments['recommendations'] ?? null,
                'promotion_potential' => $comments['promotion_potential'] ?? null,
                'leadership_potential' => $comments['leadership_potential'] ?? null,
                'submitted_at' => now(),
            ],
        );

        CfmScorecard::query()->updateOrCreate(
            [
                'cfm_id' => $cfm->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ],
            [
                'ao_evaluation_id' => $evaluation->id,
                'period_type' => 'quarterly',
                'categories' => $this->labeledCategories($categoryScores),
                'overall_score' => $overall,
            ],
        );

        CfmReviewHistory::query()->create([
            'cfm_id' => $cfm->id,
            'review_type' => 'ao_evaluation',
            'score' => $overall,
            'comments' => $comments['recommendations'] ?? null,
            'status' => 'completed',
            'reviewer_id' => $evaluator->id,
            'reviewable_type' => CfmAoEvaluation::class,
            'reviewable_id' => $evaluation->id,
            'reviewed_at' => now(),
        ]);

        return $evaluation;
    }

    public function latestFor(User $cfm): ?CfmAoEvaluation
    {
        return CfmAoEvaluation::query()
            ->where('cfm_id', $cfm->id)
            ->where('status', 'submitted')
            ->latest('submitted_at')
            ->first();
    }

    /**
     * @param  array<string, int>  $categoryScores
     * @return array<string, array{label: string, score: int}>
     */
    private function labeledCategories(array $categoryScores): array
    {
        $labels = config('cfm-effectiveness.ao_scorecard_categories', []);
        $result = [];

        foreach ($categoryScores as $key => $score) {
            $result[$key] = [
                'label' => $labels[$key] ?? ucfirst(str_replace('_', ' ', $key)),
                'score' => (int) $score,
            ];
        }

        return $result;
    }
}
