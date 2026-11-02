<?php

namespace Tests\Feature;

use App\Models\MentorAssignment;
use App\Models\Team;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use Database\Seeders\DownlineManagementSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberProfileAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_agency_owner_can_view_team_member_profile_read_only_and_record_production(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            DownlineManagementSeeder::class,
        ]);

        $owner = User::where('email', 'downline-owner@gwtracks.com')->firstOrFail();
        $member = User::where('sponsor_id', $owner->id)->firstOrFail();

        $owner->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($owner)
            ->get(route('team.member.profile', $member))
            ->assertOk()
            ->assertSee('Team Member Profile', false)
            ->assertSee('Add production entry', false)
            ->assertDontSee('Save Profile', false)
            ->assertDontSee('Generate Invite Link', false);

        $this->actingAs($owner)
            ->post(route('team.member.production.store', $member), [
                'description' => 'Owner-recorded policy',
                'annual_premium' => 2500,
                'posted_at' => now()->toDateString(),
            ])
            ->assertRedirect(route('team.member.profile', ['user' => $member, 'tab' => 'annual-premium']))
            ->assertSessionHas('production_feedback.type', 'success');

        $this->actingAs($owner)
            ->get(route('team.member.profile', ['user' => $member, 'tab' => 'annual-premium']))
            ->assertOk()
            ->assertSee('Owner-recorded policy', false)
            ->assertSee('Manual Entry', false);
    }

    public function test_cfm_can_view_trainee_profile_and_record_production_but_not_unrelated_member(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
        ]);

        $sponsor = User::factory()->create(['name' => 'Trainee Sponsor']);
        $sponsor->assignRole('member');

        $cfm = User::factory()->create(['name' => 'Assigned CFM', 'sponsor_id' => $sponsor->id]);
        $cfm->assignRole('certified-field-mentor');

        $trainee = User::factory()->create(['name' => 'Active Trainee', 'sponsor_id' => $sponsor->id]);
        $trainee->assignRole('member');

        $otherMember = User::factory()->create(['name' => 'Other Downline', 'sponsor_id' => $sponsor->id]);
        $otherMember->assignRole('member');

        MentorAssignment::query()->create([
            'mentor_id' => $cfm->id,
            'apprentice_id' => $trainee->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        app(DownlineHierarchyService::class)->rebuild();

        $this->actingAs($cfm)
            ->get(route('team.member.profile', $trainee))
            ->assertOk()
            ->assertSee('Team Member Profile', false)
            ->assertSee('Active Trainee', false)
            ->assertSee('Add production entry', false)
            ->assertDontSee('Save Profile', false);

        $this->actingAs($cfm)
            ->post(route('team.member.production.store', $trainee), [
                'description' => 'CFM-recorded policy',
                'annual_premium' => 1800,
            ])
            ->assertRedirect(route('team.member.profile', ['user' => $trainee, 'tab' => 'annual-premium']))
            ->assertSessionHas('production_feedback.type', 'success');

        $this->actingAs($cfm)
            ->get(route('team.member.profile', $otherMember))
            ->assertForbidden();

        $this->actingAs($cfm)
            ->post(route('team.member.production.store', $otherMember), [
                'description' => 'Should not save',
                'annual_premium' => 500,
            ])
            ->assertForbidden();
    }

    public function test_agency_owner_can_view_team_member_when_not_in_sponsor_hierarchy(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
        ]);

        $owner = User::factory()->create(['name' => 'Agency Owner']);
        $owner->assignRole('agency-owner');

        $team = Team::query()->create([
            'name' => 'Owner Team',
            'owner_id' => $owner->id,
            'is_active' => true,
        ]);

        $owner->update(['team_id' => $team->id]);

        $member = User::factory()->create([
            'name' => 'Same Team Member',
            'team_id' => $team->id,
            'sponsor_id' => null,
        ]);
        $member->assignRole('member');

        app(DownlineHierarchyService::class)->rebuild();

        $this->actingAs($owner)
            ->get(route('team.member.profile', $member))
            ->assertOk()
            ->assertSee('Same Team Member', false);
    }
}
