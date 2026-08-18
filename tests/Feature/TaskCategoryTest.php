<?php

namespace Tests\Feature;

use App\Models\TaskCategory;
use App\Models\User;
use App\Services\TaskCategoryService;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskCategorySeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TaskCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_task_category_seeder_from_management_ui(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TaskCategorySeeder::class,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        TaskCategory::query()->where('slug', 'training')->update([
            'action_label' => 'Launch Training Hub',
        ]);

        $seederPath = database_path('seeders/TaskCategorySeeder.php');
        $originalSeeder = file_get_contents($seederPath);

        try {
            $this->actingAs($admin)
                ->post(route('admin.management.update-seeder', 'task-categories'))
                ->assertRedirect(route('admin.management.resource.index', 'task-categories'))
                ->assertSessionHas('status', 'seeder-updated');

            $this->assertStringContainsString('Launch Training Hub', file_get_contents($seederPath));
            $this->assertStringContainsString('TaskCategory::query()->updateOrCreate', file_get_contents($seederPath));
        } finally {
            file_put_contents($seederPath, $originalSeeder);
        }
    }

    public function test_admin_can_reorder_task_categories(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TaskCategorySeeder::class,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $first = TaskCategory::query()->orderBy('sort_order')->orderBy('id')->firstOrFail();
        $second = TaskCategory::query()->orderBy('sort_order')->orderBy('id')->skip(1)->firstOrFail();

        $firstOrder = $first->sort_order;
        $secondOrder = $second->sort_order;

        $this->actingAs($admin)
            ->patch(route('admin.management.reorder', ['task-categories', $second->id, 'move' => 'up']))
            ->assertRedirect(route('admin.management.resource.index', 'task-categories'))
            ->assertSessionHas('status', 'record-order-updated');

        $first->refresh();
        $second->refresh();

        $this->assertSame($firstOrder, $second->sort_order);
        $this->assertSame($secondOrder, $first->sort_order);
    }

    public function test_task_category_management_page_shows_update_seeder_and_sort_controls(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TaskCategorySeeder::class,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.management.resource.index', 'task-categories'))
            ->assertOk()
            ->assertSee('Update Seeder', false)
            ->assertSee('Sort by Sort Order', false)
            ->assertSee('Move up', false)
            ->assertSee('Move down', false);
    }

    public function test_admin_sidebar_includes_task_categories_link(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Task Categories', false)
            ->assertSee(route('admin.management.resource.index', 'task-categories'), false);
    }

    public function test_task_category_seeder_creates_categories_with_action_routes(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TaskCategorySeeder::class,
        ]);

        $prospectCategory = TaskCategory::query()->where('slug', 'prospect-follow-up')->first();

        $this->assertNotNull($prospectCategory);
        $this->assertSame('Prospect Follow-Up', $prospectCategory->name);
        $this->assertSame('team.prospects', $prospectCategory->action_route);
        $this->assertSame('Open Prospects', $prospectCategory->action_label);
        $this->assertNotNull($prospectCategory->resolveActionUrl());

        $assignCfmCategory = TaskCategory::query()->where('slug', 'assign-a-cfm')->first();

        $this->assertNotNull($assignCfmCategory);
        $this->assertSame('Assign a CFM', $assignCfmCategory->name);
        $this->assertSame('team.cfms', $assignCfmCategory->action_route);
        $this->assertSame('Assign CFM', $assignCfmCategory->action_label);
    }

    public function test_task_category_service_resolves_action_for_task_name(): void
    {
        $this->seed(TaskCategorySeeder::class);

        $action = app(TaskCategoryService::class)->actionForName('Training');

        $this->assertNotNull($action);
        $this->assertSame('Open Training', $action['label']);
        $this->assertStringContainsString('/training', $action['url']);
    }

    public function test_admin_can_create_update_and_archive_task_category(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('admin.management.store', 'task-categories'), [
                'name' => 'Custom Category',
                'slug' => 'custom-category',
                'description' => 'A test task category.',
                'action_route' => 'tasks.index',
                'action_url' => null,
                'action_label' => 'Start Work',
                'icon' => 'tasks',
                'accent_class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'sort_order' => 200,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $categoryId = DB::table('task_categories')->where('slug', 'custom-category')->value('id');
        $this->assertNotNull($categoryId);

        $this->actingAs($admin)
            ->patch(route('admin.management.update', ['task-categories', $categoryId]), [
                'name' => 'Custom Category Updated',
                'slug' => 'custom-category',
                'description' => 'Updated description.',
                'action_route' => 'tasks.index',
                'action_url' => null,
                'action_label' => 'Go to Tasks',
                'icon' => 'tasks',
                'accent_class' => 'bg-slate-100 text-slate-700 border-slate-200',
                'sort_order' => 210,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertSame(
            'Custom Category Updated',
            DB::table('task_categories')->where('id', $categoryId)->value('name'),
        );

        $this->actingAs($admin)
            ->delete(route('admin.management.destroy', ['task-categories', $categoryId]))
            ->assertRedirect();

        $this->assertNotNull(DB::table('task_categories')->where('id', $categoryId)->value('deleted_at'));
    }

    public function test_workflow_task_modal_item_uses_category_action_link(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TaskCategorySeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $agencyOwner = User::factory()->create();
        $agencyOwner->assignRole('agency-owner');

        $member = User::factory()->create([
            'sponsor_id' => $agencyOwner->id,
            'mentor_id' => null,
        ]);
        $member->assignRole('member');

        $stepId = DB::table('checklists')
            ->join('checklist_types', 'checklist_types.id', '=', 'checklists.checklist_type_id')
            ->where('checklist_types.code', 'licensing')
            ->value('checklists.id');

        DB::table('checklist_progress')->insert([
            'user_id' => $member->id,
            'checklist_id' => $stepId,
            'mentor_assignment_id' => null,
            'status' => 'pending_confirmation',
            'submitted_at' => now()->subDay(),
            'completed_at' => null,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $payload = app(\App\Http\Controllers\TaskController::class)->openTasksByPriorityFor($agencyOwner);
        $task = collect($payload['items'])->firstWhere('type', 'Confirmation');

        $this->assertNotNull($task);
        $this->assertSame('Licensing', $task['category']);
        $this->assertSame('Action Link', $task['action_label']);
        $this->assertStringContainsString('/licensing', $task['url']);
    }

    public function test_cfm_assignment_modal_item_uses_assign_a_cfm_category_action_link(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TaskCategorySeeder::class,
            CountrySeeder::class,
            TimezoneSeeder::class,
        ]);

        $agencyOwner = User::factory()->create();
        $agencyOwner->assignRole('agency-owner');

        $member = User::factory()->create([
            'sponsor_id' => $agencyOwner->id,
            'mentor_id' => null,
        ]);
        $member->assignRole('member');

        $payload = app(\App\Http\Controllers\TaskController::class)->openTasksByPriorityFor($agencyOwner);
        $task = collect($payload['items'])->firstWhere('type', 'CFM Assignment');

        $this->assertNotNull($task);
        $this->assertSame('Assign a CFM', $task['category']);
        $this->assertSame('Assign', $task['action_label']);
        $this->assertStringContainsString('/team/cfms', $task['url']);
        $this->assertStringNotContainsString('/admin/users', $task['url']);
    }
}
