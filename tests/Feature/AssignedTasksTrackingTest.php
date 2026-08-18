<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\MyTasksModal;
use App\Models\Task;
use App\Models\TaskUser;
use App\Models\User;
use App\Services\AssignedTasksTrackingService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskCategorySeeder;
use Database\Seeders\TaskSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AssignedTasksTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_tracks_delegated_tasks_with_resolved_statuses(): void
    {
        [$assignor, $assignee] = $this->seedAssignorAndAssignee();

        $task = Task::query()->where('title', 'Follow up with a prospect')->firstOrFail();

        TaskUser::create([
            'assignor_id' => $assignor->id,
            'assignee_id' => $assignee->id,
            'task_id' => $task->id,
            'task_category_id' => $task->task_category_id,
            'status' => 'in_progress',
            'priority' => 'high',
            'due_date' => now()->addDay(),
            'progress' => 40,
        ]);

        TaskUser::create([
            'assignor_id' => $assignor->id,
            'assignee_id' => $assignee->id,
            'task_id' => $task->id,
            'task_category_id' => $task->task_category_id,
            'status' => 'completed',
            'priority' => 'medium',
            'completed_at' => now()->subHour(),
        ]);

        TaskUser::create([
            'assignor_id' => $assignor->id,
            'assignee_id' => $assignor->id,
            'task_id' => $task->id,
            'task_category_id' => $task->task_category_id,
            'status' => 'to_do',
            'priority' => 'low',
        ]);

        $service = app(AssignedTasksTrackingService::class);
        $summary = $service->summaryFor($assignor);

        $this->assertSame(2, $summary['stats']['total']);
        $this->assertSame(1, $summary['stats']['active']);
        $this->assertSame(1, $summary['stats']['completed']);
        $this->assertSame(1, $summary['stats']['in_progress']);

        $statusKeys = collect($summary['items'])->pluck('status_key')->all();
        $this->assertContains('in_progress', $statusKeys);
        $this->assertContains('completed', $statusKeys);
        $this->assertNotContains('to_do', $statusKeys);
    }

    public function test_tasks_page_shows_delegation_tracker_for_assigned_tasks(): void
    {
        [$assignor, $assignee] = $this->seedAssignorAndAssignee();
        $this->createDelegatedTask($assignor, $assignee, 'in_progress');

        $this->actingAs($assignor)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Tasks I assigned to others', false)
            ->assertSee('Delegation Tracker', false)
            ->assertSee($assignee->name)
            ->assertSee('In Progress', false);
    }

    public function test_calendar_page_shows_delegation_tracker_panel(): void
    {
        [$assignor, $assignee] = $this->seedAssignorAndAssignee();
        $this->createDelegatedTask($assignor, $assignee, 'waiting');

        $this->actingAs($assignor)
            ->get(route('events.index'))
            ->assertOk()
            ->assertSee('calendar-assigned-by-me-panel', false)
            ->assertSee('Tasks I assigned to others', false)
            ->assertSee($assignee->name)
            ->assertSee('Waiting', false);
    }

    public function test_my_tasks_modal_shows_delegated_tasks_table(): void
    {
        [$assignor, $assignee] = $this->seedAssignorAndAssignee();
        $this->createDelegatedTask($assignor, $assignee, 'to_do');

        Livewire::actingAs($assignor)
            ->test(MyTasksModal::class)
            ->dispatch('open-my-tasks-modal')
            ->assertSee('my-tasks-assigned-by-me-panel', false)
            ->assertSee('Tasks I assigned to others', false)
            ->assertSee($assignee->name)
            ->assertSee('To Do', false);
    }

    public function test_assignee_sees_task_assigned_by_someone_else_in_my_tasks_modal(): void
    {
        [$assignor, $assignee] = $this->seedAssignorAndAssignee();
        $task = Task::query()->where('title', 'Follow up with a prospect')->firstOrFail();

        TaskUser::create([
            'assignor_id' => $assignor->id,
            'assignee_id' => $assignee->id,
            'task_id' => $task->id,
            'task_category_id' => $task->task_category_id,
            'status' => 'to_do',
            'priority' => 'high',
            'due_date' => now()->addWeek(),
        ]);

        Livewire::actingAs($assignee)
            ->test(\App\Livewire\Dashboard\MyTasksModal::class)
            ->dispatch('open-my-tasks-modal')
            ->assertSee('Assigned to me', false)
            ->assertSee('Follow up with a prospect')
            ->assertSee('Assigned by '.$assignor->name, false)
            ->assertDontSee('Tasks I assigned to others', false);
    }

    public function test_overdue_status_is_resolved_from_past_due_date(): void
    {
        [$assignor, $assignee] = $this->seedAssignorAndAssignee();
        $this->createDelegatedTask($assignor, $assignee, 'to_do', now()->subDay());

        $service = app(AssignedTasksTrackingService::class);
        $row = $service->summaryFor($assignor)['items'][0];

        $this->assertSame('overdue', $row['status_key']);
        $this->assertSame('Overdue', $row['status']);
    }

    public function test_archive_hides_delegation_from_tracker_without_cancelling_task(): void
    {
        [$assignor, $assignee] = $this->seedAssignorAndAssignee();
        $task = $this->createDelegatedTask($assignor, $assignee, 'in_progress');

        Livewire::actingAs($assignor)
            ->test(\App\Livewire\Tasks\AssignedByMePanel::class)
            ->assertSee('Archive', false)
            ->call('archiveTask', $task->id)
            ->assertDontSee($assignee->name);

        $task->refresh();
        $this->assertSame('in_progress', $task->status);
        $this->assertNotNull($task->delegation_tracker_archived_at);

        $this->assertSame(0, app(AssignedTasksTrackingService::class)->summaryFor($assignor)['stats']['total']);
    }

    public function test_delete_cancels_open_delegation_and_removes_from_assignee_list(): void
    {
        [$assignor, $assignee] = $this->seedAssignorAndAssignee();
        $task = $this->createDelegatedTask($assignor, $assignee, 'to_do');

        Livewire::actingAs($assignor)
            ->test(\App\Livewire\Tasks\AssignedByMePanel::class)
            ->call('deleteTask', $task->id);

        $task = TaskUser::withTrashed()->findOrFail($task->id);
        $this->assertSame('cancelled', $task->status);
        $this->assertNotNull($task->deleted_at);
        $this->assertSame(0, TaskUser::query()->where('assignee_id', $assignee->id)->count());
    }

    public function test_archived_delegations_can_be_restored(): void
    {
        [$assignor, $assignee] = $this->seedAssignorAndAssignee();
        $task = $this->createDelegatedTask($assignor, $assignee, 'completed');

        app(AssignedTasksTrackingService::class)->archiveDelegation($assignor, $task);

        Livewire::actingAs($assignor)
            ->test(\App\Livewire\Tasks\AssignedByMePanel::class)
            ->call('toggleArchived')
            ->assertSee('Restore', false)
            ->call('restoreTask', $task->id)
            ->call('toggleArchived')
            ->assertSee($assignee->name);
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function seedAssignorAndAssignee(): array
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
            TaskCategorySeeder::class,
            TaskSeeder::class,
        ]);

        $teamId = (int) DB::table('teams')->value('id');

        $assignor = User::factory()->create(['team_id' => $teamId, 'name' => 'Jordan Assignor']);
        $assignor->assignRole('member');

        $assignee = User::factory()->create(['team_id' => $teamId, 'name' => 'Alex Assignee']);
        $assignee->assignRole('member');

        return [$assignor, $assignee];
    }

    private function createDelegatedTask(
        User $assignor,
        User $assignee,
        string $status,
        ?\Illuminate\Support\Carbon $dueDate = null,
    ): TaskUser {
        $task = Task::query()->where('title', 'Follow up with a prospect')->firstOrFail();

        return TaskUser::create([
            'assignor_id' => $assignor->id,
            'assignee_id' => $assignee->id,
            'task_id' => $task->id,
            'task_category_id' => $task->task_category_id,
            'status' => $status,
            'priority' => 'medium',
            'due_date' => $dueDate ?? now()->addWeek(),
        ]);
    }
}
