<?php

namespace App\Livewire\Dashboard;

use App\Http\Controllers\TaskController;
use App\Models\Task;
use App\Models\TaskUser;
use App\Models\User;
use App\Services\AssignedTasksTrackingService;
use App\Services\TaskAssignmentService;
use App\Services\TaskCategoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class MyTasksModal extends Component
{
    private const SEARCH_MIN_LENGTH = 3;

    public const CREATE_ONE = 'create_one';

    public const MODULE_OTHER = 'other';

    /** @var list<string> */
    public const RELATED_MODULES = [
        'Prospects',
        'Team',
        'Training',
        'Rank Advancement',
        'Licensing',
        'FNA',
        'Profile',
        'Onboarding',
    ];

    public bool $show = false;

    public bool $showCreateForm = false;

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

    public ?string $dueDate = null;

    public string $additionalNotes = '';

    public string $relatedPerson = '';

    public string $relatedPersonSearch = '';

    public bool $relatedPersonPickerOpen = false;

    public string $relatedModuleSelection = '';

    public string $relatedModuleOther = '';

    public ?string $selectedTaskDescription = null;

    #[On('open-my-tasks-modal')]
    public function open(bool $createForm = false): void
    {
        $this->show = true;

        if ($createForm) {
            $this->resetCreateForm();
            $this->showCreateForm = true;

            return;
        }

        $this->showCreateForm = false;
    }

    #[On('task-assigned')]
    public function refreshAssignedTasks(): void
    {
        // Re-render to refresh delegated task tracking table.
    }

    #[On('invitation-email-sent')]
    public function refreshAfterInvitationEmail(): void
    {
        session()->flash('my_tasks_status', 'Invitation email sent. Task completed.');
    }

    #[On('invitation-deleted')]
    public function refreshAfterInvitationDeleted(): void
    {
        session()->flash('my_tasks_status', 'Invitation link deleted. Task completed.');
    }

    public function openInvitationEmailModal(int $invitationId): void
    {
        $this->dispatch('open-invite-member-modal', invitationId: $invitationId);
    }

    public function close(): void
    {
        $this->show = false;
        $this->showCreateForm = false;
        $this->assigneePickerOpen = false;
        $this->relatedPersonPickerOpen = false;
    }

    public function toggleCreateForm(): void
    {
        if (! $this->showCreateForm) {
            $this->resetCreateForm();
        }

        $this->showCreateForm = ! $this->showCreateForm;
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
        if ($this->taskCategorySelection === self::CREATE_ONE) {
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
        if ($this->taskTemplateSelection === self::CREATE_ONE) {
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

    public function createTask(TaskAssignmentService $assignments, TaskCategoryService $taskCategories): void
    {
        $user = auth()->user();

        abort_unless($user, 403);

        $this->validate([
            'assigneeId' => ['required', 'integer', 'exists:users,id'],
            'taskCategorySelection' => ['required', Rule::in(array_merge(
                $taskCategories->activeCategories()->pluck('id')->map(fn ($id) => (string) $id)->all(),
                [self::CREATE_ONE],
            ))],
            'newCategoryName' => ['required_if:taskCategorySelection,'.self::CREATE_ONE, 'nullable', 'string', 'max:120'],
            'taskTemplateSelection' => ['required', Rule::in(array_merge(
                $this->libraryTaskSelectionOptions(),
                [self::CREATE_ONE],
            ))],
            'newTaskTemplateTitle' => ['required_if:taskTemplateSelection,'.self::CREATE_ONE, 'nullable', 'string', 'max:191'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'dueDate' => ['nullable', 'date'],
            'additionalNotes' => ['nullable', 'string', 'max:5000'],
            'relatedPerson' => ['nullable', 'string', 'max:120'],
            'relatedModuleSelection' => ['nullable', Rule::in(array_merge(self::RELATED_MODULES, [self::MODULE_OTHER, '']))],
            'relatedModuleOther' => ['required_if:relatedModuleSelection,'.self::MODULE_OTHER, 'nullable', 'string', 'max:60'],
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
            'status' => 'to_do',
            'due_date' => $this->dueDate ?: null,
            'additional_notes' => $this->additionalNotes ?: null,
            'related_person' => $this->relatedPerson ?: null,
            'related_module' => $this->resolveRelatedModuleValue(),
        ]);

        $this->showCreateForm = false;
        $this->resetCreateForm();
        session()->flash('my_tasks_status', 'Task created.');
    }

    public function completeTask(int $taskUserId): void
    {
        $task = TaskUser::query()
            ->whereKey($taskUserId)
            ->where('assignee_id', auth()->id())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->firstOrFail();

        $task->forceFill([
            'status' => 'completed',
            'progress' => 100,
            'completed_at' => now(),
        ])->save();

        session()->flash('my_tasks_status', 'Task marked complete.');
        $this->dispatch('my-tasks-changed');
    }

    public function render(
        TaskController $tasks,
        TaskAssignmentService $assignments,
        TaskCategoryService $taskCategories,
        AssignedTasksTrackingService $assignedTasksTracking,
    ): View {
        $viewer = auth()->user();
        $assigneeNeedle = trim($this->assigneeSearch);
        $relatedPersonNeedle = trim($this->relatedPersonSearch);
        $payload = $this->show && $viewer
            ? $tasks->openTasksByPriorityFor($viewer)
            : ['count' => 0, 'items' => []];

        return view('livewire.dashboard.my-tasks-modal', [
            'tasks' => $payload,
            'assignedByMe' => $viewer && $this->show
                ? $assignedTasksTracking->summaryFor($viewer, 15)
                : ['items' => [], 'stats' => ['total' => 0, 'active' => 0, 'completed' => 0, 'overdue' => 0]],
            'assigneeOptions' => $viewer && strlen($assigneeNeedle) >= self::SEARCH_MIN_LENGTH
                ? $assignments->searchAssignableUsersFor($viewer, $assigneeNeedle)
                : collect(),
            'relatedPersonOptions' => $viewer && strlen($relatedPersonNeedle) >= self::SEARCH_MIN_LENGTH
                ? $assignments->searchRelatedPeopleFor($viewer, $relatedPersonNeedle)
                : collect(),
            'libraryTasks' => $assignments->libraryTasksForCategory($this->taskCategoryId),
            'categories' => $taskCategories->activeCategories(),
            'relatedModules' => self::RELATED_MODULES,
            'priorities' => [
                'low' => 'Low',
                'medium' => 'Medium',
                'high' => 'High',
                'urgent' => 'Urgent',
            ],
        ]);
    }

    private function resetCreateForm(): void
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
            if (in_array($task->related_module, self::RELATED_MODULES, true)) {
                $this->relatedModuleSelection = $task->related_module;
                $this->relatedModuleOther = '';
            } else {
                $this->relatedModuleSelection = self::MODULE_OTHER;
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
        if ($this->taskCategorySelection === self::CREATE_ONE) {
            return $taskCategories->createCategory(trim($this->newCategoryName))->id;
        }

        return (int) $this->taskCategoryId;
    }

    private function resolveTaskId(int $categoryId): int
    {
        if ($this->taskTemplateSelection === self::CREATE_ONE) {
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
        if ($this->relatedModuleSelection === self::MODULE_OTHER) {
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
