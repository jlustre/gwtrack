<?php

namespace App\Services\CfmEffectiveness;

use App\Models\CfmEffectiveness\CfmFeedbackQuestion;
use App\Models\CfmEffectiveness\CfmFeedbackResponse;
use App\Models\CfmEffectiveness\CfmImprovementArea;
use App\Models\CfmEffectiveness\CfmReview;
use App\Models\CfmEffectiveness\CfmReviewHistory;
use App\Models\CfmEffectiveness\CfmStrength;
use App\Models\MentorAssignment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CfmEffectivenessFeedbackService
{
    /**
     * @return Collection<int, CfmReview>
     */
    public function pendingReviewsFor(User $trainee): Collection
    {
        return CfmReview::query()
            ->where('trainee_id', $trainee->id)
            ->where('status', 'pending')
            ->with(['cfm.profile', 'reviewCycle'])
            ->orderBy('due_at')
            ->get();
    }

    /**
     * @param  array<int, int>  $ratings  question_id => rating
     */
    public function submitReview(CfmReview $review, User $trainee, array $ratings, array $openFeedback = []): CfmReview
    {
        abort_unless($review->trainee_id === $trainee->id, 403);
        abort_unless($review->isPending(), 422);

        return DB::transaction(function () use ($review, $ratings, $openFeedback): CfmReview {
            foreach ($ratings as $questionId => $rating) {
                CfmFeedbackResponse::query()->updateOrCreate(
                    [
                        'cfm_review_id' => $review->id,
                        'question_id' => (int) $questionId,
                    ],
                    ['rating' => max(0, min(5, (int) $rating))],
                );
            }

            $average = CfmFeedbackResponse::query()
                ->where('cfm_review_id', $review->id)
                ->avg('rating');

            $review->update([
                'status' => 'submitted',
                'submitted_at' => now(),
                'average_rating' => round((float) $average, 2),
                'helped_most' => $openFeedback['helped_most'] ?? null,
                'improvements' => $openFeedback['improvements'] ?? null,
                'comments' => $openFeedback['comments'] ?? null,
                'suggestions' => $openFeedback['suggestions'] ?? null,
                'analysis_summary' => $this->buildAnalysisSummary($review->cfm_id),
            ]);

            CfmReviewHistory::query()->create([
                'cfm_id' => $review->cfm_id,
                'review_type' => 'trainee_feedback',
                'score' => $review->average_rating,
                'comments' => $openFeedback['comments'] ?? null,
                'status' => 'completed',
                'reviewer_id' => $review->trainee_id,
                'reviewable_type' => CfmReview::class,
                'reviewable_id' => $review->id,
                'reviewed_at' => now(),
            ]);

            return $review->fresh(['responses.question']);
        });
    }

    public function createMilestoneReview(
        MentorAssignment $assignment,
        string $triggerType,
        ?User $requestedBy = null,
    ): CfmReview {
        return CfmReview::query()->firstOrCreate(
            [
                'cfm_id' => $assignment->mentor_id,
                'trainee_id' => $assignment->apprentice_id,
                'trigger_type' => $triggerType,
            ],
            [
                'mentor_assignment_id' => $assignment->id,
                'status' => 'pending',
                'due_at' => now()->addDays(14),
                'requested_by' => $requestedBy?->id,
            ],
        );
    }

    /**
     * Request a new AO/admin-driven trainee evaluation (or reuse an existing pending one).
     */
    public function requestAdminEvaluation(MentorAssignment $assignment, ?User $requestedBy = null): CfmReview
    {
        $pending = CfmReview::query()
            ->where('cfm_id', $assignment->mentor_id)
            ->where('trainee_id', $assignment->apprentice_id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if ($pending) {
            return $pending;
        }

        return CfmReview::query()->create([
            'cfm_id' => $assignment->mentor_id,
            'trainee_id' => $assignment->apprentice_id,
            'mentor_assignment_id' => $assignment->id,
            'trigger_type' => 'ao_requested',
            'status' => 'pending',
            'due_at' => now()->addDays(14),
            'requested_by' => $requestedBy?->id,
        ]);
    }

    /**
     * Create or reuse a pending trainee-initiated CFM evaluation for the associate's current mentor.
     */
    public function ensureTraineeInitiatedReview(User $trainee): ?CfmReview
    {
        $assignment = MentorAssignment::query()
            ->where('apprentice_id', $trainee->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        $cfmId = $assignment?->mentor_id ?: $trainee->mentor_id;

        if (! $cfmId) {
            return null;
        }

        $pending = CfmReview::query()
            ->where('trainee_id', $trainee->id)
            ->where('cfm_id', $cfmId)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if ($pending) {
            return $pending;
        }

        return CfmReview::query()->create([
            'cfm_id' => $cfmId,
            'trainee_id' => $trainee->id,
            'mentor_assignment_id' => $assignment?->id,
            'trigger_type' => 'trainee_requested',
            'status' => 'pending',
            'due_at' => now()->addDays(14),
            'requested_by' => $trainee->id,
        ]);
    }

    /**
     * @return Collection<int, CfmReview>
     */
    public function completedReviewsFor(User $trainee): Collection
    {
        return CfmReview::query()
            ->where('trainee_id', $trainee->id)
            ->where('status', 'submitted')
            ->with(['cfm.profile', 'reviewCycle'])
            ->latest('submitted_at')
            ->limit(20)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function aggregatedFeedbackFor(User $cfm): array
    {
        $reviews = CfmReview::query()
            ->where('cfm_id', $cfm->id)
            ->where('status', 'submitted')
            ->with('responses.question')
            ->latest('submitted_at')
            ->limit(50)
            ->get();

        $questionAverages = CfmFeedbackResponse::query()
            ->whereIn('cfm_review_id', $reviews->pluck('id'))
            ->selectRaw('question_id, AVG(rating) as avg_rating, COUNT(*) as response_count')
            ->groupBy('question_id')
            ->with('question')
            ->get();

        $overallAvg = $reviews->avg('average_rating');

        return [
            'review_count' => $reviews->count(),
            'overall_average' => $overallAvg ? round((float) $overallAvg, 2) : null,
            'satisfaction_percent' => $overallAvg ? round(((float) $overallAvg / 5) * 100) : null,
            'question_averages' => $questionAverages->map(fn ($row) => [
                'question' => $row->question?->question,
                'average' => round((float) $row->avg_rating, 2),
                'count' => (int) $row->response_count,
            ])->values()->all(),
            'strengths' => CfmStrength::query()->where('cfm_id', $cfm->id)->orderByDesc('mention_count')->limit(5)->get(),
            'improvement_areas' => CfmImprovementArea::query()->where('cfm_id', $cfm->id)->orderByDesc('mention_count')->limit(5)->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildAnalysisSummary(int $cfmId): array
    {
        $reviews = CfmReview::query()
            ->where('cfm_id', $cfmId)
            ->where('status', 'submitted')
            ->latest('submitted_at')
            ->limit(20)
            ->get();

        $strengthKeywords = ['encouraging', 'responsive', 'organized', 'supportive', 'available', 'helpful'];
        $improvementKeywords = ['follow-up', 'follow up', 'response', 'prospecting', 'communication', 'availability'];

        $strengthCounts = [];
        $improvementCounts = [];

        foreach ($reviews as $review) {
            $this->tallyKeywords($review->helped_most ?? '', $strengthKeywords, $strengthCounts);
            $this->tallyKeywords($review->improvements ?? '', $improvementKeywords, $improvementCounts);
            $this->tallyKeywords($review->suggestions ?? '', $improvementKeywords, $improvementCounts);
        }

        arsort($strengthCounts);
        arsort($improvementCounts);

        foreach (array_slice($strengthCounts, 0, 5, true) as $label => $count) {
            CfmStrength::query()->updateOrCreate(
                ['cfm_id' => $cfmId, 'label' => ucfirst($label), 'source' => 'feedback'],
                ['mention_count' => $count, 'last_identified_at' => now()],
            );
        }

        foreach (array_slice($improvementCounts, 0, 5, true) as $label => $count) {
            CfmImprovementArea::query()->updateOrCreate(
                ['cfm_id' => $cfmId, 'label' => ucfirst($label), 'source' => 'feedback'],
                ['mention_count' => $count, 'last_identified_at' => now()],
            );
        }

        return [
            'common_strengths' => array_keys(array_slice($strengthCounts, 0, 3, true)),
            'common_improvements' => array_keys(array_slice($improvementCounts, 0, 3, true)),
            'sample_size' => $reviews->count(),
        ];
    }

    /**
     * @return Collection<int, CfmFeedbackQuestion>
     */
    public function activeQuestions(): Collection
    {
        return CfmFeedbackQuestion::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @param  list<string>  $keywords
     * @param  array<string, int>  $counts
     */
    private function tallyKeywords(string $text, array $keywords, array &$counts): void
    {
        $lower = strtolower($text);

        foreach ($keywords as $keyword) {
            if (str_contains($lower, strtolower($keyword))) {
                $counts[$keyword] = ($counts[$keyword] ?? 0) + 1;
            }
        }
    }
}
