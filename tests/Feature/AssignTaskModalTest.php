<?php

namespace Tests\Feature;

use App\Livewire\Tasks\AssignTaskModal;
use App\Models\Task;
use App\Models\TaskUser;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskCategorySeeder;
use Database\Seeders\TaskSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AssignTaskModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_topbar_includes_assign_task_trigger(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('open-assign-task-modal', false)
            ->assertSee('Assign a task', false);
    }

    public function test_user_can_assign_task_through_modal_form(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
            TaskCategorySeeder::class,
            TaskSeeder::class,
        ]);

        $teamId = (int) DB::table('teams')->value('id');

        $assignor = User::factory()->create(['team_id' => $teamId]);
        $assignor->assignRole('member');

        $assignee = User::factory()->create(['team_id' => $teamId]);
        $assignee->assignRole('member');

        $task = Task::query()->where('title', 'Follow up with a prospect')->firstOrFail();

        Livewire::actingAs($assignor)
            ->test(AssignTaskModal::class)
            ->dispatch('open-assign-task-modal')
            ->assertSet('show', true)
            ->call('selectAssignee', $assignee->id)
            ->set('taskCategorySelection', (string) $task->task_category_id)
            ->set('taskTemplateSelection', (string) $task->id)
            ->set('priority', 'high')
            ->set('status', 'to_do')
            ->set('dueDate', now()->addDay()->toDateString())
            ->set('additionalNotes', 'Please call before noon.')
            ->call('selectRelatedPerson', 'Alex Rivera')
            ->call('assign')
            ->assertSet('show', false);

        $this->assertDatabaseHas('task_users', [
            'assignee_id' => $assignee->id,
            'assignor_id' => $assignor->id,
            'task_id' => $task->id,
            'priority' => 'high',
            'status' => 'to_do',
            'additional_notes' => 'Please call before noon.',
            'related_person' => 'Alex Rivera',
        ]);

        $this->assertSame(1, TaskUser::query()->count());
    }

    public function test_assignee_search_requires_three_characters_before_showing_options(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
        ]);

        $teamId = (int) DB::table('teams')->value('id');

        $assignor = User::factory()->create([
            'team_id' => $teamId,
            'name' => 'Jordan Searchable',
        ]);
        $assignor->assignRole('member');

        User::factory()->create([
            'team_id' => $teamId,
            'name' => 'Alex Searchable',
            'email' => 'alex.searchable@example.com',
        ]);

        Livewire::actingAs($assignor)
            ->test(AssignTaskModal::class)
            ->dispatch('open-assign-task-modal')
            ->set('assigneeSearch', 'Jo')
            ->assertSet('assigneePickerOpen', false)
            ->set('assigneeSearch', 'Sea')
            ->assertSet('assigneePickerOpen', true)
            ->assertSee('Jordan Searchable')
            ->assertSee('Alex Searchable');
    }

    public function test_task_template_select_lists_category_templates(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TaskCategorySeeder::class,
            TaskSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $task = Task::query()->where('title', 'Follow up with a prospect')->firstOrFail();

        Livewire::actingAs($user)
            ->test(AssignTaskModal::class)
            ->dispatch('open-assign-task-modal')
            ->set('taskCategorySelection', (string) $task->task_category_id)
            ->assertSee('Follow up with a prospect')
            ->assertSee('Create one');
    }

    public function test_assignee_selection_displays_name_only(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
        ]);

        $teamId = (int) DB::table('teams')->value('id');

        $user = User::factory()->create([
            'team_id' => $teamId,
            'name' => 'Jordan Assignee',
            'email' => 'jordan.assignee@example.com',
        ]);
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(AssignTaskModal::class)
            ->dispatch('open-assign-task-modal')
            ->call('selectAssignee', $user->id)
            ->assertSet('assigneeSearch', 'Jordan Assignee')
            ->assertSet('assigneeSearch', fn (string $value): bool => ! str_contains($value, '@'));
    }
}
