<?php

namespace App\Livewire\Tasks;

use App\Http\Controllers\TaskController;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class TasksPage extends Component
{
    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $priority = '';

    #[Url(except: '')]
    public string $assignee = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $module = '';

    #[Url(as: 'due_date', except: '')]
    public string $dueDate = '';

    #[Url(except: 1)]
    public int $page = 1;

    #[Url(as: 'view', except: 'list')]
    public string $viewMode = 'list';

    public function mount(
        string $q = '',
        string $status = '',
        string $priority = '',
        string $assignee = '',
        string $category = '',
        string $module = '',
        string $dueDate = '',
        int $page = 1,
        string $view = 'list',
    ): void {
        abort_unless(auth()->check(), 403);

        $this->q = $q;
        $this->status = $status;
        $this->priority = $priority;
        $this->assignee = $assignee;
        $this->category = $category;
        $this->module = $module;
        $this->dueDate = $dueDate;
        $this->page = max(1, $page);
        $this->viewMode = $this->normalizeView($view);
    }

    #[On('task-assigned')]
    #[On('invitation-email-sent')]
    #[On('invitation-deleted')]
    #[On('delegation-tracker-changed')]
    #[On('tasks-workspace-refresh')]
    public function refreshWorkspace(TaskController $tasks): void
    {
        $workspace = $tasks->workspaceFor(auth()->user(), $this->filterPayload());

        $this->dispatch('tasks-payload-updated', payload: $workspace['taskManagementPayload']);
    }

    public function syncFiltersFromClient(
        string $q = '',
        string $status = '',
        string $priority = '',
        string $assignee = '',
        string $category = '',
        string $module = '',
        string $dueDate = '',
        int $page = 1,
        string $view = 'list',
    ): void {
        $this->q = $q;
        $this->status = $status;
        $this->priority = $priority;
        $this->assignee = $assignee;
        $this->category = $category;
        $this->module = $module;
        $this->dueDate = $dueDate;
        $this->page = max(1, $page);
        $this->viewMode = $this->normalizeView($view);

        $this->skipRender();
    }

    public function render(TaskController $tasks): View
    {
        $workspace = $tasks->workspaceFor(auth()->user(), $this->filterPayload());

        return view('livewire.tasks.tasks-page', $workspace);
    }

    /**
     * @return array<string, mixed>
     */
    private function filterPayload(): array
    {
        return [
            'q' => $this->q,
            'status' => $this->status,
            'priority' => $this->priority,
            'assignee' => $this->assignee,
            'category' => $this->category,
            'module' => $this->module,
            'due_date' => $this->dueDate,
            'page' => $this->page,
            'view' => $this->viewMode,
        ];
    }

    private function normalizeView(string $view): string
    {
        $allowed = ['list', 'board', 'calendar', 'my', 'team', 'completed'];

        return in_array($view, $allowed, true) ? $view : 'list';
    }
}
