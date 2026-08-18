<?php

namespace Tests\Feature;

use App\Models\Rank;
use App\Models\User;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CfmManagementAccessTest extends TestCase
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
            ->assertSee('Certified Field Mentor Management', false);
    }

    public function test_executive_director_can_open_cfm_management_page(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $ed = User::factory()->create([
            'rank_id' => Rank::where('code', 'ED')->value('id'),
        ]);
        $ed->assignRole('member');

        $this->actingAs($ed)
            ->get(route('team.cfms'))
            ->assertOk();
    }

    public function test_regular_member_cannot_open_cfm_management_page(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($member)
            ->get(route('team.cfms'))
            ->assertForbidden();
    }

    public function test_cfm_cannot_open_cfm_management_page(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $this->actingAs($cfm)
            ->get(route('team.cfms'))
            ->assertForbidden();
    }
}
