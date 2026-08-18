<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRolePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_super_admin_admin_center(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super-admin');

        $this->actingAs($user)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Super Admin Control Center');
    }

    public function test_agency_owner_can_access_user_management(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('agency-owner');

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('User Management');
    }

    public function test_admin_can_access_admin_dashboard_and_user_management(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Super Admin Control Center');

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('User Management');

        $this->actingAs($user)
            ->get(route('admin.checklists.index'))
            ->assertOk()
            ->assertSee('Checklists');
    }

    public function test_team_leader_gets_team_leader_admin_center_but_cannot_manage_users(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('team-leader');

        $this->actingAs($user)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Team Leader Workspace');

        $this->actingAs($user)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_cfm_and_trainer_get_their_role_specific_admin_centers(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $trainer = User::factory()->create();
        $trainer->assignRole('trainer');

        $this->actingAs($cfm)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Certified Field Mentor Workspace');

        $this->actingAs($trainer)
            ->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Trainer Workspace');
    }

    public function test_member_cannot_access_admin_center(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('admin.index'))
            ->assertForbidden();
    }
}
