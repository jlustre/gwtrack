<?php

namespace App\Services\CfmEffectiveness;

use App\Models\CfmEffectiveness\CfmEffectivenessActionPlan;
use App\Models\CfmEffectiveness\CfmEffectivenessRisk;
use App\Models\CfmEffectiveness\CfmEffectivenessScore;
use App\Models\CfmEffectiveness\CfmImprovementArea;
use App\Models\User;
use Illuminate\Support\Collection;

class CfmEffectivenessImprovementService
{
    public function __construct(
        private readonly CfmEffectivenessMetricsService $metrics,
        private readonly CfmEffectivenessFeedbackService $feedback,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function recommendationsFor(User $cfm): array
    {
        $objectiveMetrics = $this->metrics->calculateFor($cfm);
        $feedback = $this->feedback->aggregatedFeedbackFor($cfm);
        $recommendations = [];

        foreach ($objectiveMetrics as $key => $metric) {
            if (($metric['score'] ?? 100) >= 75) {
                continue;
            }

            $recommendations[] = [
                'area' => $metric['label'],
                'metric_key' => $key,
                'current_score' => $metric['score'],
                'priority' => ($metric['score'] ?? 0) < 50 ? 'high' : 'medium',
                'suggestion' => $this->suggestionForMetric($key),
            ];
        }

        foreach ($feedback['improvement_areas'] as $area) {
            $recommendations[] = [
                'area' => $area->label,
                'metric_key' => 'feedback',
                'current_score' => null,
                'priority' => 'medium',
                'suggestion' => "Trainee feedback suggests improving {$area->label}.",
            ];
        }

        return collect($recommendations)->unique('area')->values()->all();
    }

    public function generateActionPlans(User $cfm, User $creator, int $limit = 3): Collection
    {
        $recommendations = collect($this->recommendationsFor($cfm))->take($limit);

        return $recommendations->map(function (array $rec) use ($cfm, $creator): CfmEffectivenessActionPlan {
            return CfmEffectivenessActionPlan::query()->firstOrCreate(
                [
                    'cfm_id' => $cfm->id,
                    'improvement_area' => $rec['area'],
                    'status' => 'active',
                ],
                [
                    'target_outcome' => "Improve {$rec['area']} score to 80+ within 90 days.",
                    'action_steps' => [
                        ['step' => $rec['suggestion'], 'completed' => false],
                        ['step' => 'Review trainee progress weekly and document coaching notes.', 'completed' => false],
                        ['step' => 'Schedule follow-up mentor sessions for at-risk trainees.', 'completed' => false],
                    ],
                    'due_date' => now()->addDays(90)->toDateString(),
                    'created_by' => $creator->id,
                ],
            );
        });
    }

    /**
     * @return list<CfmEffectivenessActionPlan>
     */
    public function activePlansFor(User $cfm): array
    {
        return CfmEffectivenessActionPlan::query()
            ->where('cfm_id', $cfm->id)
            ->where('status', 'active')
            ->orderBy('due_date')
            ->get()
            ->all();
    }

    private function suggestionForMetric(string $key): string
    {
        return match ($key) {
            'retention_rate' => 'Increase check-in frequency and early intervention for disengaged trainees.',
            'fap_completion_rate' => 'Review FAP milestones with each trainee and assign weekly coaching tasks.',
            'licensing_completion_rate' => 'Partner with trainees on licensing checklist deadlines and study plans.',
            'meeting_completion_rate' => 'Protect mentor meeting blocks and confirm sessions 24 hours in advance.',
            'responsiveness_score' => 'Aim to respond to trainee messages within 4 hours during business days.',
            'coaching_activity_score' => 'Log coaching notes, assign follow-up tasks, and complete progress reviews.',
            'goal_influence_score' => 'Review trainee goals weekly and align coaching to goal milestones.',
            'promotion_development_score' => 'Identify promotion-ready trainees and create development action plans.',
            default => 'Focus coaching efforts on this area over the next 30 days.',
        };
    }
}
