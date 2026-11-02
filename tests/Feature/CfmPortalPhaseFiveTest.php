<?php

namespace Tests\Feature;

use App\Livewire\Cfm\Portal;
use App\Models\CfmActionPlan;
use App\Models\CfmPromotion;
use App\Models\CfmRiskScore;
use App\Models\MentorAssignment;
use App\Models\User;
use Database\Seeders\CfmManagementSeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CfmPortalPhaseFiveTest extends TestCase
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

    public function test_cfm_can_view_risk_center_with_automated_assessment(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'risk')
            ->assertSee('Risk & Action Plans')
            ->assertSee('Refresh assessment');

        $this->assertDatabaseHas('cfm_risk_scores', [
            'cfm_id' => $cfm->id,
            'trainee_id' => $assignment->apprentice_id,
        ]);
    }

    public function test_cfm_can_refresh_risk_assessment_and_create_action_plan(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'risk')
            ->call('runRiskAssessment')
            ->set('actionPlanTitle', 'Recovery coaching plan')
            ->set('actionPlanSteps', "Weekly check-in\nComplete licensing module")
            ->call('createActionPlan');

        $this->assertDatabaseHas('cfm_action_plans', [
            'cfm_id' => $cfm->id,
            'trainee_id' => $assignment->apprentice_id,
            'title' => 'Recovery coaching plan',
            'status' => 'active',
        ]);

        $this->assertGreaterThanOrEqual(1, CfmRiskScore::query()->where('trainee_id', $assignment->apprentice_id)->count());
    }

    public function test_cfm_can_view_promotion_readiness_center(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'promotion')
            ->assertSee('Promotion Readiness')
            ->assertSee('Refresh readiness');

        $this->assertDatabaseHas('cfm_promotions', [
            'cfm_id' => $cfm->id,
            'trainee_id' => $assignment->apprentice_id,
        ]);
    }

    public function test_cfm_can_refresh_and_update_promotion_status(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'promotion')
            ->call('refreshPromotionReadiness')
            ->set('promotionStatus', 'nominated')
            ->call('updatePromotionStatus');

        $this->assertDatabaseHas('cfm_promotions', [
            'cfm_id' => $cfm->id,
            'trainee_id' => $assignment->apprentice_id,
            'status' => 'nominated',
        ]);
    }

    public function test_cfm_can_export_trainee_roster_csv(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();

        $response = $this->actingAs($cfm)
            ->get(route('cfm.portal.roster.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Name', $content);
        $this->assertStringContainsString('Risk Score', $content);
        $this->assertStringContainsString('Promotion Readiness %', $content);
    }

    public function test_cfm_can_complete_action_plan(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = $this->activeAssignmentForCfm($cfm);

        $plan = CfmActionPlan::query()->create([
            'cfm_id' => $cfm->id,
            'trainee_id' => $assignment->apprentice_id,
            'title' => 'Test plan',
            'status' => 'active',
            'created_by' => $cfm->id,
        ]);

        Livewire::actingAs($cfm)
            ->test(Portal::class)
            ->set('selectedTraineeId', $assignment->apprentice_id)
            ->set('activeSection', 'risk')
            ->call('completeActionPlan', $plan->id);

        $this->assertDatabaseHas('cfm_action_plans', [
            'id' => $plan->id,
            'status' => 'completed',
        ]);
    }
}
