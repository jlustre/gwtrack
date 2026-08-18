<?php

namespace App\Livewire\CfmEffectiveness;

use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\CfmEffectiveness\CfmEffectivenessFeedbackService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CfmReviewCenter extends Component
{
    public string $statusMessage = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('view own mentor feedback requests'), 403);
    }

    public function startEvaluation(CfmEffectivenessFeedbackService $feedback): void
    {
        abort_unless(auth()->user()->can('submit mentor feedback'), 403);

        $review = $feedback->ensureTraineeInitiatedReview(auth()->user());

        if (! $review) {
            $this->statusMessage = 'No CFM mentor is assigned to your account yet. Ask your sponsor or team leader to assign a Certified Field Mentor first.';

            return;
        }

        $this->redirect(route('cfm.effectiveness.reviews.show', $review), navigate: true);
    }

    public function render(CfmEffectivenessFeedbackService $feedback): View
    {
        $user = auth()->user();

        $assignment = MentorAssignment::query()
            ->with('mentor.profile')
            ->where('apprentice_id', $user->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        $cfm = $assignment?->mentor
            ?? ($user->mentor_id ? User::query()->with('profile')->find($user->mentor_id) : null);

        return view('livewire.cfm-effectiveness.review-center', [
            'pendingReviews' => $feedback->pendingReviewsFor($user),
            'completedReviews' => $feedback->completedReviewsFor($user),
            'cfm' => $cfm,
            'aspectCount' => $feedback->activeQuestions()->count(),
            'categories' => config('cfm-effectiveness.mentoring_aspect_categories', []),
        ]);
    }
}
