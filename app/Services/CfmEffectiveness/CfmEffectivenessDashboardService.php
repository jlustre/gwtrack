<?php

namespace App\Services\CfmEffectiveness;

use App\Models\CfmEffectiveness\CfmEffectivenessScore;
use App\Models\CfmEffectiveness\CfmLeaderboard;
use App\Models\CfmEffectiveness\CfmRecognitionAward;
use App\Models\CfmEffectiveness\CfmReview;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Models\UserRankProgress;
use App\Services\CfmManagementService;
use App\Support\MemberDisplayName;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CfmEffectivenessDashboardService
{
    public function __construct(
        private readonly CfmEffectivenessMetricsService $metrics,
        private readonly CfmEffectivenessScoreService $scores,
        private readonly CfmEffectivenessFeedbackService $feedback,
        private readonly CfmAoEvaluationService $aoEvaluations,
        private readonly CfmEffectivenessImprovementService $improvements,
        private readonly CfmEffectivenessRiskService $risks,
        private readonly CfmManagementService $cfmManagement,
        private readonly CfmTraineeSuccessAnalyticsService $successAnalytics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function dashboardFor(User $viewer, ?User $cfm = null): array
    {
        $cfm ??= $viewer;
        $profile = $this->cfmManagement->profileFor($cfm, $viewer);
        $scoreData = $this->scores->calculateAndStore($cfm);
        $objectiveMetrics = $scoreData['objective_metrics'];
        $feedback = $this->feedback->aggregatedFeedbackFor($cfm);
        $aoEval = $this->aoEvaluations->latestFor($cfm);
        $openRisks = $this->risks->detectAndStore($cfm);
        $actionPlans = $this->improvements->activePlansFor($cfm);
        $pendingReviews = CfmReview::query()
            ->where('cfm_id', $cfm->id)
            ->where('status', 'pending')
            ->count();

        return [
            'cfm' => $this->cfmProfileCard($cfm, $profile),
            'effectiveness_score' => $scoreData['overall_score'],
            'score_breakdown' => [
                'objective' => $scoreData['objective_score'],
                'feedback' => $scoreData['feedback_score'],
                'ao' => $scoreData['ao_score'],
            ],
            'objective_metrics' => $objectiveMetrics,
            'trainee_satisfaction' => $feedback['satisfaction_percent'],
            'feedback_summary' => $feedback,
            'ao_rating' => $aoEval?->overall_score,
            'ao_evaluation' => $aoEval,
            'open_coaching_items' => (int) ($profile['overdueTasks'] ?? 0),
            'upcoming_reviews' => $pendingReviews,
            'action_plans' => $actionPlans,
            'recommendations' => $this->improvements->recommendationsFor($cfm),
            'risks' => $openRisks,
            'badges' => CfmRecognitionAward::query()
                ->where('cfm_id', $cfm->id)
                ->with('badge')
                ->latest()
                ->limit(6)
                ->get(),
            'success_analytics' => $this->successAnalytics->summaryFor($cfm),
            'can_manage' => $viewer->can('manage CFM evaluations'),
            'is_self' => $viewer->id === $cfm->id,
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function leaderboardFor(string $metricKey = 'overall_effectiveness', ?Carbon $periodStart = null): Collection
    {
        $periodStart ??= now()->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $persisted = CfmLeaderboard::query()
            ->where('metric_key', $metricKey)
            ->whereDate('period_start', $periodStart->toDateString())
            ->whereDate('period_end', $periodEnd->toDateString())
            ->with('cfm.profile')
            ->orderBy('rank_position')
            ->limit(20)
            ->get();

        if ($persisted->isNotEmpty()) {
            return $persisted->map(fn (CfmLeaderboard $row): array => [
                'cfm_id' => $row->cfm_id,
                'name' => MemberDisplayName::for($row->cfm),
                'photo_url' => $row->cfm?->profilePhotoUrl(),
                'score' => (float) $row->score,
                'rank' => $row->rank_position,
            ]);
        }

        $cfms = User::role('certified-field-mentor')->get();

        return $cfms->map(function (User $cfm) use ($metricKey, $periodStart): array {
            if ($metricKey === 'overall_effectiveness') {
                $score = $this->scores->calculateAndStore($cfm, $periodStart, $periodStart->copy()->endOfMonth());

                return [
                    'cfm_id' => $cfm->id,
                    'name' => MemberDisplayName::for($cfm),
                    'photo_url' => $cfm->profilePhotoUrl(),
                    'score' => $score['overall_score'],
                ];
            }

            $metrics = $this->metrics->calculateFor($cfm, $periodStart, $periodStart->copy()->endOfMonth());

            return [
                'cfm_id' => $cfm->id,
                'name' => MemberDisplayName::for($cfm),
                'photo_url' => $cfm->profilePhotoUrl(),
                'score' => $metrics[$metricKey]['score'] ?? 0,
            ];
        })
            ->sortByDesc('score')
            ->values()
            ->map(function (array $row, int $index): array {
                $row['rank'] = $index + 1;

                return $row;
            });
    }

    /**
     * @return array<string, mixed>
     */
    public function agencyOverview(User $viewer): array
    {
        $cfms = User::role('certified-field-mentor')->get();
        $scores = $cfms->map(fn (User $cfm) => $this->scores->calculateAndStore($cfm)['overall_score']);
        $atRisk = $cfms->filter(fn (User $cfm) => $this->risks->openRisksFor($cfm)->isNotEmpty());

        return [
            'cfm_count' => $cfms->count(),
            'average_effectiveness' => $scores->isEmpty() ? 0 : round($scores->avg(), 1),
            'top_performers' => $this->leaderboardFor()->take(5)->values()->all(),
            'at_risk_cfms' => $atRisk->map(fn (User $cfm) => [
                'id' => $cfm->id,
                'name' => MemberDisplayName::for($cfm),
                'risks' => $this->risks->openRisksFor($cfm)->pluck('message')->all(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function cfmProfileCard(User $cfm, array $profile): array
    {
        $outcomes = $this->traineeOutcomeCounts($cfm);

        return [
            'id' => $cfm->id,
            'name' => MemberDisplayName::for($cfm),
            'photo_url' => $cfm->profilePhotoUrl(),
            'rank' => $cfm->rank?->name ?? 'Certified Field Mentor',
            'years_experience' => $cfm->joined_at ? (int) $cfm->joined_at->diffInYears(now()) : 0,
            'current_trainees' => (int) ($profile['activeApprentices'] ?? 0),
            'active_trainees' => (int) ($profile['activeApprentices'] ?? 0),
            'graduated_trainees' => (int) ($profile['completedApprentices'] ?? 0),
            'licensed_trainees' => $outcomes['licensed_trainees'],
            'promoted_trainees' => $outcomes['promoted_trainees'],
        ];
    }

    /**
     * @return array{licensed_trainees: int, promoted_trainees: int}
     */
    private function traineeOutcomeCounts(User $cfm): array
    {
        $metrics = $this->metrics->calculateFor($cfm);

        $traineeIds = MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->pluck('apprentice_id')
            ->unique()
            ->values();

        $promotedTrainees = $traineeIds->isEmpty()
            ? 0
            : UserRankProgress::query()
                ->whereIn('user_id', $traineeIds)
                ->whereIn('status', config('rank-advancement.completed_statuses', ['completed']))
                ->distinct()
                ->count('user_id');

        return [
            'licensed_trainees' => (int) ($metrics['licensing_completion_rate']['meta']['licensed'] ?? 0),
            'promoted_trainees' => $promotedTrainees,
        ];
    }
}
