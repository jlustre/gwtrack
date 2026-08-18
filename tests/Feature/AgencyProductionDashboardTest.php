<?php

namespace Tests\Feature;

use App\Livewire\AgencyProductionDashboard;
use App\Models\MemberProductionEntry;
use App\Models\User;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AgencyProductionDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(RankSeeder::class);
    }

    public function test_member_can_view_personal_production_dashboard(): void
    {
        $member = User::factory()->create();
        $member->assignRole('associate');

        MemberProductionEntry::query()->create([
            'user_id' => $member->id,
            'source' => 'manual',
            'description' => 'Whole life policy',
            'annual_premium' => 12000,
            'status' => 'posted',
            'posted_at' => now()->toDateString(),
        ]);

        $this->actingAs($member)
            ->get(route('team.production'))
            ->assertOk()
            ->assertSee('Production Dashboard', false)
            ->assertSee('$12,000', false);

        Livewire::actingAs($member)
            ->test(AgencyProductionDashboard::class)
            ->assertSet('period', 'ytd')
            ->assertSee('Whole life policy', false);
    }

    public function test_team_leader_sees_agency_rollups_and_top_producers(): void
    {
        $leader = User::factory()->create();
        $leader->assignRole('team-leader');

        $member = User::factory()->create(['sponsor_id' => $leader->id]);
        $member->assignRole('associate');

        DB::table('user_hierarchy_paths')->insert([
            ['ancestor_id' => $leader->id, 'descendant_id' => $leader->id, 'depth' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['ancestor_id' => $leader->id, 'descendant_id' => $member->id, 'depth' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        MemberProductionEntry::query()->create([
            'user_id' => $leader->id,
            'source' => 'manual',
            'description' => 'Leader policy',
            'annual_premium' => 5000,
            'status' => 'posted',
            'posted_at' => now()->toDateString(),
        ]);

        MemberProductionEntry::query()->create([
            'user_id' => $member->id,
            'source' => 'manual',
            'description' => 'Member policy',
            'annual_premium' => 15000,
            'status' => 'posted',
            'posted_at' => now()->toDateString(),
        ]);

        $this->actingAs($leader)
            ->get(route('team.production'))
            ->assertOk()
            ->assertSee('Team production', false)
            ->assertSee('Top producers', false)
            ->assertSee('$20,000', false)
            ->assertSee('$15,000', false);
    }

    public function test_leader_can_drill_into_member_production(): void
    {
        $leader = User::factory()->create();
        $leader->assignRole('team-leader');

        $member = User::factory()->create(['sponsor_id' => $leader->id, 'name' => 'Jordan Producer']);
        $member->assignRole('associate');

        DB::table('user_hierarchy_paths')->insert([
            ['ancestor_id' => $leader->id, 'descendant_id' => $leader->id, 'depth' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['ancestor_id' => $leader->id, 'descendant_id' => $member->id, 'depth' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        MemberProductionEntry::query()->create([
            'user_id' => $member->id,
            'source' => 'manual',
            'description' => 'Indexed universal life',
            'annual_premium' => 9000,
            'status' => 'posted',
            'posted_at' => now()->toDateString(),
        ]);

        Livewire::actingAs($leader)
            ->test(AgencyProductionDashboard::class)
            ->set('member', $member->id)
            ->assertSee('Jordan Producer', false)
            ->assertSee('Indexed universal life', false)
            ->assertSee('$9,000', false);
    }

    public function test_leader_cannot_view_out_of_scope_member(): void
    {
        $leader = User::factory()->create();
        $leader->assignRole('team-leader');

        $otherMember = User::factory()->create();
        $otherMember->assignRole('associate');

        DB::table('user_hierarchy_paths')->insert([
            ['ancestor_id' => $leader->id, 'descendant_id' => $leader->id, 'depth' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Livewire::actingAs($leader)
            ->test(AgencyProductionDashboard::class)
            ->set('member', $otherMember->id)
            ->assertForbidden();
    }
}
