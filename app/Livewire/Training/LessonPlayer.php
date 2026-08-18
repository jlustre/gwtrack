<?php

namespace App\Livewire\Training;

use App\Models\TrainingLesson;
use App\Models\TrainingModule;
use App\Services\Training\TrainingCoursePlayerService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class LessonPlayer extends Component
{
    public TrainingModule $module;

    public TrainingLesson $lesson;

    public function mount(TrainingModule $module, TrainingLesson $lesson, TrainingCoursePlayerService $player): void
    {
        abort_unless($module->is_published && $module->status === 'published', 404);
        abort_unless((int) $lesson->training_module_id === (int) $module->id, 404);

        $access = $player->accessState(auth()->user(), $module, $lesson);
        abort_unless($access['allowed'], 403);

        $this->module = $module;
        $this->lesson = $lesson;

        $player->startLesson(auth()->user(), $module, $lesson);
    }

    public function markComplete(TrainingCoursePlayerService $player): void
    {
        $player->markLessonComplete(auth()->user(), $this->module, $this->lesson);

        session()->flash('training_status', 'lesson-completed');

        $lessons = $this->module->lessons()->orderBy('sort_order')->get()->values();
        $index = $lessons->search(fn (TrainingLesson $item) => (int) $item->id === (int) $this->lesson->id);

        if ($index !== false && $index < $lessons->count() - 1) {
            $next = $lessons[$index + 1];
            $access = $player->accessState(auth()->user(), $this->module, $next);

            if ($access['allowed']) {
                $this->redirect(route('training.lessons.show', [$this->module, $next]), navigate: true);

                return;
            }
        }

        $this->redirect(route('training.courses.show', $this->module), navigate: true);
    }

    public function reopen(TrainingCoursePlayerService $player): void
    {
        $player->reopenLesson(auth()->user(), $this->module, $this->lesson);
        session()->flash('training_status', 'lesson-reopened');
    }

    public function render(TrainingCoursePlayerService $player): View
    {
        $user = auth()->user();
        $lessons = $this->module->lessons()->orderBy('sort_order')->get()->values();
        $currentIndex = $lessons->search(fn (TrainingLesson $item) => (int) $item->id === (int) $this->lesson->id);
        $previousLesson = $currentIndex > 0 ? $lessons[$currentIndex - 1] : null;
        $nextLesson = ($currentIndex !== false && $currentIndex < $lessons->count() - 1) ? $lessons[$currentIndex + 1] : null;

        return view('livewire.training.lesson-player', [
            'progress' => $player->progressForModule($user, $this->module)->get($this->lesson->id),
            'progressPercent' => $player->moduleProgressPercent($user, $this->module),
            'lessonRows' => $player->lessonRows($user, $this->module),
            'previousLesson' => $previousLesson,
            'nextLesson' => $nextLesson,
            'nextAccessible' => $nextLesson
                ? $player->accessState($user, $this->module, $nextLesson)['allowed']
                : false,
        ]);
    }
}
