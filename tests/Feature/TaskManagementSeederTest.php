<?php



namespace Tests\Feature;



use App\Models\Task;
use App\Models\TaskSuggestion;

use App\Models\TaskUser;

use App\Models\User;

use Database\Seeders\ChecklistSeeder;

use Database\Seeders\ChecklistTypeSeeder;

use Database\Seeders\RankSeeder;

use Database\Seeders\RolePermissionSeeder;

use Database\Seeders\TaskCategorySeeder;

use Database\Seeders\TaskManagementSeeder;

use Database\Seeders\TaskScenarioSeeder;

use Database\Seeders\TeamSeeder;

use Illuminate\Foundation\Testing\RefreshDatabase;

use Tests\TestCase;



class TaskManagementSeederTest extends TestCase

{

    use RefreshDatabase;



    public function test_task_management_seeder_populates_database_backed_tasks(): void

    {

        $this->seed([

            RankSeeder::class,

            RolePermissionSeeder::class,

            TeamSeeder::class,

            ChecklistTypeSeeder::class,

            ChecklistSeeder::class,

            TaskCategorySeeder::class,

            TaskScenarioSeeder::class,

            TaskManagementSeeder::class,

        ]);



        $agencyOwner = User::where('email', 'agency-owner@gwtrack.com')->firstOrFail();



        $this->assertGreaterThanOrEqual(20, TaskUser::count());

        $this->assertGreaterThanOrEqual(5, TaskSuggestion::count());

        $davidTaskLibrary = Task::query()
            ->where('title', 'Follow up with David Kim on life insurance application')
            ->firstOrFail();

        $this->assertDatabaseHas('task_users', [
            'assignee_id' => $agencyOwner->id,
            'task_id' => $davidTaskLibrary->id,
            'status' => 'overdue',
        ]);

        $davidTask = TaskUser::query()
            ->where('assignee_id', $agencyOwner->id)
            ->where('task_id', $davidTaskLibrary->id)
            ->firstOrFail();

        $this->assertGreaterThanOrEqual(2, $davidTask->checklistItems()->count());

        $this->assertGreaterThanOrEqual(1, $davidTask->comments()->count());
    }
}

