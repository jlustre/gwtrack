<?php

namespace App\Livewire\Admin\Training;

use App\Services\Training\TrainingAdminService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminCourseIndex extends Component
{
    public bool $showCreate = false;

    public ?int $trainingCategoryId = null;

    public string $title = '';

    public string $description = '';

    public string $courseType = 'video';

    public string $difficulty = 'beginner';

    public bool $isPublished = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('manage training'), 403);
    }

    public function createCourse(TrainingAdminService $admin): void
    {
        $this->validate([
            'trainingCategoryId' => ['required', 'integer', 'exists:training_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'courseType' => ['required', 'string'],
            'difficulty' => ['required', 'string'],
            'isPublished' => ['boolean'],
        ]);

        $module = $admin->createCourse([
            'training_category_id' => $this->trainingCategoryId,
            'title' => $this->title,
            'description' => $this->description,
            'course_type' => $this->courseType,
            'difficulty' => $this->difficulty,
            'is_published' => $this->isPublished,
        ]);

        $this->redirect(route('admin.training.courses.show', $module), navigate: true);
    }

    public function render(TrainingAdminService $admin): View
    {
        return view('livewire.admin.training.admin-course-index', [
            'courses' => $admin->coursesForAdmin(),
            'categories' => $admin->categories(),
            'courseTypes' => config('training-academy.course_types', []),
            'difficulties' => config('training-academy.difficulties', []),
        ]);
    }
}
