<?php

namespace Tests\Feature;

use App\Models\CfmMentorProfile;
use App\Models\User;
use App\Services\CfmManagementService;
use Database\Seeders\CfmManagementSeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CfmManagementPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_owner_can_open_cfm_management_page(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $owner = User::factory()->create();
        $owner->assignRole('agency-owner');

        $this->actingAs($owner)
            ->get(route('team.cfms'))
            ->assertOk()
            ->assertSee('Certified Field Mentor Management', false)
            ->assertSee('Workload Legend', false)
            ->assertSee('Compare CFMs', false)
            ->assertSee('cfm-management-page', false);
    }

    public function test_agency_owner_can_update_cfm_licensed_jurisdictions(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TaskScenarioSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $owner = User::where('email', 'agency-owner@gwtrack.com')->firstOrFail();
        $cfm = User::where('email', 'cfm@gwtrack.com')->firstOrFail();

        $this->actingAs($owner)
            ->patch(route('team.cfms.licensed-jurisdictions.update', $cfm), [
                'licensed_jurisdictions' => ['Canada|Manitoba', 'United States|California'],
            ])
            ->assertRedirect(route('team.cfms', ['cfm' => $cfm->id]))
            ->assertSessionHas('cfm_licensed_feedback', fn (array $feedback) => $feedback['type'] === 'success');

        $cfm->refresh()->load('cfmMentorProfile');
        $this->assertSame(
            ['Canada|Manitoba', 'United States|California'],
            $cfm->cfmMentorProfile?->licensed_jurisdictions
        );

        $this->actingAs($owner)
            ->get(route('team.cfms', ['cfm' => $cfm->id]))
            ->assertOk()
            ->assertSee('Licensed jurisdictions', false)
            ->assertSee('MB, CA', false)
            ->assertSee('CA:USA', false);
    }

    public function test_payload_includes_computed_recommendations_for_seeded_fap_associate(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TaskScenarioSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $owner = User::where('email', 'agency-owner@gwtrack.com')->firstOrFail();
        $ontarioAssociate = User::where('email', 'fap.queue1@example.com')->firstOrFail();

        $payload = app(CfmManagementService::class)->payloadFor($owner);

        $this->assertArrayHasKey('recommendationsByAssociate', $payload);
        $this->assertArrayHasKey($ontarioAssociate->id, $payload['recommendationsByAssociate']);
        $this->assertNotNull($payload['defaultRecommendationAssociateId']);
        $this->assertArrayHasKey(
            $payload['defaultRecommendationAssociateId'],
            $payload['recommendationsByAssociate']
        );

        $recommendations = $payload['recommendationsByAssociate'][$ontarioAssociate->id];
        $cards = array_values(array_filter($recommendations, fn (array $row) => ! empty($row['cfmName'])));

        $this->assertNotEmpty($cards);

        $mariaCard = collect($cards)->firstWhere('cfmName', 'Maria Santos');
        $this->assertNotNull($mariaCard);
        $this->assertContains($mariaCard['statusLabel'], ['Recommended', 'Use Caution']);
        $this->assertGreaterThan(0, $mariaCard['fitScore']);

        $johnCard = collect($cards)->firstWhere('cfmName', 'John Reyes');
        $this->assertNull($johnCard);

        $this->actingAs($owner)
            ->get(route('team.cfms'))
            ->assertOk()
            ->assertSee('Smart Recommendations', false)
            ->assertSee('Maria Santos', false)
            ->assertSee('Owen Taylor', false);
    }

    public function test_cfm_viewing_own_profile_is_listed_as_my_hierarchy(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $cfmUser = User::factory()->create(['name' => 'Joey Sponsor CFM']);
        $cfmUser->assignRole(['super-admin', 'certified-field-mentor']);

        CfmMentorProfile::updateOrCreate(
            ['user_id' => $cfmUser->id],
            [
                'certification_status' => 'certified',
                'hierarchy_access' => 'my_hierarchy',
                'max_apprentices' => 6,
            ]
        );

        $self = app(CfmManagementService::class)
            ->accessibleCfms($cfmUser)
            ->firstWhere('id', $cfmUser->id);

        $this->assertNotNull($self);
        $this->assertTrue($self['inMyHierarchy']);
        $this->assertSame('My Hierarchy', $self['hierarchySource']);
        $this->assertNull($self['hierarchyNotice']);
    }

    public function test_cfm_assignment_uses_profile_insurance_licenses_when_cfm_mentor_profile_is_empty(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            \Database\Seeders\CountrySeeder::class,
            \Database\Seeders\StateProvinceSeeder::class,
        ]);

        $owner = User::factory()->create();
        $owner->assignRole('agency-owner');

        $cfm = User::factory()->create(['name' => 'Licensed CFM', 'sponsor_id' => $owner->id]);
        $cfm->assignRole('certified-field-mentor');

        $associate = User::factory()->create([
            'name' => 'California Recruit',
            'sponsor_id' => $owner->id,
            'mentor_id' => null,
            'is_active' => true,
        ]);
        $associate->assignRole('member');
        $associate->profile()->create(
            \App\Support\LocationOptions::profileLocationIds('United States', 'California')
        );

        $cfm->profile()->create([
            'insurance_licenses' => ['United States|California'],
        ]);

        app(\App\Services\DownlineHierarchyService::class)->rebuild();

        $payload = app(CfmManagementService::class)->payloadFor($owner);
        $cfmRow = collect($payload['cfms'])->firstWhere('id', $cfm->id);

        $this->assertNotNull($cfmRow);
        $this->assertContains('United States|California', $cfmRow['licensedJurisdictions']);

        $associateRow = collect($payload['assignableAssociates'])->firstWhere('id', $associate->id);
        $this->assertSame('United States|California', $associateRow['jurisdictionKey']);

        $this->actingAs($owner)
            ->postJson(route('team.cfms.assign'), [
                'associate_id' => $associate->id,
                'cfm_id' => $cfm->id,
                'notify_cfm' => false,
                'notify_associate' => false,
            ])
            ->assertOk();
    }
}
