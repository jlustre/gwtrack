<?php

namespace App\Services;

use App\Models\TaskUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AssignedTasksTrackingService
{
    /**
     * @return array{items: list<array<string, mixed>>, stats: array<string, int>}
     */
    public function summaryFor(User $assignor, ?int $limit = null, bool $includeArchived = false): array
    {
        $query = $this->assignedByQuery($assignor, $includeArchived);

        if ($limit !== null) {
            $query->limit($limit);
        }

        $tasks = $query->get();
        $items = $tasks->map(fn (TaskUser $task): array => $this->formatRow($task))->values()->all();

        return [
            'items' => $items,
            'stats' => $this->statsFor($assignor, $includeArchived),
        ];
    }

    public function archiveDelegation(User $assignor, TaskUser $task): void
    {
        $this->assertAssignableByAssignor($assignor, $task);

        $task->update([
            'delegation_tracker_archived_at' => now(),
        ]);
    }

    public function deleteDelegation(User $assignor, TaskUser $task): void
    {
        $this->assertAssignableByAssignor($assignor, $task);

        if (! in_array($task->status, ['completed', 'cancelled'], true)) {
            $task->update([
                'status' => 'cancelled',
            ]);
        }

        $task->delete();
    }

    public function restoreDelegation(User $assignor, TaskUser $task): void
    {
        $this->assertAssignableByAssignor($assignor, $task);

        if ($task->trashed()) {
            $task->restore();
        }

        $task->update([
            'delegation_tracker_archived_at' => null,
        ]);
    }

    /**
     * @return array<string, int>
     */
    public function statsFor(User $assignor, bool $includeArchived = false): array
    {
        $tasks = $this->assignedByQuery($assignor, $includeArchived)->get(['id', 'status', 'due_date']);

        $open = 0;
        $inProgress = 0;
        $waiting = 0;
        $overdue = 0;
        $completed = 0;
        $cancelled = 0;

        foreach ($tasks as $task) {
            $resolved = $this->resolvedStatusKey($task);

            match ($resolved) {
                'completed' => $completed++,
                'cancelled' => $cancelled++,
                'overdue' => $overdue++,
                'in_progress' => $inProgress++,
                'waiting' => $waiting++,
                default => $open++,
            };
        }

        return [
            'total' => $tasks->count(),
            'open' => $open,
            'in_progress' => $inProgress,
            'waiting' => $waiting,
            'overdue' => $overdue,
            'completed' => $completed,
            'cancelled' => $cancelled,
            'active' => $open + $inProgress + $waiting + $overdue,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatRow(TaskUser $task): array
    {
        $statusKey = $this->resolvedStatusKey($task);
        $statusLabel = $this->resolvedStatusLabel($task);
        $assigneeName = $task->assignee?->name ?? 'Unassigned';

        return [
            'id' => $task->id,
            'title' => $task->displayTitle(),
            'assignee' => $assigneeName,
            'assignee_initials' => $this->initialsFor($assigneeName),
            'category' => $task->categoryName() ?? 'Task',
            'priority' => $task->displayPriority(),
            'status' => $statusLabel,
            'status_key' => $statusKey,
            'status_badge_class' => $this->statusBadgeClass($statusKey),
            'due_label' => $task->due_date?->format('M j, Y') ?? '—',
            'due_iso' => $task->due_date?->toDateString(),
            'assigned_at' => $task->created_at?->format('M j, Y') ?? '—',
            'completed_at' => $task->completed_at?->format('M j, Y g:i A'),
            'related_person' => $task->related_person,
            'related_module' => $task->related_module,
            'progress' => (int) ($task->progress ?? 0),
            'notes' => $task->displayNotes(),
            'is_closed' => in_array($statusKey, ['completed', 'cancelled'], true),
        ];
    }

    public function resolvedStatusLabel(TaskUser $task): string
    {
        return match ($this->resolvedStatusKey($task)) {
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'overdue' => 'Overdue',
            'in_progress' => 'In Progress',
            'waiting' => 'Waiting',
            'to_do' => 'To Do',
            default => $task->displayStatus(),
        };
    }

    public function resolvedStatusKey(TaskUser $task): string
    {
        if ($task->status === 'completed') {
            return 'completed';
        }

        if ($task->status === 'cancelled') {
            return 'cancelled';
        }

        if ($task->status === 'overdue' || ($task->due_date?->isPast() ?? false)) {
            return 'overdue';
        }

        if (in_array($task->status, ['in_progress', 'waiting', 'to_do'], true)) {
            return $task->status;
        }

        return 'to_do';
    }

    public function statusBadgeClass(string $statusKey): string
    {
        return match ($statusKey) {
            'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'cancelled' => 'bg-slate-100 text-slate-600 border-slate-200',
            'overdue' => 'bg-red-100 text-red-800 border-red-200',
            'in_progress' => 'bg-blue-100 text-blue-800 border-blue-200',
            'waiting' => 'bg-violet-100 text-violet-800 border-violet-200',
            default => 'bg-amber-100 text-amber-900 border-amber-200',
        };
    }

    /**
     * @return Builder<TaskUser>
     */
    private function assignedByQuery(User $assignor, bool $includeArchived = false): Builder
    {
        return TaskUser::query()
            ->with(['assignee:id,name,email', 'task:id,title', 'taskCategory:id,name'])
            ->where('assignor_id', $assignor->id)
            ->where('assignee_id', '!=', $assignor->id)
            ->when(! $includeArchived, fn (Builder $query): Builder => $query->whereNull('delegation_tracker_archived_at'))
            ->when($includeArchived, fn (Builder $query): Builder => $query->whereNotNull('delegation_tracker_archived_at'))
            ->orderByRaw("CASE WHEN status IN ('completed', 'cancelled') THEN 1 ELSE 0 END")
            ->orderByRaw(
                "CASE WHEN status NOT IN ('completed', 'cancelled') AND due_date IS NOT NULL AND due_date < ? THEN 0 ELSE 1 END",
                [now()->toDateString()],
            )
            ->orderBy('due_date')
            ->orderByDesc('created_at');
    }

    private function assertAssignableByAssignor(User $assignor, TaskUser $task): void
    {
        abort_unless(
            (int) $task->assignor_id === (int) $assignor->id
            && (int) $task->assignee_id !== (int) $assignor->id,
            403,
        );
    }

    private function initialsFor(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return str(collect($parts)->take(2)->map(fn (string $part): string => mb_substr($part, 0, 1))->implode(''))
            ->upper()
            ->toString() ?: '?';
    }
}
