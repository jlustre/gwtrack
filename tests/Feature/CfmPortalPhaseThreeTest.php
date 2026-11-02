<?php

namespace Tests\Feature;

use App\Livewire\Cfm\Portal;
use App\Models\CfmNote;
use App\Models\CfmTask;
use App\Models\MentorAssignment;
use App\Models\User;
use Database\Seeders\CfmManagementSeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CfmPortalPhaseThreeTest extends TestCase
{
    use RefreshDatabase;

    private function seedPortal(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TaskScenarioSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            CfmManagementSeeder::class,
        ]);
    }

    private function activeAssignmentForCfm(User $cfm): MentorAssignment
    {
        return MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->where('status', 'active')
            ->firstOrFail();
    }

    public function test_cfm_can_view_task_management_center(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'tasks')
            ->assertSee('Task Management')
            ->assertSee('Assign new task');
    }

    public function test_cfm_can_create_and_complete_task_from_portal(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'tasks')
            ->set('taskTitle', 'Schedule FNA practice session')
            ->set('taskCategory', 'fap')
            ->set('taskPriority', 'high')
            ->call('createTask');

        $task = CfmTask::query()->where('trainee_id', $assignment->apprentice_id)->firstOrFail();
        $this->assertSame('Schedule FNA practice session', $task->title);
        $this->assertSame('open', $task->status);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'tasks')
            ->call('updateTaskStatus', $task->id, 'completed');

        $this->assertDatabaseHas('cfm_tasks', [
            'id' => $task->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('cfm_task_logs', [
            'cfm_task_id' => $task->id,
            'action' => 'status_changed',
        ]);
    }

    public function test_cfm_can_view_coaching_notes_center(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'notes')
            ->assertSee('Coaching Notes')
            ->assertSee('Coaching history');
    }

    public function test_cfm_can_create_update_and_delete_coaching_note(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'notes')
            ->set('noteCategory', 'strength')
            ->set('noteBody', 'Strong prospecting energy in team meetings.')
            ->call('saveNote');

        $note = CfmNote::query()->where('trainee_id', $assignment->apprentice_id)->firstOrFail();
        $this->assertSame('strength', $note->category);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'notes')
            ->call('editNote', $note->id)
            ->set('noteBody', 'Strong prospecting energy and follow-through.')
            ->call('saveNote');

        $this->assertDatabaseHas('cfm_notes', [
            'id' => $note->id,
            'body' => 'Strong prospecting energy and follow-through.',
        ]);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'notes')
            ->call('deleteNote', $note->id);

        $this->assertDatabaseMissing('cfm_notes', ['id' => $note->id]);
    }

    public function test_other_cfm_cannot_access_trainee_task(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        $task = CfmTask::query()->create([
            'cfm_id' => $cfm->id,
            'trainee_id' => $assignment->apprentice_id,
            'title' => 'Private task',
            'category' => 'coaching',
            'priority' => 'normal',
            'status' => 'open',
            'assigned_by' => $cfm->id,
        ]);

        $otherCfm = User::factory()->create();
        $otherCfm->assignRole('certified-field-mentor');

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($otherCfm)
            ->test(Portal::class)
            ->call('deleteTask', $task->id);
    }
}
