<?php

namespace Tests\Feature;

use App\Models\Checklist;
use App\Models\MentorAssignment;
use App\Models\User;
use Database\Seeders\CfmManagementSeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CfmTraineeChecklistTest extends TestCase
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

    public function test_cfm_can_view_trainee_checklist_page(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->where('status', 'active')
            ->firstOrFail();

        $this->actingAs($cfm)
            ->get(route('cfm.portal.trainees.checklist', $assignment))
            ->assertOk()
            ->assertSee('Trainee Mentoring Checklist', false)
            ->assertSee($assignment->apprentice->name, false)
            ->assertSee('Phase 1', false)
            ->assertSee('Confirm CFM assignment', false);
    }

    public function test_cfm_can_mark_checklist_item_complete(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->where('status', 'active')
            ->firstOrFail();

        $item = Checklist::query()->forTypeCode('cfm-mentoring')->orderBy('sort_order')->firstOrFail();

        $this->actingAs($cfm)
            ->patch(route('cfm.portal.trainees.checklist.update', [$assignment, $item]), [
                'completed' => true,
            ])
            ->assertRedirect(route('cfm.portal.trainees.checklist', $assignment))
            ->assertSessionHas('profile_feedback', fn (array $feedback) => $feedback['type'] === 'success');

        $this->assertDatabaseHas('checklist_progress', [
            'mentor_assignment_id' => $assignment->id,
            'checklist_id' => $item->id,
            'status' => 'completed',
            'completed_by' => $cfm->id,
        ]);
    }

    public function test_other_cfm_cannot_access_trainee_checklist(): void
    {
        $this->seedPortal();

        $assignment = MentorAssignment::query()
            ->where('status', 'active')
            ->firstOrFail();

        $otherCfm = User::factory()->create();
        $otherCfm->assignRole('certified-field-mentor');

        $this->actingAs($otherCfm)
            ->get(route('cfm.portal.trainees.checklist', $assignment))
            ->assertForbidden();
    }

    public function test_cfm_portal_shows_track_mentoring_link(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();

        $this->actingAs($cfm)
            ->get(route('cfm.portal'))
            ->assertOk()
            ->assertSee('Track mentoring', false)
            ->assertSee('View checklist', false);
    }

    public function test_cfm_can_fetch_trainee_checklist_json_for_modal(): void
    {
        $this->seedPortal();

        $cfm = User::where('email', 'cfm@gwtracks.com')->firstOrFail();
        $assignment = MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->where('status', 'active')
            ->firstOrFail();

        $item = Checklist::query()->forTypeCode('cfm-mentoring')->orderBy('sort_order')->firstOrFail();

        $this->actingAs($cfm)
            ->patch(route('cfm.portal.trainees.checklist.update', [$assignment, $item]), [
                'completed' => true,
            ]);

        $this->actingAs($cfm)
            ->getJson(route('cfm.portal.trainees.checklist', $assignment))
            ->assertOk()
            ->assertJsonPath('trainee.name', $assignment->apprentice->name)
            ->assertJsonPath('stats.completed', 1)
            ->assertJsonStructure([
                'trainee' => ['name', 'rank'],
                'stats' => ['total', 'completed', 'remaining', 'percent'],
                'phases',
                'checklist_url',
            ]);
    }
}
