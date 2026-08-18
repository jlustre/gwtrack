<?php

namespace Tests\Feature;

use App\Models\CfmMentorProfile;
use App\Models\MentorAssignment;
use App\Models\User;
use Database\Seeders\CfmManagementSeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CfmAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_owner_can_assign_associate_to_cfm(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $agencyOwner = User::where('email', 'agency-owner@gwtrack.com')->firstOrFail();
        $cfm = User::where('email', 'maria.cfm@gwtrack.com')->firstOrFail();
        $associate = User::where('email', 'fap.queue1@example.com')->firstOrFail();

        $this->actingAs($agencyOwner)
            ->postJson(route('team.cfms.assign'), [
                'associate_id' => $associate->id,
                'cfm_id' => $cfm->id,
                'reason' => 'Ready for FAP mentorship',
                'start_date' => now()->toDateString(),
                'notes' => 'Seeded assignment test',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'pending');

        $associate->refresh();
        $this->assertNull($associate->mentor_id);

        $this->assertDatabaseHas('mentor_assignments', [
            'mentor_id' => $cfm->id,
            'apprentice_id' => $associate->id,
            'status' => 'pending',
        ]);
    }

    private function confirmAssignmentFor(User $cfm, User $associate): void
    {
        $assignment = MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->where('apprentice_id', $associate->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $this->actingAs($cfm)
            ->post(route('cfm.portal.assignments.confirm', $assignment))
            ->assertRedirect(route('cfm.portal'));
    }

    public function test_assignment_requires_cfm_approval_when_requested(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $agencyOwner = User::where('email', 'agency-owner@gwtrack.com')->firstOrFail();
        $cfm = User::where('email', 'maria.cfm@gwtrack.com')->firstOrFail();
        $associate = User::where('email', 'fap.queue2@example.com')->firstOrFail();

        $this->actingAs($agencyOwner)
            ->postJson(route('team.cfms.assign'), [
                'associate_id' => $associate->id,
                'cfm_id' => $cfm->id,
                'require_cfm_approval' => true,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'pending');

        $associate->refresh();
        $this->assertNull($associate->mentor_id);

        $this->assertSame(1, MentorAssignment::query()
            ->where('mentor_id', $cfm->id)
            ->where('apprentice_id', $associate->id)
            ->where('status', 'pending')
            ->count());
    }

    public function test_assignment_skips_cfm_approval_when_not_required(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $agencyOwner = User::where('email', 'agency-owner@gwtrack.com')->firstOrFail();
        $cfm = User::where('email', 'maria.cfm@gwtrack.com')->firstOrFail();
        $associate = User::where('email', 'fap.queue2@example.com')->firstOrFail();

        $this->actingAs($agencyOwner)
            ->postJson(route('team.cfms.assign'), [
                'associate_id' => $associate->id,
                'cfm_id' => $cfm->id,
                'require_cfm_approval' => false,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'active');

        $associate->refresh();
        $this->assertSame($cfm->id, $associate->mentor_id);

        $this->assertDatabaseHas('mentor_assignments', [
            'mentor_id' => $cfm->id,
            'apprentice_id' => $associate->id,
            'status' => 'active',
        ]);
    }

    public function test_cannot_assign_cfm_as_own_trainee(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
        ]);

        $cfm = User::factory()->create();
        $cfm->assignRole(['super-admin', 'certified-field-mentor']);

        CfmMentorProfile::updateOrCreate(
            ['user_id' => $cfm->id],
            [
                'certification_status' => 'certified',
                'hierarchy_access' => 'my_hierarchy',
                'max_apprentices' => 6,
                'licensed_jurisdictions' => ['Canada|Ontario'],
            ]
        );

        $cfm->profile()->updateOrCreate(
            ['user_id' => $cfm->id],
            [
                'country' => 'Canada',
                'province' => 'Ontario',
                'insurance_licenses' => ['Canada|Ontario'],
            ]
        );

        $this->actingAs($cfm)
            ->postJson(route('team.cfms.assign'), [
                'associate_id' => $cfm->id,
                'cfm_id' => $cfm->id,
                'require_cfm_approval' => false,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cfm_id']);
    }

    public function test_sponsor_cfm_can_be_assigned_to_direct_recruit_as_upline(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
        ]);

        $sponsorCfm = User::factory()->create(['name' => 'Sponsor CFM']);
        $sponsorCfm->assignRole(['super-admin', 'certified-field-mentor']);

        CfmMentorProfile::updateOrCreate(
            ['user_id' => $sponsorCfm->id],
            [
                'certification_status' => 'certified',
                'hierarchy_access' => 'my_hierarchy',
                'max_apprentices' => 6,
                'licensed_jurisdictions' => ['Canada|Ontario'],
            ]
        );

        $sponsorCfm->profile()->updateOrCreate(
            ['user_id' => $sponsorCfm->id],
            [
                'country' => 'Canada',
                'province' => 'Ontario',
                'insurance_licenses' => ['Canada|Ontario'],
            ]
        );

        $recruit = User::factory()->create([
            'name' => 'Joey Lustre',
            'sponsor_id' => $sponsorCfm->id,
        ]);
        $recruit->profile()->updateOrCreate(
            ['user_id' => $recruit->id],
            [
                'country' => 'Canada',
                'province' => 'Ontario',
            ]
        );

        app(\App\Services\DownlineHierarchyService::class)->rebuild();

        $this->actingAs($sponsorCfm)
            ->postJson(route('team.cfms.assign'), [
                'associate_id' => $recruit->id,
                'cfm_id' => $sponsorCfm->id,
                'require_cfm_approval' => false,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'active');

        $recruit->refresh();
        $this->assertSame($sponsorCfm->id, $recruit->mentor_id);
    }

    public function test_agency_owner_can_assign_associate_via_web_form(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $agencyOwner = User::where('email', 'agency-owner@gwtrack.com')->firstOrFail();
        $cfm = User::where('email', 'maria.cfm@gwtrack.com')->firstOrFail();
        $associate = User::where('email', 'fap.queue1@example.com')->firstOrFail();

        $this->actingAs($agencyOwner)
            ->post(route('team.cfms.assign'), [
                'associate_id' => $associate->id,
                'cfm_id' => $cfm->id,
                'reason' => 'Ready for mentorship',
            ])
            ->assertRedirect(route('team.cfms'))
            ->assertSessionHas('status');

        $this->confirmAssignmentFor($cfm, $associate);

        $associate->refresh();
        $this->assertSame($cfm->id, $associate->mentor_id);
    }

    public function test_cannot_assign_cfm_not_licensed_in_associate_jurisdiction(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $agencyOwner = User::where('email', 'agency-owner@gwtrack.com')->firstOrFail();
        $celeste = User::where('email', 'cfm@gwtrack.com')->firstOrFail();
        $associate = User::where('email', 'fap.queue4@example.com')->firstOrFail();

        $this->actingAs($agencyOwner)
            ->postJson(route('team.cfms.assign'), [
                'associate_id' => $associate->id,
                'cfm_id' => $celeste->id,
                'reason' => 'Should be blocked by jurisdiction rule',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cfm_id']);

        $associate->refresh();
        $this->assertNull($associate->mentor_id);
    }

    public function test_can_assign_when_cfm_is_licensed_in_associate_jurisdiction(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $agencyOwner = User::where('email', 'agency-owner@gwtrack.com')->firstOrFail();
        $maria = User::where('email', 'maria.cfm@gwtrack.com')->firstOrFail();
        $associate = User::where('email', 'fap.queue2@example.com')->firstOrFail();

        $this->actingAs($agencyOwner)
            ->postJson(route('team.cfms.assign'), [
                'associate_id' => $associate->id,
                'cfm_id' => $maria->id,
            ])
            ->assertOk();

        $this->confirmAssignmentFor($maria, $associate);

        $associate->refresh();
        $this->assertSame($maria->id, $associate->mentor_id);
    }

    public function test_can_assign_us_associate_to_us_licensed_cfm(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $agencyOwner = User::where('email', 'agency-owner@gwtrack.com')->firstOrFail();
        $james = User::where('email', 'james.cfm@gwtrack.com')->firstOrFail();
        $associate = User::where('email', 'fap.queue.us-tx@example.com')->firstOrFail();

        $this->actingAs($agencyOwner)
            ->postJson(route('team.cfms.assign'), [
                'associate_id' => $associate->id,
                'cfm_id' => $james->id,
            ])
            ->assertOk();

        $this->confirmAssignmentFor($james, $associate);

        $associate->refresh();
        $this->assertSame($james->id, $associate->mentor_id);
    }

    public function test_cannot_assign_us_associate_to_unlicensed_us_cfm(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            EmailTemplateSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $agencyOwner = User::where('email', 'agency-owner@gwtrack.com')->firstOrFail();
        $lisa = User::where('email', 'lisa.cfm@gwtrack.com')->firstOrFail();
        $associate = User::where('email', 'fap.queue.us-tx@example.com')->firstOrFail();

        $this->actingAs($agencyOwner)
            ->postJson(route('team.cfms.assign'), [
                'associate_id' => $associate->id,
                'cfm_id' => $lisa->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cfm_id']);
    }
}
