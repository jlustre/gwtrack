<?php

namespace App\Livewire\Training;

use App\Models\Assessment;
use App\Services\Training\TrainingAssessmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class AssessmentTaker extends Component
{
    public Assessment $assessment;

    /** @var array<int|string, array{answer_id?: int, text?: string}> */
    public array $responses = [];

    public function mount(Assessment $assessment, TrainingAssessmentService $assessments): void
    {
        abort_unless($assessment->is_published && $assessment->hasQuestions(), 404);

        $access = $assessments->canTake(auth()->user(), $assessment);
        abort_unless($access['allowed'], 403);

        $this->assessment = $assessment->load('module');
    }

    public function submit(TrainingAssessmentService $assessments): void
    {
        try {
            $attempt = $assessments->submitAttempt(auth()->user(), $this->assessment, $this->responses);
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->redirect(route('assessments.attempts.show', [$this->assessment, $attempt]), navigate: true);
    }

    public function render(TrainingAssessmentService $assessments): View
    {
        return view('livewire.training.assessment-taker', [
            'questions' => $assessments->questionsForTaking($this->assessment),
        ]);
    }
}
