<?php

namespace App\Livewire\Tasks;

use App\Livewire\Dashboard\MyTasksModal;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskAssignmentService;
use App\Services\TaskCategoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class AssignTaskModal extends Component
{
    private const SEARCH_MIN_LENGTH = 3;

    public bool $show = false;

    public ?int $assigneeId = null;

    public string $assigneeSearch = '';

    public bool $assigneePickerOpen = false;

    public string $taskCategorySelection = '';

    public ?int $taskCategoryId = null;

    public string $newCategoryName = '';

    public string $taskTemplateSelection = '';

    public ?int $taskId = null;

    public string $newTaskTemplateTitle = '';

    public string $priority = 'medium';

    public string $status = 'to_do';

    public ?string $dueDate = null;

    public string $additionalNotes = '';

    public string $relatedPerson = '';

    public string $relatedPersonSearch = '';

    public bool $relatedPersonPickerOpen = false;

    public string $relatedModuleSelection = '';

    public string $relatedModuleOther = '';

    public ?string $selectedTaskDescription = null;

    #[On('open-assign-task-modal')]
    public function open(): void
    {
        $this->resetForm();
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
        $this->assigneePickerOpen = false;
        $this->relatedPersonPickerOpen = false;
    }

    public function updatedAssigneeSearch(): void
    {
        if ($this->assigneeId && $this->assigneeSearch !== $this->assigneeLabelFor($this->assigneeId)) {
            $this->assigneeId = null;
        }

        $this->assigneePickerOpen = strlen(trim($this->assigneeSearch)) >= self::SEARCH_MIN_LENGTH;
    }

    public function updatedRelatedPersonSearch(): void
    {
        if ($this->relatedPerson && $this->relatedPersonSearch !== $this->relatedPerson) {
            $this->relatedPerson = '';
        }

        $this->relatedPersonPickerOpen = strlen(trim($this->relatedPersonSearch)) >= self::SEARCH_MIN_LENGTH;
    }

    public function updatedTaskCategorySelection(): void
    {
        if ($this->taskCategorySelection === MyTasksModal::CREATE_ONE) {
            $this->taskCategoryId = null;
        } elseif (filled($this->taskCategorySelection)) {
            $this->taskCategoryId = (int) $this->taskCategorySelection;
        } else {
            $this->taskCategoryId = null;
        }

        $this->taskTemplateSelection = '';
        $this->taskId = null;
        $this->newTaskTemplateTitle = '';
        $this->selectedTaskDescription = null;
    }

    public function updatedTaskTemplateSelection(): void
    {
        if ($this->taskTemplateSelection === MyTasksModal::CREATE_ONE) {
            $this->taskId = null;
            $this->selectedTaskDescription = null;

            return;
        }

        if (filled($this->taskTemplateSelection)) {
            $this->taskId = (int) $this->taskTemplateSelection;
            $this->syncDefaultsFromTask();
        } else {
            $this->taskId = null;
            $this->selectedTaskDescription = null;
        }
    }

    public function openAssigneePicker(): void
    {
        if (strlen(trim($this->assigneeSearch)) >= self::SEARCH_MIN_LENGTH) {
            $this->assigneePickerOpen = true;
        }
    }

    public function openRelatedPersonPicker(): void
    {
        if (strlen(trim($this->relatedPersonSearch)) >= self::SEARCH_MIN_LENGTH) {
            $this->relatedPersonPickerOpen = true;
        }
    }

    public function selectAssignee(int $assigneeId): void
    {
        $this->assigneeId = $assigneeId;
        $this->assigneeSearch = $this->assigneeLabelFor($assigneeId);
        $this->assigneePickerOpen = false;
        $this->resetValidation('assigneeId');
    }

    public function selectRelatedPerson(string $label): void
    {
        $this->relatedPerson = $label;
        $this->relatedPersonSearch = $label;
        $this->relatedPersonPickerOpen = false;
        $this->resetValidation('relatedPerson');
    }

    public function assign(TaskAssignmentService $assignments, TaskCategoryService $taskCategories): void
    {
        $user = auth()->user();

        abort_unless($user, 403);

        $this->validate([
            'assigneeId' => ['required', 'integer', 'exists:users,id'],
            'taskCategorySelection' => ['required', Rule::in(array_merge(
                $taskCategories->activeCategories()->pluck('id')->map(fn ($id) => (string) $id)->all(),
                [MyTasksModal::CREATE_ONE],
            ))],
            'newCategoryName' => ['required_if:taskCategorySelection,'.MyTasksModal::CREATE_ONE, 'nullable', 'string', 'max:120'],
            'taskTemplateSelection' => ['required', Rule::in(array_merge(
                $this->libraryTaskSelectionOptions(),
                [MyTasksModal::CREATE_ONE],
            ))],
            'newTaskTemplateTitle' => ['required_if:taskTemplateSelection,'.MyTasksModal::CREATE_ONE, 'nullable', 'string', 'max:191'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'status' => ['required', 'in:to_do,in_progress,waiting,overdue,completed,cancelled'],
            'dueDate' => ['nullable', 'date'],
            'additionalNotes' => ['nullable', 'string', 'max:5000'],
            'relatedPerson' => ['nullable', 'string', 'max:120'],
            'relatedModuleSelection' => ['nullable', Rule::in(array_merge(MyTasksModal::RELATED_MODULES, [MyTasksModal::MODULE_OTHER, '']))],
            'relatedModuleOther' => ['required_if:relatedModuleSelection,'.MyTasksModal::MODULE_OTHER, 'nullable', 'string', 'max:60'],
        ]);

        if (! $assignments->viewerCanAssignTo($user, (int) $this->assigneeId)) {
            $this->addError('assigneeId', 'You do not have permission to assign tasks to this member.');

            return;
        }

        $categoryId = $this->resolveCategoryId($taskCategories);
        $taskId = $this->resolveTaskId($categoryId);

        $assignments->assign($user, [
            'assignee_id' => $this->assigneeId,
            'task_category_id' => $categoryId,
            'task_id' => $taskId,
            'priority' => $this->priority,
            'status' => $this->status,
            'due_date' => $this->dueDate ?: null,
            'additional_notes' => $this->additionalNotes ?: null,
            'related_person' => $this->relatedPerson ?: null,
            'related_module' => $this->resolveRelatedModuleValue(),
        ]);

        $this->dispatch('task-assigned');
        $this->close();
        session()->flash('task_assignment_status', 'Task assigned to '.$this->assigneeLabelFor((int) $this->assigneeId).'.');
    }

    public function render(
        TaskAssignmentService $assignments,
        TaskCategoryService $taskCategories,
    ): View {
        $viewer = auth()->user();
        $assigneeNeedle = trim($this->assigneeSearch);
        $relatedPersonNeedle = trim($this->relatedPersonSearch);

        return view('livewire.tasks.assign-task-modal', [
            'assigneeOptions' => $viewer && strlen($assigneeNeedle) >= self::SEARCH_MIN_LENGTH
                ? $assignments->searchAssignableUsersFor($viewer, $assigneeNeedle)
                : collect(),
            'relatedPersonOptions' => $viewer && strlen($relatedPersonNeedle) >= self::SEARCH_MIN_LENGTH
                ? $assignments->searchRelatedPeopleFor($viewer, $relatedPersonNeedle)
                : collect(),
            'libraryTasks' => $assignments->libraryTasksForCategory($this->taskCategoryId),
            'categories' => $taskCategories->activeCategories(),
            'relatedModules' => MyTasksModal::RELATED_MODULES,
            'priorities' => [
                'low' => 'Low',
                'medium' => 'Medium',
                'high' => 'High',
                'urgent' => 'Urgent',
            ],
            'statuses' => [
                'to_do' => 'To Do',
                'in_progress' => 'In Progress',
                'waiting' => 'Waiting',
                'overdue' => 'Overdue',
            ],
        ]);
    }

    private function resetForm(): void
    {
        $user = auth()->user();

        $this->assigneeId = $user?->id;
        $this->assigneeSearch = $this->assigneeId ? $this->assigneeLabelFor($this->assigneeId) : '';
        $this->assigneePickerOpen = false;
        $this->taskCategorySelection = '';
        $this->taskCategoryId = null;
        $this->newCategoryName = '';
        $this->taskTemplateSelection = '';
        $this->taskId = null;
        $this->newTaskTemplateTitle = '';
        $this->priority = 'medium';
        $this->status = 'to_do';
        $this->dueDate = now()->addDays(3)->toDateString();
        $this->additionalNotes = '';
        $this->relatedPerson = '';
        $this->relatedPersonSearch = '';
        $this->relatedPersonPickerOpen = false;
        $this->relatedModuleSelection = '';
        $this->relatedModuleOther = '';
        $this->selectedTaskDescription = null;
        $this->resetValidation();
    }

    private function syncDefaultsFromTask(): void
    {
        if (! $this->taskId) {
            $this->selectedTaskDescription = null;

            return;
        }

        $task = Task::query()->find($this->taskId);

        if (! $task) {
            $this->selectedTaskDescription = null;

            return;
        }

        $this->selectedTaskDescription = $task->description;
        $this->priority = in_array($task->default_priority, ['low', 'medium', 'high', 'urgent'], true)
            ? $task->default_priority
            : 'medium';

        if (filled($task->related_module)) {
            if (in_array($task->related_module, MyTasksModal::RELATED_MODULES, true)) {
                $this->relatedModuleSelection = $task->related_module;
                $this->relatedModuleOther = '';
            } else {
                $this->relatedModuleSelection = MyTasksModal::MODULE_OTHER;
                $this->relatedModuleOther = $task->related_module;
            }
        }
    }

    private function assigneeLabelFor(int $assigneeId): string
    {
        return User::query()->whereKey($assigneeId)->value('name') ?? '';
    }

    private function resolveCategoryId(TaskCategoryService $taskCategories): int
    {
        if ($this->taskCategorySelection === MyTasksModal::CREATE_ONE) {
            return $taskCategories->createCategory(trim($this->newCategoryName))->id;
        }

        return (int) $this->taskCategoryId;
    }

    private function resolveTaskId(int $categoryId): int
    {
        if ($this->taskTemplateSelection === MyTasksModal::CREATE_ONE) {
            return $this->createOneOffTask($categoryId);
        }

        return (int) $this->taskId;
    }

    private function createOneOffTask(int $categoryId): int
    {
        $title = trim($this->newTaskTemplateTitle);

        $task = Task::query()->create([
            'task_category_id' => $categoryId,
            'title' => $title,
            'description' => null,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(8)),
            'default_priority' => $this->priority,
            'related_module' => $this->resolveRelatedModuleValue(),
            'sort_order' => 0,
            'is_active' => false,
        ]);

        return $task->id;
    }

    private function resolveRelatedModuleValue(): ?string
    {
        if ($this->relatedModuleSelection === MyTasksModal::MODULE_OTHER) {
            $value = trim($this->relatedModuleOther);

            return $value !== '' ? $value : null;
        }

        return filled($this->relatedModuleSelection) ? $this->relatedModuleSelection : null;
    }

    /**
     * @return list<string>
     */
    private function libraryTaskSelectionOptions(): array
    {
        if (! $this->taskCategoryId) {
            return [];
        }

        return Task::query()
            ->where('task_category_id', $this->taskCategoryId)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->values()
            ->all();
    }
}
