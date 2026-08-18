<?php

namespace App\Livewire\Tasks;

use App\Models\TaskUser;
use App\Services\AssignedTasksTrackingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class AssignedByMePanel extends Component
{
    public bool $compact = false;

    public ?int $limit = null;

    public string $panelId = 'assigned-by-me-panel';

    public bool $showArchived = false;

    #[On('delegation-tracker-changed')]
    public function refreshPanel(): void
    {
        // Re-render delegation tracker rows.
    }

    public function toggleArchived(): void
    {
        $this->showArchived = ! $this->showArchived;
    }

    public function archiveTask(int $taskUserId, AssignedTasksTrackingService $tracking): void
    {
        $task = $this->delegatedTaskFor($taskUserId);

        $tracking->archiveDelegation(auth()->user(), $task);

        $this->dispatch('delegation-tracker-changed');
        session()->flash('delegation_tracker_status', 'Task archived in your delegation tracker.');
    }

    public function deleteTask(int $taskUserId, AssignedTasksTrackingService $tracking): void
    {
        $task = $this->delegatedTaskFor($taskUserId);

        $tracking->deleteDelegation(auth()->user(), $task);

        $this->dispatch('delegation-tracker-changed');
        session()->flash('delegation_tracker_status', 'Delegated task removed.');
    }

    public function restoreTask(int $taskUserId, AssignedTasksTrackingService $tracking): void
    {
        $task = TaskUser::withTrashed()
            ->whereKey($taskUserId)
            ->where('assignor_id', auth()->id())
            ->where('assignee_id', '!=', auth()->id())
            ->firstOrFail();

        $tracking->restoreDelegation(auth()->user(), $task);

        $this->dispatch('delegation-tracker-changed');
        session()->flash('delegation_tracker_status', 'Task restored to your delegation tracker.');
    }

    public function render(AssignedTasksTrackingService $tracking): View
    {
        $user = auth()->user();
        $assignedByMe = $user
            ? $tracking->summaryFor($user, $this->limit, $this->showArchived)
            : ['items' => [], 'stats' => ['total' => 0, 'active' => 0, 'completed' => 0, 'overdue' => 0]];

        return view('livewire.tasks.assigned-by-me-panel', [
            'assignedByMe' => $assignedByMe,
        ]);
    }

    private function delegatedTaskFor(int $taskUserId): TaskUser
    {
        return TaskUser::query()
            ->whereKey($taskUserId)
            ->where('assignor_id', auth()->id())
            ->where('assignee_id', '!=', auth()->id())
            ->firstOrFail();
    }
}
