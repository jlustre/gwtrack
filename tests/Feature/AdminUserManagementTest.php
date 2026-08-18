<?php

namespace Tests\Feature;

use App\Models\Rank;
use App\Models\RegistrationInvitation;
use App\Models\Team;
use App\Models\User;
use App\Support\LocationOptions;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateProvinceSeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function locationPayload(): array
    {
        return LocationOptions::profileLocationIds('Canada', 'Ontario', 'Canada Eastern Time');
    }

    /**
     * @return array<string, mixed>
     */
    private function profilePayload(): array
    {
        return [
            'phone' => '416-555-0100',
            'city' => 'Toronto',
            'best_contact_time' => 'Morning (8am – 12pm)',
            'bio' => 'Focused on recruiting and mentorship.',
            'license_number' => 'LIC-12345',
            'gw_associate_id' => 'GW-ADMIN-001',
            'gw_invite_link' => 'https://greatwayfinancial.com/invite/admin-test',
            'is_gw_active_associate' => '1',
            'recruited_at' => '2026-01-15',
            'is_licensed_life_insurance' => '1',
            'insurance_licenses' => [LocationOptions::jurisdictionKey('Canada', 'Ontario')],
            ...$this->locationPayload(),
        ];
    }

    public function test_super_admin_can_create_user_with_role_rank_team_status_and_sponsor(): void
    {
        $this->seed([RankSeeder::class, RolePermissionSeeder::class]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $sponsor = User::factory()->create();
        $team = Team::create(['name' => 'Alpha Team']);
        $rank = Rank::where('code', 'FA')->firstOrFail();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'New Managed User',
                'email' => 'managed@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => 'member',
                'rank_id' => $rank->id,
                'team_id' => $team->id,
                'sponsor_id' => $sponsor->id,
                'is_active' => '1',
                'joined_at' => '2026-05-31',
            ]);

        $managedUser = User::where('email', 'managed@example.com')->firstOrFail();

        $response->assertRedirect(route('admin.users.edit', $managedUser));
        $this->assertTrue($managedUser->hasRole('member'));
        $this->assertSame($rank->id, $managedUser->rank_id);
        $this->assertSame($team->id, $managedUser->team_id);
        $this->assertSame($sponsor->id, $managedUser->sponsor_id);
        $this->assertTrue($managedUser->is_active);
        $this->assertSame('2026-05-31', $managedUser->joined_at->format('Y-m-d'));
    }

    public function test_agency_owner_can_update_user_role_rank_team_status_and_sponsor(): void
    {
        $this->seed([RankSeeder::class, RolePermissionSeeder::class]);

        $agencyOwner = User::factory()->create();
        $agencyOwner->assignRole('agency-owner');

        $managedUser = User::factory()->create();
        $managedUser->assignRole('member');

        $sponsor = User::factory()->create();
        $team = Team::create(['name' => 'Beta Team']);
        $rank = Rank::where('code', 'SFA')->firstOrFail();

        $response = $this
            ->actingAs($agencyOwner)
            ->patch(route('admin.users.update', $managedUser), [
                'name' => 'Updated Managed User',
                'email' => 'updated@example.com',
                'password' => '',
                'password_confirmation' => '',
                'role' => 'team-leader',
                'rank_id' => $rank->id,
                'team_id' => $team->id,
                'sponsor_id' => $sponsor->id,
                'is_active' => '0',
                'joined_at' => '2026-05-30',
            ]);

        $response->assertRedirect(route('admin.users.edit', $managedUser));

        $managedUser->refresh();

        $this->assertSame('Updated Managed User', $managedUser->name);
        $this->assertSame('updated@example.com', $managedUser->email);
        $this->assertTrue($managedUser->hasRole('team-leader'));
        $this->assertSame($rank->id, $managedUser->rank_id);
        $this->assertSame($team->id, $managedUser->team_id);
        $this->assertSame($sponsor->id, $managedUser->sponsor_id);
        $this->assertFalse($managedUser->is_active);
        $this->assertSame('2026-05-30', $managedUser->joined_at->format('Y-m-d'));
    }

    public function test_admin_can_soft_delete_and_restore_user(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $managedUser = User::factory()->create();
        $managedUser->profile()->create(['gw_associate_id' => 'SOFT-DELETE-1']);
        $invitation = RegistrationInvitation::factory()->for($managedUser, 'sponsor')->create();

        $this
            ->actingAs($admin)
            ->delete(route('admin.users.destroy', $managedUser))
            ->assertRedirect(route('admin.users.index', ['trashed' => 'with']));

        $this->assertSoftDeleted('users', ['id' => $managedUser->id]);
        $this->assertSoftDeleted('profiles', ['user_id' => $managedUser->id]);
        $this->assertSoftDeleted('registration_invitations', ['id' => $invitation->id]);

        $this
            ->actingAs($admin)
            ->patch(route('admin.users.restore', $managedUser->id))
            ->assertRedirect(route('admin.users.edit', $managedUser));

        $this->assertNotSoftDeleted('users', ['id' => $managedUser->id]);
    }

    public function test_admin_cannot_self_deactivate_or_self_delete(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this
            ->actingAs($admin)
            ->patch(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => 'super-admin',
                'rank_id' => null,
                'team_id' => null,
                'sponsor_id' => null,
                'is_active' => '0',
                'joined_at' => now()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('is_active');

        $this
            ->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertSessionHasErrors('user');

        $this->assertNotSoftDeleted('users', ['id' => $admin->id]);
    }

    public function test_admin_cannot_self_demote_out_of_user_management(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this
            ->actingAs($admin)
            ->patch(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => 'member',
                'rank_id' => null,
                'team_id' => null,
                'sponsor_id' => null,
                'is_active' => '1',
                'joined_at' => now()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('role');

        $this->assertTrue($admin->fresh()->hasRole('super-admin'));
    }

    public function test_cannot_demote_the_only_super_admin(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $agencyOwner = User::factory()->create();
        $agencyOwner->assignRole('agency-owner');

        $this
            ->actingAs($agencyOwner)
            ->patch(route('admin.users.update', $admin), [
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => 'agency-owner',
                'rank_id' => null,
                'team_id' => null,
                'sponsor_id' => null,
                'is_active' => '1',
                'joined_at' => now()->format('Y-m-d'),
            ])
            ->assertSessionHasErrors('role');

        $this->assertTrue($admin->fresh()->hasRole('super-admin'));
    }

    public function test_team_leader_cannot_access_user_management_crud(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $teamLeader = User::factory()->create();
        $teamLeader->assignRole('team-leader');

        $this
            ->actingAs($teamLeader)
            ->get(route('admin.users.create'))
            ->assertForbidden();
    }

    public function test_admin_edit_form_includes_profile_sections(): void
    {
        $this->seed([RankSeeder::class, RolePermissionSeeder::class, CountrySeeder::class, StateProvinceSeeder::class, TimezoneSeeder::class]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $managedUser = User::factory()->create();
        $managedUser->assignRole('member');
        $managedUser->profile()->create([
            'phone' => '905-555-0100',
            'city' => 'Hamilton',
            ...$this->locationPayload(),
        ]);

        $this
            ->actingAs($admin)
            ->get(route('admin.users.edit', $managedUser))
            ->assertOk()
            ->assertSee('Contact & Location', false)
            ->assertSee('GW Details', false)
            ->assertSee('Insurance Licenses', false)
            ->assertSee('CFM / Mentor', false)
            ->assertSee('905-555-0100', false)
            ->assertSee('Hamilton', false);
    }

    public function test_super_admin_can_create_user_with_profile_fields(): void
    {
        $this->seed([RankSeeder::class, RolePermissionSeeder::class, CountrySeeder::class, StateProvinceSeeder::class, TimezoneSeeder::class]);

        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $mentor = User::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Profile Managed User',
                'email' => 'profile-managed@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => 'member',
                'rank_id' => null,
                'team_id' => null,
                'sponsor_id' => null,
                'mentor_id' => $mentor->id,
                'is_active' => '1',
                'joined_at' => '2026-05-31',
                ...$this->profilePayload(),
            ]);

        $managedUser = User::where('email', 'profile-managed@example.com')->firstOrFail();
        $profile = $managedUser->profile;

        $response->assertRedirect(route('admin.users.edit', $managedUser));
        $this->assertSame($mentor->id, $managedUser->mentor_id);
        $this->assertNotNull($profile);
        $this->assertSame('416-555-0100', $profile->phone);
        $this->assertSame('Toronto', $profile->city);
        $this->assertSame('Morning (8am – 12pm)', $profile->best_contact_time);
        $this->assertSame('Focused on recruiting and mentorship.', $profile->bio);
        $this->assertSame('LIC-12345', $profile->license_number);
        $this->assertSame('GW-ADMIN-001', $profile->gw_associate_id);
        $this->assertSame('https://greatwayfinancial.com/invite/admin-test', $profile->gw_invite_link);
        $this->assertTrue($profile->is_gw_active_associate);
        $this->assertSame('2026-01-15', $profile->recruited_at->format('Y-m-d'));
        $this->assertTrue($profile->is_licensed_life_insurance);
        $this->assertSame([LocationOptions::jurisdictionKey('Canada', 'Ontario')], $profile->insurance_licenses);
        $this->assertSame($this->locationPayload()['country_id'], $profile->country_id);
        $this->assertSame($this->locationPayload()['state_province_id'], $profile->state_province_id);
        $this->assertSame($this->locationPayload()['timezone_id'], $profile->timezone_id);
    }

    public function test_agency_owner_can_update_user_profile_fields(): void
    {
        $this->seed([RankSeeder::class, RolePermissionSeeder::class, CountrySeeder::class, StateProvinceSeeder::class, TimezoneSeeder::class]);

        $agencyOwner = User::factory()->create();
        $agencyOwner->assignRole('agency-owner');

        $managedUser = User::factory()->create();
        $managedUser->assignRole('member');
        $managedUser->profile()->create(['gw_associate_id' => 'GW-OLD']);

        $mentor = User::factory()->create();

        $response = $this
            ->actingAs($agencyOwner)
            ->patch(route('admin.users.update', $managedUser), [
                'name' => $managedUser->name,
                'email' => $managedUser->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => 'member',
                'rank_id' => null,
                'team_id' => null,
                'sponsor_id' => null,
                'mentor_id' => $mentor->id,
                'is_active' => '1',
                'joined_at' => now()->format('Y-m-d'),
                ...$this->profilePayload(),
            ]);

        $response->assertRedirect(route('admin.users.edit', $managedUser));

        $managedUser->refresh();
        $profile = $managedUser->profile;

        $this->assertSame($mentor->id, $managedUser->mentor_id);
        $this->assertSame('416-555-0100', $profile->phone);
        $this->assertSame('Toronto', $profile->city);
        $this->assertSame('GW-ADMIN-001', $profile->gw_associate_id);
        $this->assertTrue($profile->is_licensed_life_insurance);
        $this->assertSame([LocationOptions::jurisdictionKey('Canada', 'Ontario')], $profile->insurance_licenses);
    }
}
