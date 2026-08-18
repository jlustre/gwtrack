<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskUser;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskCategorySeeder;
use Database\Seeders\TaskSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskManagementAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sidebar_includes_tasks_and_task_assignments_links(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tasks', false)
            ->assertSee('Task Assignments', false)
            ->assertSee(route('admin.management.resource.index', 'tasks'), false)
            ->assertSee(route('admin.management.resource.index', 'task-users'), false);
    }

    public function test_admin_can_create_update_and_archive_task_template(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TaskCategorySeeder::class,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $categoryId = TaskCategory::query()->where('slug', 'training')->value('id');

        $this->actingAs($admin)
            ->post(route('admin.management.store', 'tasks'), [
                'task_category_id' => $categoryId,
                'title' => 'Admin Created Task',
                'description' => 'Created from admin management.',
                'slug' => '',
                'default_priority' => 'high',
                'related_module' => 'Training',
                'sort_order' => 500,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $taskId = DB::table('tasks')->where('title', 'Admin Created Task')->value('id');
        $this->assertNotNull($taskId);
        $this->assertSame('admin-created-task', DB::table('tasks')->where('id', $taskId)->value('slug'));

        $this->actingAs($admin)
            ->patch(route('admin.management.update', ['tasks', $taskId]), [
                'task_category_id' => $categoryId,
                'title' => 'Admin Updated Task',
                'description' => 'Updated from admin management.',
                'slug' => 'admin-created-task',
                'default_priority' => 'medium',
                'related_module' => 'Training',
                'sort_order' => 510,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(
            'Admin Updated Task',
            DB::table('tasks')->where('id', $taskId)->value('title'),
        );

        $this->actingAs($admin)
            ->delete(route('admin.management.destroy', ['tasks', $taskId]))
            ->assertRedirect();

        $this->assertNotNull(DB::table('tasks')->where('id', $taskId)->value('deleted_at'));
    }

    public function test_admin_tasks_index_shows_update_seeder_control(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TaskCategorySeeder::class,
            TaskSeeder::class,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', 'tasks'))
            ->assertOk()
            ->assertSee('Update Seeder', false)
            ->assertSee('Follow up with a prospect', false);
    }

    public function test_admin_can_create_update_and_archive_task_assignment(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TaskCategorySeeder::class,
            TaskSeeder::class,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $assignee = User::factory()->create();
        $assignee->assignRole('member');

        $task = Task::query()->where('title', 'Follow up with a prospect')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.management.store', 'task-users'), [
                'assignee_id' => $assignee->id,
                'assignor_id' => $admin->id,
                'task_id' => $task->id,
                'task_category_id' => $task->task_category_id,
                'priority' => 'high',
                'status' => 'to_do',
                'due_date' => now()->addDays(2)->toDateString(),
                'progress' => 0,
                'related_person' => 'Taylor Brooks',
                'related_module' => 'Prospects',
                'additional_notes' => 'Admin-assigned follow-up.',
                'reminder' => '',
                'completed_at' => '',
            ])
            ->assertRedirect();

        $assignmentId = TaskUser::query()->where('additional_notes', 'Admin-assigned follow-up.')->value('id');
        $this->assertNotNull($assignmentId);

        $this->actingAs($admin)
            ->patch(route('admin.management.update', ['task-users', $assignmentId]), [
                'assignee_id' => $assignee->id,
                'assignor_id' => $admin->id,
                'task_id' => $task->id,
                'task_category_id' => $task->task_category_id,
                'priority' => 'urgent',
                'status' => 'in_progress',
                'due_date' => now()->addDay()->toDateString(),
                'progress' => 25,
                'related_person' => 'Taylor Brooks',
                'related_module' => 'Prospects',
                'additional_notes' => 'Admin-updated follow-up.',
                'reminder' => '',
                'completed_at' => '',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_users', [
            'id' => $assignmentId,
            'priority' => 'urgent',
            'status' => 'in_progress',
            'additional_notes' => 'Admin-updated follow-up.',
            'progress' => 25,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.management.destroy', ['task-users', $assignmentId]))
            ->assertRedirect();

        $this->assertNotNull(DB::table('task_users')->where('id', $assignmentId)->value('deleted_at'));
    }

    public function test_task_assignment_category_syncs_from_selected_task_on_save(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TaskCategorySeeder::class,
            TaskSeeder::class,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $assignee = User::factory()->create();
        $assignee->assignRole('member');

        $task = Task::query()->where('title', 'Follow up with a prospect')->firstOrFail();
        $wrongCategoryId = TaskCategory::query()->where('slug', 'training')->value('id');

        $this->actingAs($admin)
            ->post(route('admin.management.store', 'task-users'), [
                'assignee_id' => $assignee->id,
                'assignor_id' => $admin->id,
                'task_id' => $task->id,
                'task_category_id' => $wrongCategoryId,
                'priority' => 'medium',
                'status' => 'to_do',
                'due_date' => '',
                'progress' => '',
                'related_person' => '',
                'related_module' => '',
                'additional_notes' => 'Category sync test.',
                'reminder' => '',
                'completed_at' => '',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('task_users', [
            'additional_notes' => 'Category sync test.',
            'task_id' => $task->id,
            'task_category_id' => $task->task_category_id,
        ]);
    }
}
