<?php

namespace App\Services\CfmEffectiveness;

use App\Models\CfmEffectiveness\CfmReview;
use App\Models\CfmEffectiveness\CfmReviewCycle;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\ChecklistService;
use App\Services\Notifications\NotificationOrchestrator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CfmMilestoneReviewTriggerService
{
    public function __construct(
        private readonly CfmEffectivenessFeedbackService $feedback,
        private readonly ChecklistService $checklists,
        private readonly NotificationOrchestrator $notifications,
    ) {}

    public function onAssignmentActivated(MentorAssignment $assignment): void
    {
        $assignment->loadMissing(['mentor', 'apprentice']);

        if ($assignment->status !== 'active') {
            return;
        }

        $this->syncEligibleDayReviews($assignment);
    }

    public function maybeTriggerChecklistCompletion(User $trainee, string $typeCode): void
    {
        if (! in_array($typeCode, ['fap', 'licensing'], true)) {
            return;
        }

        $checklistIds = $this->checklists->activeChecklistIdsForType($typeCode);
        if ($this->checklists->checklistPercent($checklistIds, $trainee->id) < 100) {
            return;
        }

        $assignment = $this->activeAssignmentFor($trainee);
        if (! $assignment) {
            return;
        }

        $triggerType = $typeCode === 'fap' ? 'fap_completion' : 'licensing_completion';
        $this->createAndNotify($assignment, $triggerType);
    }

    public function onPromotionNominated(MentorAssignment $assignment): void
    {
        $assignment->loadMissing(['mentor', 'apprentice']);

        if ($assignment->status !== 'active') {
            return;
        }

        $this->createAndNotify($assignment, 'promotion');
    }

    public function requestReview(MentorAssignment $assignment, User $requestedBy, string $triggerType = 'ao_requested'): ?CfmReview
    {
        return $this->createAndNotify($assignment, $triggerType, $requestedBy);
    }

    public function syncEligibleDayReviews(MentorAssignment $assignment): void
    {
        if (! $assignment->started_at) {
            return;
        }

        $days = (int) Carbon::parse($assignment->started_at)->diffInDays(now());

        foreach ([30 => '30_day', 60 => '60_day', 90 => '90_day'] as $threshold => $trigger) {
            if ($days >= $threshold) {
                $this->createAndNotify($assignment, $trigger);
            }
        }
    }

    public function syncAllActiveAssignments(): int
    {
        $created = 0;

        MentorAssignment::query()
            ->where('status', 'active')
            ->with(['mentor', 'apprentice'])
            ->each(function (MentorAssignment $assignment) use (&$created): void {
                $before = CfmReview::query()->count();
                $this->syncEligibleDayReviews($assignment);
                $created += CfmReview::query()->count() - $before;
            });

        return $created;
    }

    private function createAndNotify(
        MentorAssignment $assignment,
        string $triggerType,
        ?User $requestedBy = null,
    ): ?CfmReview {
        if ($this->reviewExists($assignment, $triggerType)) {
            return null;
        }

        $review = $this->feedback->createMilestoneReview($assignment, $triggerType, $requestedBy);
        $this->attachReviewCycle($review, $triggerType);
        $this->notifyTraineeOfReview($review);

        return $review;
    }

    private function reviewExists(MentorAssignment $assignment, string $triggerType): bool
    {
        return CfmReview::query()
            ->where('cfm_id', $assignment->mentor_id)
            ->where('trainee_id', $assignment->apprentice_id)
            ->where('trigger_type', $triggerType)
            ->exists();
    }

    private function activeAssignmentFor(User $trainee): ?MentorAssignment
    {
        if (! $trainee->mentor_id) {
            return null;
        }

        return MentorAssignment::query()
            ->where('mentor_id', $trainee->mentor_id)
            ->where('apprentice_id', $trainee->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();
    }

    private function attachReviewCycle(CfmReview $review, string $triggerType): void
    {
        if ($review->review_cycle_id) {
            return;
        }

        $cycleId = CfmReviewCycle::query()
            ->where('trigger_type', $triggerType)
            ->where('is_active', true)
            ->value('id');

        if ($cycleId) {
            $review->update(['review_cycle_id' => $cycleId]);
        }
    }

    private function notifyTraineeOfReview(CfmReview $review): void
    {
        $review->loadMissing(['trainee', 'cfm', 'reviewCycle']);

        try {
            $this->notifications->dispatch('mentor_feedback_requested', [
                'queue' => true,
                'recipients' => [$review->trainee_id],
                'module' => 'cfm_effectiveness',
                'priority' => 'medium',
                'related' => ['type' => CfmReview::class, 'id' => $review->id],
                'related_user_id' => $review->cfm_id,
                'template_data' => [
                    'trainee_name' => $review->trainee->name,
                    'cfm_name' => $review->cfm->name,
                    'review_label' => config("cfm-effectiveness.review_triggers.{$review->trigger_type}.label")
                        ?? $review->reviewCycle?->name
                        ?? ucfirst(str_replace('_', ' ', $review->trigger_type)),
                    'due_date' => $review->due_at?->format('M j, Y') ?? now()->addDays(14)->format('M j, Y'),
                ],
                'action_link' => [
                    'route' => 'cfm.effectiveness.reviews.show',
                    'params' => ['review' => $review->id],
                    'label' => 'Complete review',
                ],
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Unable to notify trainee of mentor feedback request.', [
                'review_id' => $review->id,
                'trainee_id' => $review->trainee_id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
