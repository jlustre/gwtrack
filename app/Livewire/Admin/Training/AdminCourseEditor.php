<?php

namespace App\Livewire\Admin\Training;

use App\Models\TrainingLesson;
use App\Models\TrainingModule;
use App\Services\Training\TrainingAdminService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminCourseEditor extends Component
{
    public TrainingModule $module;

    public ?int $trainingCategoryId = null;

    public string $title = '';

    public string $slug = '';

    public string $description = '';

    public string $courseType = 'video';

    public string $difficulty = 'beginner';

    public ?int $durationMinutes = null;

    public ?int $instructorId = null;

    public int $sortOrder = 0;

    public bool $isPublished = false;

    public bool $isFeatured = false;

    public bool $sequentialRequired = true;

    public bool $dripEnabled = false;

    public string $lessonTitle = '';

    public string $lessonType = 'video';

    public string $lessonContent = '';

    public string $lessonVideoUrl = '';

    public int $lessonSortOrder = 10;

    public bool $lessonRequired = true;

    public ?int $editingLessonId = null;

    public function mount(TrainingModule $module): void
    {
        abort_unless(auth()->user()->can('manage training'), 403);

        $this->module = $module->load(['category', 'lessons', 'instructor']);
        $this->fillFromModule();
    }

    public function saveCourse(TrainingAdminService $admin): void
    {
        $this->validate([
            'trainingCategoryId' => ['required', 'integer', 'exists:training_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'courseType' => ['required', 'string'],
            'difficulty' => ['required', 'string'],
            'durationMinutes' => ['nullable', 'integer', 'min:1'],
            'instructorId' => ['nullable', 'integer', 'exists:users,id'],
            'sortOrder' => ['required', 'integer', 'min:0'],
        ]);

        $this->module = $admin->updateCourse($this->module, [
            'training_category_id' => $this->trainingCategoryId,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'course_type' => $this->courseType,
            'difficulty' => $this->difficulty,
            'duration_minutes' => $this->durationMinutes,
            'instructor_id' => $this->instructorId,
            'sort_order' => $this->sortOrder,
            'is_published' => $this->isPublished,
            'is_featured' => $this->isFeatured,
            'sequential_required' => $this->sequentialRequired,
            'drip_enabled' => $this->dripEnabled,
        ]);

        session()->flash('admin_training_status', 'course-saved');
    }

    public function saveLesson(TrainingAdminService $admin): void
    {
        $this->validate([
            'lessonTitle' => ['required', 'string', 'max:255'],
            'lessonType' => ['required', 'string'],
            'lessonContent' => ['nullable', 'string'],
            'lessonVideoUrl' => ['nullable', 'string', 'max:500'],
            'lessonSortOrder' => ['required', 'integer', 'min:0'],
        ]);

        $payload = [
            'title' => $this->lessonTitle,
            'lesson_type' => $this->lessonType,
            'content' => $this->lessonContent,
            'video_url' => $this->lessonVideoUrl !== '' ? $this->lessonVideoUrl : null,
            'sort_order' => $this->lessonSortOrder,
            'is_required' => $this->lessonRequired,
        ];

        if ($this->editingLessonId) {
            $lesson = TrainingLesson::query()->where('training_module_id', $this->module->id)->findOrFail($this->editingLessonId);
            $admin->updateLesson($lesson, $payload);
        } else {
            $admin->createLesson($this->module, $payload);
        }

        $this->resetLessonForm();
        $this->module->refresh()->load('lessons');
        session()->flash('admin_training_status', 'lesson-saved');
    }

    public function editLesson(int $lessonId): void
    {
        $lesson = $this->module->lessons->firstWhere('id', $lessonId);

        if (! $lesson) {
            return;
        }

        $this->editingLessonId = $lesson->id;
        $this->lessonTitle = $lesson->title;
        $this->lessonType = $lesson->lesson_type ?? 'video';
        $this->lessonContent = $lesson->content ?? '';
        $this->lessonVideoUrl = $lesson->video_url ?? '';
        $this->lessonSortOrder = (int) $lesson->sort_order;
        $this->lessonRequired = (bool) $lesson->is_required;
    }

    public function deleteLesson(int $lessonId, TrainingAdminService $admin): void
    {
        $lesson = TrainingLesson::query()->where('training_module_id', $this->module->id)->findOrFail($lessonId);
        $admin->deleteLesson($lesson);
        $this->module->refresh()->load('lessons');

        if ($this->editingLessonId === $lessonId) {
            $this->resetLessonForm();
        }

        session()->flash('admin_training_status', 'lesson-deleted');
    }

    public function cancelLessonEdit(): void
    {
        $this->resetLessonForm();
    }

    public function render(TrainingAdminService $admin): View
    {
        return view('livewire.admin.training.admin-course-editor', [
            'categories' => $admin->categories(),
            'instructors' => $admin->instructors(),
            'courseTypes' => config('training-academy.course_types', []),
            'difficulties' => config('training-academy.difficulties', []),
            'lessonTypes' => [
                'video' => 'Video',
                'document' => 'Document',
                'article' => 'Article',
                'interactive' => 'Interactive',
                'quiz' => 'Quiz',
            ],
        ]);
    }

    private function fillFromModule(): void
    {
        $this->trainingCategoryId = $this->module->training_category_id;
        $this->title = $this->module->title;
        $this->slug = $this->module->slug;
        $this->description = $this->module->description ?? '';
        $this->courseType = $this->module->course_type ?? 'video';
        $this->difficulty = $this->module->difficulty ?? 'beginner';
        $this->durationMinutes = $this->module->duration_minutes;
        $this->instructorId = $this->module->instructor_id;
        $this->sortOrder = (int) $this->module->sort_order;
        $this->isPublished = (bool) $this->module->is_published;
        $this->isFeatured = (bool) $this->module->is_featured;
        $this->sequentialRequired = (bool) $this->module->sequential_required;
        $this->dripEnabled = (bool) $this->module->drip_enabled;
    }

    private function resetLessonForm(): void
    {
        $this->editingLessonId = null;
        $this->lessonTitle = '';
        $this->lessonType = 'video';
        $this->lessonContent = '';
        $this->lessonVideoUrl = '';
        $this->lessonSortOrder = (($this->module->lessons->max('sort_order') ?? 0) + 10);
        $this->lessonRequired = true;
    }
}
