<?php

namespace App\Livewire\CfmEffectiveness;

use App\Models\CfmEffectiveness\CfmReview;
use App\Services\CfmEffectiveness\CfmEffectivenessFeedbackService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CfmReviewForm extends Component
{
    public CfmReview $review;

    /** @var array<int, int|null> */
    public array $ratings = [];

    public string $helpedMost = '';

    public string $improvements = '';

    public string $comments = '';

    public string $suggestions = '';

    public function mount(CfmReview $review, CfmEffectivenessFeedbackService $feedback): void
    {
        abort_unless(auth()->user()->can('submit mentor feedback'), 403);
        abort_unless($review->trainee_id === auth()->id(), 403);
        abort_unless($review->isPending(), 404);

        $this->review = $review->load(['cfm.profile', 'reviewCycle']);

        foreach ($feedback->activeQuestions() as $question) {
            $this->ratings[$question->id] = null;
        }
    }

    public function updatedRatings(): void
    {
        // Livewire updates ratings reactively for progress UI.
    }

    public function submit(CfmEffectivenessFeedbackService $feedback): void
    {
        $questions = $feedback->activeQuestions();
        $questionIds = $questions->pluck('id')->all();

        $this->validate([
            'ratings' => ['required', 'array'],
            'ratings.*' => ['nullable', 'integer', 'min:1', 'max:5'],
            'helpedMost' => ['nullable', 'string', 'max:5000'],
            'improvements' => ['nullable', 'string', 'max:5000'],
            'comments' => ['nullable', 'string', 'max:5000'],
            'suggestions' => ['nullable', 'string', 'max:5000'],
        ]);

        $missing = collect($questionIds)->filter(fn ($id) => ! array_key_exists($id, $this->ratings) || $this->ratings[$id] === null);

        if ($missing->isNotEmpty()) {
            $this->addError('ratings', 'Please rate every mentoring aspect with 1–5 stars before submitting.');

            return;
        }

        $normalized = [];
        foreach ($questionIds as $id) {
            $normalized[$id] = (int) $this->ratings[$id];
        }

        $feedback->submitReview($this->review, auth()->user(), $normalized, [
            'helped_most' => $this->helpedMost,
            'improvements' => $this->improvements,
            'comments' => $this->comments,
            'suggestions' => $this->suggestions,
        ]);

        session()->flash('cfm_effectiveness_status', 'Thank you. Your anonymous star evaluation has been submitted.');

        $this->redirect(route('cfm.effectiveness.reviews'), navigate: true);
    }

    public function render(CfmEffectivenessFeedbackService $feedback): View
    {
        $questions = $feedback->activeQuestions();
        $categories = config('cfm-effectiveness.mentoring_aspect_categories', []);

        $ratedCount = collect($this->ratings)->filter(fn ($value) => $value !== null)->count();
        $totalCount = $questions->count();
        $runningAvg = collect($this->ratings)
            ->filter(fn ($value) => $value !== null)
            ->avg();

        return view('livewire.cfm-effectiveness.review-form', [
            'questions' => $questions,
            'questionsByCategory' => $questions->groupBy(fn ($q) => $q->category ?: 'mentorship'),
            'categories' => $categories,
            'ratedCount' => $ratedCount,
            'totalCount' => $totalCount,
            'progressPercent' => $totalCount > 0 ? (int) round(($ratedCount / $totalCount) * 100) : 0,
            'runningAvg' => $runningAvg !== null ? round((float) $runningAvg, 1) : null,
        ]);
    }
}
