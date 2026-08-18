<?php

namespace Tests\Feature;

use App\Livewire\Tasks\TasksPage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TasksLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_tasks_page_renders_livewire_component(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->syncRoles(['member']);

        $this->actingAs($user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSeeLivewire(TasksPage::class)
            ->assertSee('Organize team priorities', false)
            ->assertSee('Task Management', false);
    }

    public function test_livewire_can_sync_filters_without_redirect(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->syncRoles(['member']);

        Livewire::actingAs($user)
            ->test(TasksPage::class)
            ->call('syncFiltersFromClient', 'follow up', 'To Do', 'High', '', '', '', '', 2, 'board')
            ->assertSet('q', 'follow up')
            ->assertSet('status', 'To Do')
            ->assertSet('priority', 'High')
            ->assertSet('page', 2)
            ->assertSet('viewMode', 'board')
            ->assertNoRedirect();
    }

    public function test_livewire_refreshes_workspace_after_task_assigned_event(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->syncRoles(['member']);

        Livewire::actingAs($user)
            ->test(TasksPage::class)
            ->dispatch('task-assigned')
            ->assertDispatched('tasks-payload-updated')
            ->assertNoRedirect();
    }
}
