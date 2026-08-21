<?php

namespace Tests\Feature;

use App\Mail\InvitationLinkMail;
use App\Models\Profile;
use App\Models\RegistrationInvitation;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use App\Support\LocationOptions;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateProvinceSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\StartsChecklistTypes;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;
    use StartsChecklistTypes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();
        $user->syncRoles(['member']);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertDontSee('Delete Account')
            ->assertSee('Profile Details', false)
            ->assertSee('Update Profile Photo', false)
            ->assertSee('Recruits', false)
            ->assertSee('Annual Premium', false)
            ->assertSee('Checklists', false)
            ->assertSee('GW Details', false)
            ->assertSee('Greatway invite URL', false)
            ->assertSee('Save GW Details', false)
            ->assertSee('Profile Completion', false)
            ->assertSee('Profile Tracking', false);
    }

    public function test_profile_checklists_tab_shows_started_checklist_summaries(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $user = User::factory()->create();
        $this->startChecklistType($user, 'onboarding', startedAt: '2026-01-01');
        $this->startChecklistType($user, 'licensing');

        $this
            ->actingAs($user)
            ->get(route('profile.edit', ['tab' => 'checklists']))
            ->assertOk()
            ->assertSee('Checklists', false)
            ->assertSee('Onboarding', false)
            ->assertSee('Licensing', false)
            ->assertSee('View details', false)
            ->assertSee('Due', false)
            ->assertSee('text-red-600', false)
            ->assertDontSee('No checklists started yet', false);
    }

    public function test_profile_page_shows_completion_checklist_with_missing_fields(): void
    {
        $this->seed([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            \Database\Seeders\ProfileCompletionFieldSeeder::class,
        ]);

        $user = User::factory()->create([
            'name' => 'Checklist Member',
            'email' => 'checklist@example.com',
        ]);

        Profile::query()->create(array_merge([
            'user_id' => $user->id,
            'phone' => '555-0100',
        ], LocationOptions::profileLocationIds('Canada', 'Ontario', 'Canada Eastern Time')));

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Profile Completion', false)
            ->assertSee('Still needed', false)
            ->assertSee('GW associate ID', false)
            ->assertSee('Profile photo', false)
            ->assertSee('Complete profile details', false)
            ->assertSee('Phone number', false);
    }

    public function test_profile_page_shows_tab_listing_tables_with_seeded_data(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            RankSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
        ]);

        $cfm = User::where('email', 'cfm@gwtrack.com')->firstOrFail();
        $this->startChecklistType($cfm, 'onboarding');

        $this->actingAs($cfm)
            ->get(route('profile.edit', ['tab' => 'profile']))
            ->assertOk()
            ->assertSee('Agency Owner', false)
            ->assertSee('Arielle Morgan', false)
            ->assertSee('Best Contact Time', false)
            ->assertDontSee('Member Information', false);

        $this->actingAs($cfm)
            ->get(route('profile.edit', ['tab' => 'checklists']))
            ->assertOk()
            ->assertSee('Onboarding', false)
            ->assertSee('View details', false);
    }

    public function test_recruits_tab_shows_levels_and_total_with_search_and_filters(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            RankSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $sponsor = User::factory()->create(['name' => 'Tab Sponsor']);
        $levelOne = User::factory()->create([
            'name' => 'Recruit Level One',
            'email' => 'recruit.level1@example.com',
            'sponsor_id' => $sponsor->id,
            'is_active' => true,
        ]);
        $levelOne->assignRole('certified-field-mentor');
        Profile::query()->create(array_merge([
            'user_id' => $levelOne->id,
            'phone' => '+1 416-555-0100',
        ], LocationOptions::profileLocationIds('Canada', 'Ontario')));
        User::factory()->create([
            'name' => 'Recruit Level Two',
            'email' => 'recruit.level2@example.com',
            'sponsor_id' => $levelOne->id,
            'is_active' => true,
        ]);

        app(DownlineHierarchyService::class)->rebuild();

        $this->actingAs($sponsor)
            ->get(route('profile.edit', ['tab' => 'recruits']))
            ->assertOk()
            ->assertSee('Total recruits: 2', false)
            ->assertSee('1 at Level 1', false)
            ->assertSee('Level', false)
            ->assertSee('Member', false)
            ->assertSee('Role', false)
            ->assertSee('Location', false)
            ->assertSee('Recruit Level One', false)
            ->assertSee('recruit.level1@example.com', false)
            ->assertSee('+1 416-555-0100', false)
            ->assertSee('Recruit Level Two', false)
            ->assertSee('CFM', false)
            ->assertSee('Ontario', false)
            ->assertSee('Search by name, email, phone, role, rank, sponsor', false)
            ->assertSee('All levels', false)
            ->assertSee('All roles', false)
            ->assertSee('All provinces', false)
            ->assertSee('Clear filters', false);

        $this->actingAs($sponsor)
            ->get(route('profile.edit', ['tab' => 'direct-recruits']))
            ->assertRedirect(route('profile.edit', ['tab' => 'recruits']));

        $this->actingAs($sponsor)
            ->get(route('profile.edit', ['tab' => 'annual-premium']))
            ->assertOk()
            ->assertSee('Search by source, description, period', false)
            ->assertSee('All sources', false)
            ->assertSee('Filtered total', false);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $this->seed([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
        ]);

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '555-123-4567',
                'state_province_id' => LocationOptions::resolveStateProvinceId('Canada', 'Ontario'),
                'city' => 'Toronto',
                'country_id' => LocationOptions::resolveCountryId('Canada'),
                'timezone_id' => LocationOptions::resolveTimezoneId('Canada Eastern Time'),
                'best_contact_time' => 'Morning (8am – 12pm)',
                'gw_associate_id' => 'GW-2001',
                'gw_invite_link' => 'https://gw.example.com/invite/2001',
                'bio' => 'Building a strong financial services team.',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit', ['tab' => 'profile']))
            ->assertSessionHas('profile_feedback', fn (array $feedback) => $feedback['type'] === 'success');

        // Profile update clears email verification on the authenticated user instance; restore
        // in-memory verification so verified middleware allows the follow-up page assertions.
        $user->forceFill(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('profile.edit', ['tab' => 'profile']))
            ->assertOk()
            ->assertSee('Profile saved', false)
            ->assertSee('Your profile was updated successfully.', false);

        $ontarioId = LocationOptions::resolveStateProvinceId('Canada', 'Ontario');

        $this->actingAs($user)
            ->get(route('profile.edit', ['tab' => 'profile']))
            ->assertOk()
            ->assertSee('value="'.$ontarioId.'"', false)
            ->assertSee('wire:model="stateProvinceId"', false);

        $user->refresh()->load('profile.stateProvince', 'profile.countryRecord', 'profile.timezoneRecord');

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        $this->assertSame('555-123-4567', $user->profile->phone);
        $this->assertSame('Ontario', $user->profile->province);
        $this->assertSame('Toronto', $user->profile->city);
        $this->assertSame('Canada', $user->profile->country);
        $this->assertSame('Canada Eastern Time', $user->profile->timezone);
        $this->assertSame('Morning (8am – 12pm)', $user->profile->best_contact_time);
        $this->assertSame('GW-2001', $user->profile->gw_associate_id);
        $this->assertSame('https://gw.example.com/invite/2001', $user->profile->gw_invite_link);
    }

    public function test_member_can_save_multiple_insurance_licenses_on_licenses_tab(): void
    {
        $this->seed([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->patch(route('profile.licenses.update'), [
                'is_licensed_life_insurance' => true,
                'insurance_licenses' => [
                    'Canada|Ontario',
                    'Canada|British Columbia',
                    'United States|California',
                ],
                'insurance_license_numbers' => [
                    'Canada|Ontario' => 'ON-1001',
                    'Canada|British Columbia' => 'BC-2002',
                    'United States|California' => 'CA-3003',
                ],
                'insurance_license_expirations' => [
                    'Canada|Ontario' => '2027-06-30',
                    'Canada|British Columbia' => '2028-01-15',
                    'United States|California' => '2026-12-31',
                ],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit', ['tab' => 'licenses']))
            ->assertSessionHas('licenses_feedback', fn (array $feedback) => $feedback['type'] === 'success');

        $user->refresh();

        $this->assertSame(
            ['Canada|British Columbia', 'Canada|Ontario', 'United States|California'],
            $user->profile->insurance_licenses
        );
        $this->assertSame(
            [
                'Canada|British Columbia' => 'BC-2002',
                'Canada|Ontario' => 'ON-1001',
                'United States|California' => 'CA-3003',
            ],
            $user->profile->insurance_license_numbers
        );
        $this->assertSame(
            [
                'Canada|British Columbia' => '2028-01-15',
                'Canada|Ontario' => '2027-06-30',
                'United States|California' => '2026-12-31',
            ],
            $user->profile->insurance_license_expirations
        );
        $this->assertTrue($user->profile->is_licensed_life_insurance);

        $this->actingAs($user)
            ->get(route('profile.edit', ['tab' => 'licenses']))
            ->assertOk()
            ->assertSee('Insurance Licenses', false)
            ->assertSee('I am licensed in Life Insurance', false)
            ->assertSee('Enter the license number and expiration date for every selected region before saving.', false)
            ->assertSee('License number', false)
            ->assertSee('Expiration date', false)
            ->assertSee('ON:CAN', false)
            ->assertSee('BC:CAN', false)
            ->assertSee('CA:USA', false)
            ->assertSee('ON-1001', false)
            ->assertSee('BC-2002', false)
            ->assertSee('CA-3003', false)
            ->assertSee('Jun 30, 2027', false)
            ->assertSee('Jan 15, 2028', false)
            ->assertSee('Dec 31, 2026', false);
    }

    public function test_cfm_profile_license_save_syncs_cfm_mentor_licensed_jurisdictions(): void
    {
        $this->seed([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('certified-field-mentor');

        $this->actingAs($user)
            ->patch(route('profile.licenses.update'), [
                'is_licensed_life_insurance' => true,
                'insurance_licenses' => [
                    'United States|California',
                ],
                'insurance_license_numbers' => [
                    'United States|California' => 'CA-5555',
                ],
                'insurance_license_expirations' => [
                    'United States|California' => '2027-12-31',
                ],
            ])
            ->assertSessionHasNoErrors();

        $user->refresh()->load(['profile', 'cfmMentorProfile']);

        $this->assertSame(['United States|California'], $user->profile->insurance_licenses);
        $this->assertSame(['United States|California' => 'CA-5555'], $user->profile->insurance_license_numbers);
        $this->assertSame(['United States|California' => '2027-12-31'], $user->profile->insurance_license_expirations);
        $this->assertSame(['United States|California'], $user->cfmMentorProfile?->licensed_jurisdictions);
    }

    public function test_unchecking_life_insurance_license_clears_saved_jurisdictions(): void
    {
        $this->seed([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->profile()->create([
            'is_licensed_life_insurance' => true,
            'insurance_licenses' => ['Canada|Ontario'],
        ]);

        $this->actingAs($user)
            ->patch(route('profile.licenses.update'), [
                'is_licensed_life_insurance' => false,
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertFalse($user->profile->is_licensed_life_insurance);
        $this->assertSame([], $user->profile->insurance_licenses);
        $this->assertSame([], $user->profile->insurance_license_numbers);
        $this->assertSame([], $user->profile->insurance_license_expirations);
    }

    public function test_member_cannot_save_a_selected_jurisdiction_without_a_license_number(): void
    {
        $this->seed([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->from(route('profile.edit', ['tab' => 'licenses']))
            ->patch(route('profile.licenses.update'), [
                'is_licensed_life_insurance' => true,
                'insurance_licenses' => [
                    'Canada|Ontario',
                ],
                'insurance_license_numbers' => [
                    'Canada|Ontario' => '',
                ],
            ])
            ->assertRedirect(route('profile.edit', ['tab' => 'licenses']))
            ->assertSessionHasErrors('insurance_license_numbers.Canada|Ontario');

        $this->assertNull($user->fresh()->profile?->insurance_licenses);
    }

    public function test_member_cannot_save_a_selected_jurisdiction_without_an_expiration_date(): void
    {
        $this->seed([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->from(route('profile.edit', ['tab' => 'licenses']))
            ->patch(route('profile.licenses.update'), [
                'is_licensed_life_insurance' => true,
                'insurance_licenses' => [
                    'Canada|Ontario',
                ],
                'insurance_license_numbers' => [
                    'Canada|Ontario' => 'ON-1001',
                ],
                'insurance_license_expirations' => [
                    'Canada|Ontario' => '',
                ],
            ])
            ->assertRedirect(route('profile.edit', ['tab' => 'licenses']))
            ->assertSessionHasErrors('insurance_license_expirations.Canada|Ontario');

        $this->assertNull($user->fresh()->profile?->insurance_licenses);
    }

    public function test_profile_update_shows_validation_errors_on_profile_tab(): void
    {
        $this->seed([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit', ['tab' => 'profile']))
            ->patch(route('profile.update'), [
                'name' => 'Test User',
                'email' => 'not-an-email',
                'country_id' => LocationOptions::resolveCountryId('Canada'),
                'state_province_id' => LocationOptions::resolveStateProvinceId('United States', 'California'),
                'timezone_id' => 999999,
            ])
            ->assertRedirect(route('profile.edit', ['tab' => 'profile']))
            ->assertSessionHas('profile_feedback', fn (array $feedback) => $feedback['type'] === 'error')
            ->assertSessionHasErrors(['email', 'state_province_id', 'timezone_id']);

        $this->actingAs($user)
            ->get(route('profile.edit', ['tab' => 'profile']))
            ->assertOk()
            ->assertSee('Could not save profile', false)
            ->assertSee('Save Profile', false);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit', ['tab' => 'profile']));

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_member_can_save_greatway_invite_link(): void
    {
        $user = User::factory()->create();
        $inviteLink = 'https://greatwayfinancial.com/invite/abc123';

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.invite-link.update'), [
                'gw_associate_id' => 'GW-2001',
                'gw_invite_link' => $inviteLink,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'gw-invite-link-saved');

        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'gw_associate_id' => 'GW-2001',
            'gw_invite_link' => $inviteLink,
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('GW Details')
            ->assertSee('GW Associate ID', false)
            ->assertSee('GW-2001', false)
            ->assertSee($inviteLink, false)
            ->assertSee('Share Greatway invite link', false)
            ->assertSee('Share invite link', false)
            ->assertSee('Copy link', false)
            ->assertSee('Open in email', false);
    }

    public function test_greatway_invite_link_must_be_a_valid_url(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.invite-link.update'), [
                'gw_invite_link' => 'not-a-valid-url',
            ]);

        $response
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('gw_invite_link')
            ->assertSessionHas('gw_details_feedback', fn (array $feedback) => $feedback['type'] === 'error')
            ->assertSessionMissing('profile_feedback');

        $this->assertDatabaseMissing('profiles', [
            'user_id' => $user->id,
            'gw_invite_link' => 'not-a-valid-url',
        ]);
    }

    public function test_greatway_invite_link_must_be_unique_across_members(): void
    {
        $inviteLink = 'https://greatwayfinancialgroup.com/invite?GW123456';

        $existingUser = User::factory()->create();
        Profile::query()->create([
            'user_id' => $existingUser->id,
            'gw_invite_link' => $inviteLink,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.invite-link.update'), [
                'gw_invite_link' => $inviteLink,
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('gw_invite_link')
            ->assertSessionHas('gw_details_feedback', fn (array $feedback) => $feedback['type'] === 'error');

        $this->assertDatabaseMissing('profiles', [
            'user_id' => $user->id,
            'gw_invite_link' => $inviteLink,
        ]);
    }

    public function test_profile_update_rejects_duplicate_greatway_invite_link(): void
    {
        $inviteLink = 'https://greatwayfinancialgroup.com/invite?GW123456';

        $existingUser = User::factory()->create();
        Profile::query()->create([
            'user_id' => $existingUser->id,
            'gw_invite_link' => $inviteLink,
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit', ['tab' => 'profile']))
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'gw_invite_link' => $inviteLink,
                'bio' => 'Hello I am great',
            ])
            ->assertRedirect(route('profile.edit', ['tab' => 'profile']))
            ->assertSessionHasErrors('gw_invite_link')
            ->assertSessionHas('profile_feedback', fn (array $feedback) => $feedback['type'] === 'error');

        $this->assertDatabaseMissing('profiles', [
            'user_id' => $user->id,
            'gw_invite_link' => $inviteLink,
        ]);
    }

    public function test_member_can_keep_their_own_greatway_invite_link(): void
    {
        $inviteLink = 'https://greatwayfinancialgroup.com/invite?GW123456';
        $user = User::factory()->create();
        Profile::query()->create([
            'user_id' => $user->id,
            'gw_invite_link' => $inviteLink,
        ]);

        $this->actingAs($user)
            ->patch(route('profile.invite-link.update'), [
                'gw_invite_link' => $inviteLink,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertSame($inviteLink, $user->fresh()->profile->gw_invite_link);
    }

    public function test_member_can_clear_greatway_invite_link(): void
    {
        $user = User::factory()->create();
        Profile::query()->create([
            'user_id' => $user->id,
            'gw_invite_link' => 'https://greatwayfinancial.com/invite/old',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('profile.invite-link.update'), [
                'gw_invite_link' => '',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $this->assertNull($user->fresh()->profile->gw_invite_link);
    }

    public function test_saving_profile_details_does_not_clear_gw_invite_link(): void
    {
        $this->seed([
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
        ]);

        $user = User::factory()->create();
        Profile::query()->create(array_merge([
            'user_id' => $user->id,
            'gw_invite_link' => 'https://greatwayfinancial.com/invite/persist-me',
            'gw_associate_id' => 'GW-KEEP',
        ], LocationOptions::profileLocationIds('Canada', 'Ontario', 'Canada Eastern Time')));

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => '555-9999',
                'city' => 'Toronto',
                'country_id' => LocationOptions::resolveCountryId('Canada'),
                'state_province_id' => LocationOptions::resolveStateProvinceId('Canada', 'Ontario'),
                'timezone_id' => LocationOptions::resolveTimezoneId('Canada Eastern Time'),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit', ['tab' => 'profile']));

        $user->refresh()->load('profile.stateProvince');

        $this->assertSame('555-9999', $user->profile->phone);
        $this->assertSame('Ontario', $user->profile->province);
        $this->assertSame('https://greatwayfinancial.com/invite/persist-me', $user->profile->gw_invite_link);
        $this->assertSame('GW-KEEP', $user->profile->gw_associate_id);
    }

    public function test_member_can_create_invitation_link_from_profile(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $response = $this
            ->actingAs($user)
            ->post('/profile/invitations', [
                'email' => 'invitee@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit', ['open_invitations' => 1]));

        $this->assertDatabaseHas('registration_invitations', [
            'sponsor_id' => $user->id,
            'email' => 'invitee@example.com',
            'role_name' => 'member',
            'max_uses' => 1,
            'uses_count' => 0,
        ]);

        $this->assertNotNull(session('invitation_url'));
    }

    public function test_member_cannot_create_duplicate_active_invitation_for_same_email(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        RegistrationInvitation::factory()->for($user, 'sponsor')->create([
            'email' => 'invitee@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/profile/invitations', [
                'email' => 'invitee@example.com',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_accepted_invitations_appear_in_invitation_log_modal(): void
    {
        $user = User::factory()->create();
        $acceptedUser = User::factory()->create(['email' => 'accepted@example.com', 'name' => 'Accepted Member']);
        $activeInvitation = RegistrationInvitation::factory()->for($user, 'sponsor')->create([
            'email' => 'active@example.com',
        ]);

        RegistrationInvitation::factory()->for($user, 'sponsor')->create([
            'email' => 'accepted@example.com',
            'accepted_by' => $acceptedUser->id,
            'accepted_at' => now(),
            'revoked_at' => now(),
            'uses_count' => 1,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response
            ->assertOk()
            ->assertSee('My Invites')
            ->assertSee('View Invitation Log')
            ->assertSee('Invitation Link Log')
            ->assertSee($activeInvitation->code)
            ->assertSee('active@example.com')
            ->assertSee('accepted@example.com')
            ->assertSee('Registered')
            ->assertSee('Accepted Member');
    }

    public function test_profile_opens_invitation_log_when_requested(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get('/profile?open_invitations=1')
            ->assertOk()
            ->assertSee('Invitation Link Log', false);
    }

    public function test_member_can_delete_active_invitation_and_reinvite_same_email(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $invitation = RegistrationInvitation::factory()->for($user, 'sponsor')->create([
            'email' => 'invitee@example.com',
        ]);

        $deleteResponse = $this
            ->actingAs($user)
            ->delete(route('profile.invitations.destroy', $invitation));

        $deleteResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit', ['open_invitations' => 1]));

        $this->assertNotNull($invitation->refresh()->revoked_at);

        $createResponse = $this
            ->actingAs($user)
            ->post('/profile/invitations', [
                'email' => 'invitee@example.com',
            ]);

        $createResponse
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit', ['open_invitations' => 1]));

        $this->assertSame(2, RegistrationInvitation::where('email', 'invitee@example.com')->count());
    }

    public function test_member_cannot_invite_an_email_that_already_registered(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this
            ->actingAs($user)
            ->post('/profile/invitations', [
                'email' => 'existing@example.com',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_member_can_send_invitation_email_after_previewing_message(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $invitation = RegistrationInvitation::factory()->for($user, 'sponsor')->create([
            'email' => 'invitee@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('profile.invitations.send', $invitation), [
                'recipient_email' => 'invitee@example.com',
                'subject' => 'Join GWTrack',
                'message' => "Please register here:\n".$invitation->invitationUrl(),
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit', ['open_invitations' => 1]));

        Mail::assertSent(InvitationLinkMail::class, function (InvitationLinkMail $mail) use ($user) {
            return $mail->senderName === $user->name
                && $mail->senderEmail === $user->email;
        });

        $this->assertNotNull($invitation->refresh()->last_emailed_at);
    }

    public function test_member_can_send_invitation_email_with_html_message_body(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $invitation = RegistrationInvitation::factory()->for($user, 'sponsor')->create([
            'email' => 'invitee@example.com',
        ]);

        $url = $invitation->invitationUrl();
        $htmlMessage = '<p>Please register here:</p><p><a href="'.$url.'">'.$url.'</a></p>';

        $response = $this
            ->actingAs($user)
            ->post(route('profile.invitations.send', $invitation), [
                'recipient_email' => 'invitee@example.com',
                'subject' => 'Join GWTrack',
                'message' => $htmlMessage,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit', ['open_invitations' => 1]));

        Mail::assertSent(InvitationLinkMail::class, fn (InvitationLinkMail $mail): bool => str_contains($mail->emailBody, $url));
    }

    public function test_member_cannot_mail_invitation_to_email_with_active_invitation(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $invitation = RegistrationInvitation::factory()->for($user, 'sponsor')->create([
            'email' => null,
        ]);
        RegistrationInvitation::factory()->for($user, 'sponsor')->create([
            'email' => 'invitee@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('profile.invitations.send', $invitation), [
                'recipient_email' => 'invitee@example.com',
                'subject' => 'Join GWTrack',
                'message' => "Please register here:\n".$invitation->invitationUrl(),
            ]);

        $response->assertSessionHasErrors('recipient_email');

        Mail::assertNothingSent();
    }

    public function test_invitation_email_message_must_include_registration_link(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $invitation = RegistrationInvitation::factory()->for($user, 'sponsor')->create();

        $response = $this
            ->actingAs($user)
            ->post(route('profile.invitations.send', $invitation), [
                'recipient_email' => 'invitee@example.com',
                'subject' => 'Join GWTrack',
                'message' => 'Please register soon.',
            ]);

        $response->assertSessionHasErrors('message');

        Mail::assertNothingSent();
    }

    public function test_member_cannot_delete_their_own_account_from_profile(): void
    {
        $user = User::factory()->create();
        $user->profile()->create([
            'gw_associate_id' => 'GW-DELETE-1',
        ]);
        $invitation = RegistrationInvitation::factory()->for($user, 'sponsor')->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response->assertMethodNotAllowed();

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh());
        $this->assertNull(User::withTrashed()->find($user->id)?->deleted_at);
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('registration_invitations', [
            'id' => $invitation->id,
            'deleted_at' => null,
        ]);
    }

    public function test_profile_delete_route_is_not_available_even_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response->assertMethodNotAllowed();

        $this->assertNotNull($user->fresh());
    }
}
