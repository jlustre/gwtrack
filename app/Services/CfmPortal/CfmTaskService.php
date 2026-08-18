<?php

namespace App\Services\CfmPortal;

use App\Models\CfmTask;
use App\Models\CfmTaskLog;
use App\Models\User;
use App\Services\Notifications\NotificationOrchestrator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CfmTaskService
{
    public function __construct(
        private readonly CfmTraineeCenterService $centers,
        private readonly NotificationOrchestrator $notifications,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function centerFor(User $cfm, int $traineeId, string $statusFilter = 'all'): ?array
    {
        $trainee = $this->centers->resolveTrainee($cfm, $traineeId);

        if (! $trainee) {
            return null;
        }

        $tasks = CfmTask::query()
            ->where('cfm_id', $cfm->id)
            ->where('trainee_id', $trainee->id)
            ->with(['assignedBy', 'logs' => fn ($query) => $query->latest()->limit(3)])
            ->latest()
            ->get();

        $filtered = $this->filterTasks($tasks, $statusFilter);

        return [
            'key' => 'tasks',
            'title' => 'Task Management',
            'description' => 'Assign coaching tasks, track follow-ups, and monitor completion for this trainee.',
            'stats' => [
                'total' => $tasks->count(),
                'open' => $tasks->whereIn('status', ['open', 'in_progress'])->count(),
                'completed' => $tasks->where('status', 'completed')->count(),
                'overdue' => $tasks->filter(fn (CfmTask $task) => $task->isOverdue())->count(),
            ],
            'tasks' => $filtered->map(fn (CfmTask $task) => $this->taskRow($task))->values()->all(),
            'categories' => CfmTask::CATEGORIES,
            'priorities' => CfmTask::PRIORITIES,
            'statuses' => CfmTask::STATUSES,
            'status_filter' => $statusFilter,
            'member_profile_url' => route('team.member.profile', $trainee),
        ];
    }

    public function create(User $cfm, User $trainee, User $actor, array $data): CfmTask
    {
        $this->assertTraineeAccess($cfm, $trainee);

        $task = CfmTask::query()->create([
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'title' => $data['title'],
            'notes' => $data['notes'] ?? null,
            'category' => $data['category'] ?? 'coaching',
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'open',
            'due_date' => $data['due_date'] ?? null,
            'assigned_by' => $actor->id,
        ]);

        $this->log($task, $actor, 'created', 'Task assigned to trainee.');

        $this->notifications->dispatch('task_assigned', [
            'queue' => true,
            'sender' => $actor,
            'recipients' => [$trainee->id],
            'module' => 'task',
            'priority' => ($data['priority'] ?? 'normal') === 'high' ? 'high' : 'medium',
            'related' => ['type' => CfmTask::class, 'id' => $task->id],
            'related_user_id' => $trainee->id,
            'template_data' => [
                'task_name' => $task->title,
                'cfm_name' => $cfm->name,
                'deadline' => $task->due_date?->format('M j, Y') ?? 'No due date',
            ],
            'action_link' => [
                'route' => 'cfm.portal',
                'params' => ['trainee' => $trainee->id],
                'label' => 'View task',
            ],
        ]);

        return $task;
    }

    public function updateStatus(User $cfm, CfmTask $task, User $actor, string $status): CfmTask
    {
        $this->assertTaskAccess($cfm, $task);

        if (! in_array($status, CfmTask::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'Invalid task status.']);
        }

        $task->update([
            'status' => $status,
            'completed_at' => $status === 'completed' ? now() : null,
        ]);

        $this->log($task, $actor, 'status_changed', 'Status set to '.$status.'.');

        if ($status === 'completed') {
            $task->loadMissing(['trainee', 'cfm']);

            $this->notifications->dispatch('task_completed', [
                'queue' => true,
                'sender' => $actor,
                'recipients' => [$task->cfm_id],
                'module' => 'task',
                'priority' => 'info',
                'related' => ['type' => CfmTask::class, 'id' => $task->id],
                'related_user_id' => $task->trainee_id,
                'template_data' => [
                    'task_name' => $task->title,
                    'trainee_name' => $task->trainee?->name ?? 'Trainee',
                ],
                'action_link' => [
                    'route' => 'cfm.portal',
                    'params' => ['trainee' => $task->trainee_id],
                    'label' => 'View task',
                ],
            ]);
        }

        return $task->refresh();
    }

    public function delete(User $cfm, CfmTask $task, User $actor): void
    {
        $this->assertTaskAccess($cfm, $task);
        $task->delete();
    }

    public function findForCfm(User $cfm, int $taskId): CfmTask
    {
        return CfmTask::query()
            ->where('cfm_id', $cfm->id)
            ->whereKey($taskId)
            ->firstOrFail();
    }

    /**
     * @param  Collection<int, CfmTask>  $tasks
     * @return Collection<int, CfmTask>
     */
    private function filterTasks(Collection $tasks, string $filter): Collection
    {
        return match ($filter) {
            'open' => $tasks->whereIn('status', ['open', 'in_progress'])->values(),
            'completed' => $tasks->where('status', 'completed')->values(),
            'overdue' => $tasks->filter(fn (CfmTask $task) => $task->isOverdue())->values(),
            default => $tasks->values(),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function taskRow(CfmTask $task): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'notes' => $task->notes,
            'category' => $task->category,
            'priority' => $task->priority,
            'status' => $task->status,
            'due_date' => $task->due_date?->format('M j, Y') ?? '—',
            'is_overdue' => $task->isOverdue(),
            'completed_at' => $task->completed_at?->format('M j, Y g:i A'),
            'assigned_by' => $task->assignedBy?->name ?? '—',
            'created_at' => $task->created_at?->format('M j, Y'),
            'recent_logs' => $task->logs->map(fn (CfmTaskLog $log) => [
                'action' => $log->action,
                'details' => $log->details,
                'at' => $log->created_at?->format('M j, Y g:i A'),
            ])->values()->all(),
        ];
    }

    private function assertTraineeAccess(User $cfm, User $trainee): void
    {
        if (! $this->centers->resolveTrainee($cfm, $trainee->id)) {
            abort(403);
        }
    }

    private function assertTaskAccess(User $cfm, CfmTask $task): void
    {
        if ((int) $task->cfm_id !== (int) $cfm->id) {
            abort(403);
        }
    }

    private function log(CfmTask $task, User $actor, string $action, ?string $details = null): void
    {
        CfmTaskLog::query()->create([
            'cfm_task_id' => $task->id,
            'action' => $action,
            'details' => $details,
            'user_id' => $actor->id,
        ]);
    }
}
