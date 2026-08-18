<?php

namespace App\Http\Controllers;

use App\Models\TrainingLesson;
use App\Models\TrainingModule;
use App\Services\Training\TrainingAssessmentService;
use App\Services\Training\TrainingCoursePlayerService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrainingController extends Controller
{
    public function __construct(
        private readonly TrainingCoursePlayerService $player,
        private readonly TrainingAssessmentService $assessments,
    ) {}

    public function course(Request $request, TrainingModule $module): View
    {
        abort_unless($module->is_published && $module->status === 'published', 404);

        $module->load(['category', 'instructor', 'lessons' => fn ($query) => $query->orderBy('sort_order')]);

        $user = $request->user();
        $this->player->ensureEnrollment($user, $module);

        $courseAssessment = $module->assessments()->published()->whereHas('questions')->first();
        $assessmentStats = $courseAssessment
            ? $this->assessments->attemptStats($user, $courseAssessment)
            : null;
        $assessmentAccess = $courseAssessment
            ? $this->assessments->canTake($user, $courseAssessment)
            : null;

        return view('training.course', [
            'module' => $module,
            'lessonRows' => $this->player->lessonRows($user, $module),
            'progressPercent' => $this->player->moduleProgressPercent($user, $module),
            'courseStartDate' => $this->player->courseStartDate($user, $module),
            'courseAssessment' => $courseAssessment,
            'assessmentStats' => $assessmentStats,
            'assessmentAccess' => $assessmentAccess,
        ]);
    }

    public function lesson(Request $request, TrainingModule $module, TrainingLesson $lesson): View
    {
        abort_unless($module->is_published && $module->status === 'published', 404);
        abort_unless((int) $lesson->training_module_id === (int) $module->id, 404);

        $access = $this->player->accessState($request->user(), $module, $lesson);
        abort_unless($access['allowed'], 403);

        return view('training.lesson', [
            'module' => $module,
            'lesson' => $lesson,
        ]);
    }
}
