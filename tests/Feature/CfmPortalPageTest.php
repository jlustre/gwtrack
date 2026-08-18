<?php

namespace Tests\Feature;

use App\Livewire\Cfm\Portal;
use App\Models\User;
use App\Services\CfmManagementService;
use App\Services\CfmPortalService;
use App\Support\LocationOptions;
use Database\Seeders\CfmManagementSeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateProvinceSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CfmPortalPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_cfm_can_open_cfm_portal(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            TaskScenarioSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $cfm = User::where('email', 'cfm@gwtrack.com')->firstOrFail();

        $this->actingAs($cfm)
            ->get(route('cfm.portal'))
            ->assertOk()
            ->assertSee('CFM Portal', false)
            ->assertSee('Celeste Navarro', false)
            ->assertSee('My Trainees', false)
            ->assertSee('CFM Training Progress', false)
            ->assertSee('Agency Owner', false)
            ->assertSee('Arielle Morgan', false)
            ->assertSee('Edit', false);
    }

    public function test_cfm_portal_excludes_cfm_from_own_trainee_list(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            TaskScenarioSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $cfm = User::where('email', 'cfm@gwtrack.com')->firstOrFail();
        $cfm->update(['mentor_id' => $cfm->id]);

        app(CfmPortalService::class)->payloadFor($cfm);

        $cfm->refresh();
        $this->assertNull($cfm->mentor_id);

        $profile = app(CfmManagementService::class)->profileFor($cfm, $cfm);

        $this->assertFalse(
            collect($profile['apprentices'])->contains(fn (array $trainee) => (int) $trainee['id'] === (int) $cfm->id)
        );

        $this->actingAs($cfm)
            ->get(route('cfm.portal'))
            ->assertOk()
            ->assertSee('Celeste Navarro', false);
    }

    public function test_cfm_can_update_portal_profile(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            TaskScenarioSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $cfm = User::where('email', 'cfm@gwtrack.com')->firstOrFail();

        $this->actingAs($cfm)
            ->patch(route('cfm.portal.profile.update'), [
                'phone' => '555-0100',
                'city' => 'Toronto',
                'state_province_id' => LocationOptions::resolveStateProvinceId('Canada', 'Ontario'),
                'country_id' => LocationOptions::resolveCountryId('Canada'),
                'timezone_id' => LocationOptions::resolveTimezoneId('Canada Eastern Time'),
                'languages' => 'English, French',
                'specialties' => 'Field Apprenticeship, Licensing',
                'licensed_jurisdictions' => ['Canada|Ontario', 'Canada|Quebec'],
                'mentor_bio' => 'Updated mentor bio for testing.',
                'manual_unavailable' => '1',
            ])
            ->assertRedirect(route('cfm.portal'))
            ->assertSessionHas('profile_feedback', fn (array $feedback) => $feedback['type'] === 'success');

        $cfm->refresh()->load(['profile', 'cfmMentorProfile']);

        $this->assertSame('555-0100', $cfm->profile?->phone);
        $this->assertSame('Toronto', $cfm->profile?->city);
        $this->assertSame('Updated mentor bio for testing.', $cfm->cfmMentorProfile?->mentor_bio);
        $this->assertSame(['English', 'French'], $cfm->cfmMentorProfile?->languages);
        $this->assertTrue($cfm->cfmMentorProfile?->manual_unavailable);
        $this->assertSame(['Canada|Ontario', 'Canada|Quebec'], $cfm->cfmMentorProfile?->licensed_jurisdictions);
    }

    public function test_cfm_portal_displays_licensed_jurisdictions(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            TaskScenarioSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $cfm = User::where('email', 'cfm@gwtrack.com')->firstOrFail();

        $this->actingAs($cfm)
            ->get(route('cfm.portal'))
            ->assertOk()
            ->assertSee('Licensed jurisdictions', false)
            ->assertSee('ON:CAN', false)
            ->assertSee('BC:CAN', false);
    }

    public function test_cfm_profile_update_shows_validation_errors_in_modal(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            TaskScenarioSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $cfm = User::where('email', 'cfm@gwtrack.com')->firstOrFail();

        $this->actingAs($cfm)
            ->from(route('cfm.portal'))
            ->patch(route('cfm.portal.profile.update'), [
                'country_id' => LocationOptions::resolveCountryId('Canada'),
                'state_province_id' => 999999,
                'timezone_id' => 999999,
            ])
            ->assertRedirect(route('cfm.portal'))
            ->assertSessionHas('open_edit_profile_modal', true)
            ->assertSessionHas('profile_feedback', fn (array $feedback) => $feedback['type'] === 'error')
            ->assertSessionHasErrors(['state_province_id', 'timezone_id']);

        $this->actingAs($cfm)
            ->get(route('cfm.portal'))
            ->assertOk()
            ->assertSee('Could not save profile', false)
            ->assertSee('Edit CFM Profile', false)
            ->assertSee('Save Profile', false);
    }

    public function test_cfm_profile_update_shows_success_message(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            TaskScenarioSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $cfm = User::where('email', 'cfm@gwtrack.com')->firstOrFail();

        $this->actingAs($cfm)
            ->patch(route('cfm.portal.profile.update'), [
                'phone' => '555-0199',
                'city' => 'Vancouver',
                'state_province_id' => LocationOptions::resolveStateProvinceId('Canada', 'British Columbia'),
                'country_id' => LocationOptions::resolveCountryId('Canada'),
                'timezone_id' => LocationOptions::resolveTimezoneId('PST'),
            ])
            ->assertRedirect(route('cfm.portal'));

        $this->actingAs($cfm)
            ->get(route('cfm.portal'))
            ->assertOk()
            ->assertSee('Profile saved', false)
            ->assertSee('Your CFM profile was updated successfully.', false);
    }

    public function test_admin_cannot_update_cfm_portal_profile(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            TaskScenarioSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->patch(route('cfm.portal.profile.update'), [
                'phone' => '555-0100',
                'mentor_bio' => 'Should not save.',
            ])
            ->assertForbidden();
    }

    public function test_admin_viewing_cfm_portal_does_not_see_edit_button(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            TaskScenarioSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('cfm.portal'));

        $response->assertOk()->assertSee('Admin view', false);

        $profileSection = str($response->getContent())->after('>Profile</h3>')->before('Achievements')->toString();
        $this->assertStringNotContainsString('>Edit</button>', $profileSection);
    }

    public function test_member_cannot_open_cfm_portal(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('cfm.portal'))
            ->assertForbidden();
    }

    public function test_agency_owner_cannot_open_cfm_portal(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $owner = User::factory()->create();
        $owner->assignRole('agency-owner');

        $this->actingAs($owner)
            ->get(route('cfm.portal'))
            ->assertForbidden();
    }

    public function test_cfm_trainee_360_quick_actions_open_modals_without_navigation(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            TaskScenarioSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $cfm = User::where('email', 'cfm@gwtrack.com')->firstOrFail();
        $trainee = User::where('email', 'maya.fap@example.com')->firstOrFail();

        Livewire::actingAs($cfm)
            ->test(Portal::class, ['selectedTraineeId' => $trainee->id])
            ->assertSee('Send Message', false)
            ->assertDontSee('mailto:', false)
            ->call('openTraineeQuickActionModal', 'message')
            ->assertSet('traineeQuickActionModal', 'message')
            ->assertSee('Message Maya Chen through GWTrack messaging', false)
            ->set('quickMessageBody', 'Great progress on your FAP checklist this week.')
            ->call('sendQuickMessage')
            ->assertSet('traineeQuickActionModal', null)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('messages', [
            'user_id' => $cfm->id,
            'body' => 'Great progress on your FAP checklist this week.',
        ]);
    }
}
