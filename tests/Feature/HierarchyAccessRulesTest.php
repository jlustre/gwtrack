<?php

namespace Tests\Feature;

use App\Models\MentorAssignment;
use App\Models\Team;
use App\Models\TeamVisibilityPermission;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class HierarchyAccessRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_visible_member_queries_match_direct_and_full_downline_member_checks(): void
    {
        $viewer = User::factory()->create();
        $direct = User::factory()->create(['sponsor_id' => $viewer->id]);
        $deep = User::factory()->create(['sponsor_id' => $direct->id]);
        $outsider = User::factory()->create();
        $this->setRoleWithPermissions($viewer, 'direct-hierarchy-viewer', ['view direct downline']);
        $this->addHierarchyPaths($viewer, $direct, $deep, $outsider);

        $hierarchy = app(DownlineHierarchyService::class);
        $directScope = $hierarchy->visibleMembersQuery($viewer)->pluck('users.id')->all();

        $this->assertEqualsCanonicalizing([$viewer->id, $direct->id], $directScope);
        $this->assertTrue($hierarchy->canViewMember($viewer, $direct));
        $this->assertFalse($hierarchy->canViewMember($viewer, $deep));
        $this->assertFalse($hierarchy->canViewMember($viewer, $outsider));

        $this->setRoleWithPermissions($viewer, 'full-hierarchy-viewer', ['view full downline']);
        $fullScope = $hierarchy->visibleMembersQuery($viewer)->pluck('users.id')->all();

        $this->assertEqualsCanonicalizing([$viewer->id, $direct->id, $deep->id], $fullScope);
        $this->assertTrue($hierarchy->canViewMember($viewer, $deep));
        $this->assertFalse($hierarchy->canViewMember($viewer, $outsider));
    }

    public function test_expired_visibility_grants_do_not_grant_profile_or_sensitive_data_access(): void
    {
        $viewer = User::factory()->create();
        $visible = User::factory()->create();
        $expired = User::factory()->create();
        $this->setRoleWithPermissions($viewer, 'grant-viewer', []);

        TeamVisibilityPermission::query()->create([
            'viewer_id' => $viewer->id,
            'visible_user_id' => $visible->id,
            'visibility_level' => 'profile',
            'can_view_sensitive_data' => true,
            'expires_at' => now()->addDay(),
        ]);
        TeamVisibilityPermission::query()->create([
            'viewer_id' => $viewer->id,
            'visible_user_id' => $expired->id,
            'visibility_level' => 'profile',
            'can_view_sensitive_data' => true,
            'expires_at' => now()->subSecond(),
        ]);

        $hierarchy = app(DownlineHierarchyService::class);
        $ids = $hierarchy->visibleMembersQuery($viewer)->pluck('users.id')->all();

        $this->assertContains($viewer->id, $ids);
        $this->assertContains($visible->id, $ids);
        $this->assertNotContains($expired->id, $ids);
        $dashboardIds = $hierarchy->dashboardMembersQuery($viewer)->pluck('users.id')->all();
        $this->assertContains($visible->id, $dashboardIds);
        $this->assertNotContains($expired->id, $dashboardIds);
        $this->assertTrue($viewer->can('viewSensitive', $visible));
        $this->assertFalse($viewer->can('viewSensitive', $expired));
    }

    public function test_active_mentorship_and_agency_owned_team_are_scoped_visibility_exceptions(): void
    {
        $mentor = User::factory()->create();
        $apprentice = User::factory()->create();
        $this->setRoleWithPermissions($mentor, 'mentor-scope', []);
        MentorAssignment::query()->create([
            'mentor_id' => $mentor->id,
            'apprentice_id' => $apprentice->id,
            'status' => 'active',
        ]);

        $hierarchy = app(DownlineHierarchyService::class);
        $this->assertTrue($hierarchy->canViewMember($mentor, $apprentice));
        $this->assertContains($apprentice->id, $hierarchy->dashboardMembersQuery($mentor)->pluck('users.id')->all());

        MentorAssignment::query()
            ->where('mentor_id', $mentor->id)
            ->where('apprentice_id', $apprentice->id)
            ->update(['status' => 'completed']);

        $this->assertFalse($hierarchy->canViewMember($mentor, $apprentice));
        $this->assertNotContains($apprentice->id, $hierarchy->dashboardMembersQuery($mentor)->pluck('users.id')->all());

        $agencyOwner = User::factory()->create();
        $agencyMember = User::factory()->create();
        $team = Team::query()->create([
            'name' => 'Owned agency team',
            'owner_id' => $agencyOwner->id,
            'is_active' => true,
        ]);
        $agencyMember->update(['team_id' => $team->id]);
        $this->setRoleWithPermissions($agencyOwner, 'agency-owner', ['view own team']);

        $this->assertTrue($hierarchy->canViewMember($agencyOwner, $agencyMember));
        $this->assertContains($agencyMember->id, $hierarchy->dashboardMembersQuery($agencyOwner)->pluck('users.id')->all());

        $otherViewer = User::factory()->create();
        $this->setRoleWithPermissions($otherViewer, 'non-owner-team-viewer', ['view own team']);
        $this->assertFalse($hierarchy->canViewMember($otherViewer, $agencyMember));
    }

    public function test_unverified_users_cannot_access_hierarchy_routes(): void
    {
        $viewer = User::factory()->create(['email_verified_at' => null]);
        $this->setRoleWithPermissions($viewer, 'unverified-team-viewer', ['view own team']);

        $this->actingAs($viewer)
            ->get(route('team.index'))
            ->assertRedirect(route('verification.notice'));
    }

    private function setRoleWithPermissions(User $user, string $roleName, array $permissions): void
    {
        $role = Role::findOrCreate($roleName, 'web');
        $role->syncPermissions($permissions);
        $user->syncRoles($role);
    }

    private function addHierarchyPaths(User $viewer, User $direct, User $deep, User $outsider): void
    {
        $paths = [
            [$viewer->id, $viewer->id, 0],
            [$viewer->id, $direct->id, 1],
            [$viewer->id, $deep->id, 2],
            [$direct->id, $direct->id, 0],
            [$direct->id, $deep->id, 1],
            [$deep->id, $deep->id, 0],
            [$outsider->id, $outsider->id, 0],
        ];

        foreach ($paths as [$ancestorId, $descendantId, $depth]) {
            DB::table('user_hierarchy_paths')->insert([
                'ancestor_id' => $ancestorId,
                'descendant_id' => $descendantId,
                'depth' => $depth,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
