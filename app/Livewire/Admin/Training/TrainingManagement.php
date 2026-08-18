<?php

namespace App\Livewire\Admin\Training;

use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskUser;
use App\Models\TrainingPath;
use App\Models\User;
use App\Models\UserTrainingPathEnrollment;
use App\Services\TaskAssignmentService;
use App\Services\Training\TrainingPathService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class TrainingManagement extends Component
{
    public string $tab = 'enroll';

    public ?int $enrollUserId = null;

    public ?int $enrollPathId = null;

    public ?int $taskUserId = null;

    public ?int $taskPathId = null;

    public ?string $taskDueDate = null;

    public string $taskNotes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->can('manage training enrollments'), 403);

        $this->taskDueDate = now()->addDays(7)->format('Y-m-d');
    }

    public function enrollAssociate(TrainingPathService $paths, TaskAssignmentService $tasks): void
    {
        $this->validate([
            'enrollUserId' => ['required', 'integer', 'exists:users,id'],
            'enrollPathId' => ['required', 'integer', 'exists:training_paths,id'],
        ]);

        $actor = auth()->user();
        abort_unless($tasks->viewerCanAssignTo($actor, (int) $this->enrollUserId) || $actor->can('manage training'), 403);

        $user = User::query()->findOrFail($this->enrollUserId);
        $path = TrainingPath::query()->active()->findOrFail($this->enrollPathId);
        $alreadyEnrolled = $paths->enrollmentFor($user, $path) !== null;

        try {
            $paths->enroll($user, $path, $actor);
        } catch (ValidationException $exception) {
            $this->addError('enrollPathId', collect($exception->errors())->flatten()->first() ?: 'Unable to enroll associate.');

            return;
        }

        session()->flash(
            'training_management_status',
            $alreadyEnrolled
                ? "{$user->name} is already enrolled in {$path->name}."
                : "{$user->name} enrolled in {$path->name}."
        );

        $this->reset(['enrollUserId', 'enrollPathId']);
    }

    public function assignEnrollTask(TaskAssignmentService $tasks, TrainingPathService $paths): void
    {
        $this->validate([
            'taskUserId' => ['required', 'integer', 'exists:users,id'],
            'taskPathId' => ['required', 'integer', 'exists:training_paths,id'],
            'taskDueDate' => ['nullable', 'date'],
            'taskNotes' => ['nullable', 'string', 'max:2000'],
        ]);

        $actor = auth()->user();
        abort_unless($tasks->viewerCanAssignTo($actor, (int) $this->taskUserId) || $actor->can('manage training'), 403);

        $assignee = User::query()->findOrFail($this->taskUserId);
        $path = TrainingPath::query()->active()->findOrFail($this->taskPathId);

        if ($paths->enrollmentFor($assignee, $path)) {
            $this->addError('taskPathId', "{$assignee->name} is already enrolled in {$path->name}.");

            return;
        }

        $category = TaskCategory::query()->where('slug', 'training')->first();
        $libraryTask = Task::query()
            ->where('slug', 'enroll-in-training-path')
            ->when($category, fn ($query) => $query->where('task_category_id', $category->id))
            ->first();

        if (! $category || ! $libraryTask) {
            $this->addError('taskPathId', 'Training enroll task template is missing. Run TaskCategorySeeder and TaskSeeder.');

            return;
        }

        $pathUrl = route('training.paths.show', $path);
        $notes = trim(implode("\n\n", array_filter([
            "Please enroll in the learning path: {$path->name}.",
            "Open path: {$pathUrl}",
            $this->taskNotes !== '' ? $this->taskNotes : null,
        ])));

        $tasks->assign($actor, [
            'assignee_id' => $assignee->id,
            'task_category_id' => $category->id,
            'task_id' => $libraryTask->id,
            'priority' => $libraryTask->default_priority ?: 'high',
            'status' => 'to_do',
            'due_date' => $this->taskDueDate,
            'additional_notes' => $notes,
            'related_module' => 'Training',
        ]);

        session()->flash('training_management_status', "Enrollment task assigned to {$assignee->name} for {$path->name}.");

        $this->reset(['taskUserId', 'taskPathId', 'taskNotes']);
        $this->taskDueDate = now()->addDays(7)->format('Y-m-d');
    }

    public function render(TaskAssignmentService $tasks, TrainingPathService $paths): View
    {
        $actor = auth()->user();
        $manageableUsers = $actor->can('manage training')
            ? User::query()->where('is_active', true)->orderBy('name')->limit(300)->get(['id', 'name', 'email'])
            : $tasks->assignableUsersFor($actor);

        return view('livewire.admin.training.training-management', [
            'users' => $manageableUsers,
            'learningPaths' => $paths->activePaths(),
            'recentEnrollments' => UserTrainingPathEnrollment::query()
                ->with(['user', 'path', 'assignedBy'])
                ->latest('updated_at')
                ->limit(15)
                ->get(),
            'recentEnrollTasks' => TaskUser::query()
                ->with(['assignee', 'assignor', 'task'])
                ->whereHas('task', fn ($query) => $query->where('slug', 'enroll-in-training-path'))
                ->latest('updated_at')
                ->limit(15)
                ->get(),
        ]);
    }
}
