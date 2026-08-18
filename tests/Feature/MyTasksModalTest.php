<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\MyTasksModal;
use App\Models\Prospect;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskUser;
use App\Models\User;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskCategorySeeder;
use Database\Seeders\TaskSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class MyTasksModalTest extends TestCase
{
    use RefreshDatabase;

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
            ->test(MyTasksModal::class)
            ->dispatch('open-my-tasks-modal')
            ->call('toggleCreateForm')
            ->call('selectAssignee', $user->id)
            ->assertSet('assigneeSearch', 'Jordan Assignee')
            ->assertSet('assigneeSearch', fn (string $value): bool => ! str_contains($value, '@'));
    }

    public function test_user_can_create_task_with_library_template_and_related_fields(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
            TaskCategorySeeder::class,
            TaskSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $teamId = (int) DB::table('teams')->value('id');

        $user = User::factory()->create(['team_id' => $teamId]);
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Alex',
            'last_name' => 'Rivera',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        $task = Task::query()->where('title', 'Follow up with a prospect')->firstOrFail();

        Livewire::actingAs($user)
            ->test(MyTasksModal::class)
            ->dispatch('open-my-tasks-modal')
            ->call('toggleCreateForm')
            ->call('selectAssignee', $user->id)
            ->set('taskCategorySelection', (string) $task->task_category_id)
            ->set('taskTemplateSelection', (string) $task->id)
            ->set('relatedModuleSelection', 'Prospects')
            ->set('relatedPersonSearch', 'Ale')
            ->assertSet('relatedPersonPickerOpen', true)
            ->call('selectRelatedPerson', 'Alex Rivera')
            ->call('createTask')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('task_users', [
            'assignee_id' => $user->id,
            'assignor_id' => $user->id,
            'task_id' => $task->id,
            'related_person' => 'Alex Rivera',
            'related_module' => 'Prospects',
        ]);

        $this->assertSame(1, TaskUser::query()->count());
    }

    public function test_user_can_create_task_with_new_category_and_template(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
            TaskCategorySeeder::class,
            TaskSeeder::class,
        ]);

        $teamId = (int) DB::table('teams')->value('id');

        $user = User::factory()->create(['team_id' => $teamId]);
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(MyTasksModal::class)
            ->dispatch('open-my-tasks-modal')
            ->call('toggleCreateForm')
            ->call('selectAssignee', $user->id)
            ->set('taskCategorySelection', MyTasksModal::CREATE_ONE)
            ->set('newCategoryName', 'Custom Outreach')
            ->set('taskTemplateSelection', MyTasksModal::CREATE_ONE)
            ->set('newTaskTemplateTitle', 'Send introduction packet')
            ->set('relatedModuleSelection', MyTasksModal::MODULE_OTHER)
            ->set('relatedModuleOther', 'Events')
            ->call('createTask')
            ->assertHasNoErrors();

        $category = TaskCategory::query()->where('name', 'Custom Outreach')->first();

        $this->assertNotNull($category);

        $this->assertDatabaseHas('task_users', [
            'assignee_id' => $user->id,
            'task_category_id' => $category->id,
            'related_module' => 'Events',
        ]);

        $this->assertDatabaseHas('tasks', [
            'task_category_id' => $category->id,
            'title' => 'Send introduction packet',
            'is_active' => false,
        ]);
    }

    public function test_open_event_can_show_create_form_directly(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(MyTasksModal::class)
            ->dispatch('open-my-tasks-modal', createForm: true)
            ->assertSet('show', true)
            ->assertSet('showCreateForm', true)
            ->assertSee('Create task', false);
    }

    public function test_open_event_without_create_form_shows_task_list(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(MyTasksModal::class)
            ->dispatch('open-my-tasks-modal')
            ->assertSet('show', true)
            ->assertSet('showCreateForm', false)
            ->assertSee('Assigned to me', false);
    }

    public function test_create_form_shows_task_template_select_options(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
            TaskCategorySeeder::class,
            TaskSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $task = Task::query()->where('title', 'Follow up with a prospect')->firstOrFail();

        Livewire::actingAs($user)
            ->test(MyTasksModal::class)
            ->dispatch('open-my-tasks-modal')
            ->call('toggleCreateForm')
            ->set('taskCategorySelection', (string) $task->task_category_id)
            ->assertSee('Follow up with a prospect')
            ->assertSee('Create one');
    }
}
