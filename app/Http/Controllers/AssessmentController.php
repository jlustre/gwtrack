<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentAttempt;
use App\Services\Training\TrainingAssessmentService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssessmentController extends Controller
{
    public function __construct(private readonly TrainingAssessmentService $assessments) {}

    public function index(Request $request): View
    {
        return view('assessments.index', [
            'rows' => $this->assessments->assessmentRowsFor($request->user()),
        ]);
    }

    public function show(Request $request, Assessment $assessment): View
    {
        abort_unless($assessment->is_published && $assessment->hasQuestions(), 404);

        $assessment->load('module.category');
        $stats = $this->assessments->attemptStats($request->user(), $assessment);
        $access = $this->assessments->canTake($request->user(), $assessment);

        return view('assessments.show', [
            'assessment' => $assessment,
            'stats' => $stats,
            'canTake' => $access['allowed'],
            'lockReason' => $access['reason'],
            'maxAttempts' => config('training-academy.assessments.max_attempts'),
        ]);
    }

    public function take(Request $request, Assessment $assessment): View
    {
        abort_unless($assessment->is_published && $assessment->hasQuestions(), 404);

        $access = $this->assessments->canTake($request->user(), $assessment);
        abort_unless($access['allowed'], 403);

        return view('assessments.take', [
            'assessment' => $assessment,
        ]);
    }

    public function result(Request $request, Assessment $assessment, AssessmentAttempt $attempt): View
    {
        abort_unless($assessment->is_published, 404);
        abort_unless((int) $attempt->assessment_id === (int) $assessment->id, 404);
        abort_unless((int) $attempt->user_id === (int) $request->user()->id, 403);

        $assessment->load('module');

        return view('assessments.result', [
            'assessment' => $assessment,
            'attempt' => $attempt,
            'breakdown' => $this->assessments->attemptBreakdown($attempt),
            'canRetake' => $this->assessments->canTake($request->user(), $assessment)['allowed'],
            'certificationRecord' => \App\Models\UserTrainingCertification::query()
                ->with('certification')
                ->where('user_id', $request->user()->id)
                ->whereHas('certification', fn ($query) => $query
                    ->where('assessment_id', $assessment->id)
                    ->orWhere('training_module_id', $assessment->training_module_id))
                ->latest('updated_at')
                ->first(),
        ]);
    }
}
