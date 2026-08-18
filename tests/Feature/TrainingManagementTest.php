<?php

namespace Tests\Feature;

use App\Livewire\Admin\Training\TrainingManagement;
use App\Models\MentorAssignment;
use App\Models\TaskUser;
use App\Models\TrainingPath;
use App\Models\User;
use App\Models\UserTrainingPathEnrollment;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskCategorySeeder;
use Database\Seeders\TaskSeeder;
use Database\Seeders\TrainingAcademySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrainingManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $cfm;

    private User $associate;

    private TrainingPath $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(TrainingAcademySeeder::class);
        $this->seed(TaskCategorySeeder::class);
        $this->seed(TaskSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');

        $this->cfm = User::factory()->create();
        $this->cfm->assignRole('certified-field-mentor');

        $this->associate = User::factory()->create();
        $this->associate->assignRole('member');

        MentorAssignment::query()->create([
            'mentor_id' => $this->cfm->id,
            'apprentice_id' => $this->associate->id,
            'assigned_by' => $this->admin->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        $this->path = TrainingPath::query()->where('is_active', true)->firstOrFail();
    }

    public function test_admin_can_open_training_management_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.training.management'))
            ->assertOk()
            ->assertSeeText('Training Management');
    }

    public function test_cfm_can_open_training_management_page(): void
    {
        $this->actingAs($this->cfm)
            ->get(route('admin.training.management'))
            ->assertOk()
            ->assertSeeText('Training Management');
    }

    public function test_member_cannot_open_training_management_page(): void
    {
        $this->actingAs($this->associate)
            ->get(route('admin.training.management'))
            ->assertForbidden();
    }

    public function test_admin_can_enroll_associate_in_learning_path(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TrainingManagement::class)
            ->set('enrollUserId', $this->associate->id)
            ->set('enrollPathId', $this->path->id)
            ->call('enrollAssociate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('user_training_path_enrollments', [
            'user_id' => $this->associate->id,
            'training_path_id' => $this->path->id,
            'assigned_by' => $this->admin->id,
        ]);
    }

    public function test_cfm_can_assign_enroll_task_to_apprentice(): void
    {
        Livewire::actingAs($this->cfm)
            ->test(TrainingManagement::class)
            ->set('tab', 'task')
            ->set('taskUserId', $this->associate->id)
            ->set('taskPathId', $this->path->id)
            ->set('taskDueDate', now()->addDays(5)->format('Y-m-d'))
            ->set('taskNotes', 'Complete orientation modules first.')
            ->call('assignEnrollTask')
            ->assertHasNoErrors();

        $taskUser = TaskUser::query()
            ->where('assignee_id', $this->associate->id)
            ->where('assignor_id', $this->cfm->id)
            ->first();

        $this->assertNotNull($taskUser);
        $this->assertSame('to_do', $taskUser->status);
        $this->assertStringContainsString($this->path->name, (string) $taskUser->additional_notes);
        $this->assertDatabaseMissing('user_training_path_enrollments', [
            'user_id' => $this->associate->id,
            'training_path_id' => $this->path->id,
        ]);
    }

    public function test_assign_enroll_task_blocked_when_already_enrolled(): void
    {
        UserTrainingPathEnrollment::query()->create([
            'user_id' => $this->associate->id,
            'training_path_id' => $this->path->id,
            'assigned_by' => $this->admin->id,
            'status' => 'in_progress',
            'progress_percent' => 0,
            'started_at' => now(),
        ]);

        Livewire::actingAs($this->cfm)
            ->test(TrainingManagement::class)
            ->set('taskUserId', $this->associate->id)
            ->set('taskPathId', $this->path->id)
            ->call('assignEnrollTask')
            ->assertHasErrors(['taskPathId']);
    }
}
