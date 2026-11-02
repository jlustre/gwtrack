<?php

namespace Tests\Feature;

use App\Livewire\Cfm\Portal;
use App\Models\Checklist;
use App\Models\ChecklistProgress;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\ChecklistService;
use Database\Seeders\CfmManagementSeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CfmPortalPhaseTwoTest extends TestCase
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

    public function test_cfm_can_view_trainee_onboarding_center(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'onboarding')
            ->assertSee('Onboarding Center')
            ->assertSee('Onboarding not started');
    }

    public function test_cfm_can_view_started_onboarding_checklist_items(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);
        $trainee = $assignment->apprentice;

        app(ChecklistService::class)->startChecklistType($trainee, 'onboarding', $cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $trainee->id)
            ->set('activeSection', 'onboarding')
            ->assertSee('Onboarding Center')
            ->assertSee('Checklist items');
    }

    public function test_cfm_can_view_trainee_fap_center_with_mentoring_checklist(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'fap')
            ->assertSee('FAP Management Center')
            ->assertSee('CFM Mentoring Checklist');
    }

    public function test_cfm_can_view_trainee_goals_center(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'goals')
            ->assertSee('Goals & Performance')
            ->assertSee('Coaching suggestions');
    }

    public function test_cfm_can_approve_mentoring_checklist_item_from_portal(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);
        $item = Checklist::query()->forTypeCode('cfm-mentoring')->orderBy('sort_order')->firstOrFail();

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'fap')
            ->call('toggleMentoringItem', $item->id, true);

        $this->assertDatabaseHas('checklist_progress', [
            'mentor_assignment_id' => $assignment->id,
            'checklist_id' => $item->id,
            'status' => 'completed',
            'completed_by' => $cfm->id,
        ]);
    }

    public function test_cfm_can_confirm_trainee_checklist_submission_from_portal(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);
        $trainee = $assignment->apprentice;
        $checklists = app(ChecklistService::class);

        $checklists->startChecklistType($trainee, 'onboarding', $cfm);
        $item = Checklist::query()
            ->forTypeCode('onboarding')
            ->where('notified_parties', 'like', '%CFM%')
            ->orderBy('sort_order')
            ->firstOrFail();
        $checklists->updateUserProgress($trainee, $item->id, true);

        $progress = ChecklistProgress::query()
            ->where('user_id', $trainee->id)
            ->where('checklist_id', $item->id)
            ->firstOrFail();

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $trainee->id)
            ->set('activeSection', 'onboarding')
            ->call('reviewChecklistItem', $progress->id, 'confirmed');

        $this->assertDatabaseHas('checklist_progress', [
            'id' => $progress->id,
            'status' => 'completed',
            'reviewed_by' => $cfm->id,
        ]);
    }
}
