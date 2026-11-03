<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\DownlineHierarchyService;
use App\Support\MemberDisplayName;
use Database\Seeders\DownlineManagementSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DownlineManagementModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_downline_dashboard_member_search_filters_visible_team_members(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            DownlineManagementSeeder::class,
        ]);

        $owner = User::where('email', 'downline-owner@gwtracks.com')->firstOrFail();
        $member = User::where('sponsor_id', $owner->id)->firstOrFail();

        $this->actingAs($owner)
            ->get(route('team.index', ['search' => $member->name]))
            ->assertOk()
            ->assertSee($member->name)
            ->assertSee('matching members', false);

        $this->actingAs($owner)
            ->get(route('team.index', ['search' => 'no-such-member-xyz']))
            ->assertOk()
            ->assertSee('No members match your search or filters', false);
    }

    public function test_downline_pages_render_for_visible_team_members(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            DownlineManagementSeeder::class,
        ]);

        $owner = User::where('email', 'downline-owner@gwtracks.com')->firstOrFail();
        $member = User::where('sponsor_id', $owner->id)->firstOrFail();

        $this->actingAs($owner)
            ->get(route('team.index'))
            ->assertOk()
            ->assertSee('gw-page-loading', false)
            ->assertSee('gw-go-to-top', false)
            ->assertSee('gw-go-to-top__icon', false)
            ->assertSee('Team Command Center')
            ->assertSee('Rank Distribution')
            ->assertSee('Visible Team Members', false)
            ->assertSee('Search name, email, phone', false)
            ->assertSee('Reset Filters', false)
            ->assertSee('View Profile', false);

        $this->actingAs($owner)
            ->get(route('team.member.profile', $member))
            ->assertOk()
            ->assertSee('Team Member Profile', false)
            ->assertSee('Profile Details', false)
            ->assertSee('Onboarding', false)
            ->assertSee($member->name, false)
            ->assertDontSee('Generate Invite Link', false)
            ->assertDontSee('Save Profile', false);

        $this->actingAs($owner)
            ->get(route('team.tree'))
            ->assertOk()
            ->assertSee('Sponsor Tree')
            ->assertSee('genealogyTreePan', false)
            ->assertSee('searchUrl', false)
            ->assertSee('memberSearch', false)
            ->assertSee('Search your hierarchy (min. 3 characters)', false)
            ->assertDontSee('Zoom Out', false)
            ->assertDontSee('Zoom In', false)
            ->assertSee('Zoom in', false)
            ->assertSee('Zoom out', false)
            ->assertSee($member->name);

        $this->actingAs($owner)
            ->get(route('team.hierarchy'))
            ->assertOk()
            ->assertSee('Sponsor Hierarchy', false)
            ->assertSee('downlineHierarchyTable', false)
            ->assertSee('memberSearch', false)
            ->assertSee('searchMembers', false)
            ->assertSee('Search your full hierarchy', false)
            ->assertDontSee('Apply Filters', false)
            ->assertDontSee('All Ranks', false)
            ->assertSee('Expand All', false)
            ->assertSee('>Team<', false)
            ->assertSee('total_downline', false)
            ->assertSee('profileModalOpen', false)
            ->assertSee('View Full Member Profile', false)
            ->assertSee('production_formatted', false)
            ->assertSee('>Production<', false)
            ->assertSee($owner->name, false)
            ->assertSee($member->name, false);

        $directLeader = User::where('sponsor_id', $owner->id)->firstOrFail();

        $siblingLeader = User::where('sponsor_id', $owner->id)
            ->where('id', '!=', $directLeader->id)
            ->first();

        $this->actingAs($owner)
            ->get(route('team.member.hierarchy', $directLeader))
            ->assertOk()
            ->assertSee('Branch rooted at', false)
            ->assertSee($directLeader->name, false)
            ->assertSee('Make this member the topmost', false)
            ->assertSee('Make direct upline the topmost', false)
            ->assertSee(route('team.hierarchy'), false);

        if ($siblingLeader) {
            $this->actingAs($owner)
                ->get(route('team.member.hierarchy', $directLeader))
                ->assertSee(MemberDisplayName::for($siblingLeader), false);
        }

        $this->actingAs($owner)
            ->get(route('team.org-chart'))
            ->assertOk()
            ->assertSee('Executive Team Structure')
            ->assertSee('Expand All', false)
            ->assertSee('Collapse All', false)
            ->assertSee('Clear filters', false)
            ->assertSee('Search by name, email, rank, role, country', false)
            ->assertSee('orgChartBoard', false)
            ->assertSee('View Profile', false)
            ->assertSee('profileModalOpen', false)
            ->assertSee($member->name, false);

        $this->actingAs($owner)
            ->get(route('team.downlines', ['search' => $member->name]))
            ->assertOk()
            ->assertSee('All Downlines')
            ->assertSee($member->name);

        $this->actingAs($owner)
            ->get(route('team.member', $member))
            ->assertOk()
            ->assertSee('Member Profile')
            ->assertSee($member->name);
    }

    public function test_hierarchy_arrow_visibility(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            DownlineManagementSeeder::class,
        ]);

        $owner = User::where('email', 'downline-owner@gwtracks.com')->firstOrFail();
        $directLeader = User::where('sponsor_id', $owner->id)->firstOrFail();
        $deeperMember = User::where('sponsor_id', $directLeader->id)->firstOrFail();

        $ownRows = collect($this->actingAs($owner)->get(route('team.hierarchy'))->assertOk()->viewData('rows'))->keyBy('id');

        $this->assertNull($ownRows[$owner->id]['upline_hierarchy_url']);
        $this->assertNull($ownRows[$directLeader->id]['upline_hierarchy_url']);
        $this->assertSame(route('team.member.hierarchy', $directLeader), $ownRows[$directLeader->id]['make_top_url']);
        $this->assertNull($ownRows[$deeperMember->id]['upline_hierarchy_url']);
        $this->assertSame(route('team.member.hierarchy', $deeperMember), $ownRows[$deeperMember->id]['make_top_url']);

        $branchRows = collect($this->actingAs($owner)
            ->get(route('team.member.hierarchy', $directLeader))
            ->assertOk()
            ->viewData('rows'))->keyBy('id');

        $this->assertSame(route('team.hierarchy'), $branchRows[$directLeader->id]['upline_hierarchy_url']);
        $this->assertNull($branchRows[$directLeader->id]['make_top_url']);
        $this->assertNull($branchRows[$deeperMember->id]['upline_hierarchy_url']);

        $nestedBranchRows = collect($this->actingAs($owner)
            ->get(route('team.member.hierarchy', $deeperMember))
            ->assertOk()
            ->viewData('rows'))->keyBy('id');

        $this->assertSame(
            route('team.member.hierarchy', $directLeader),
            $nestedBranchRows[$deeperMember->id]['upline_hierarchy_url']
        );
        $this->assertNull($nestedBranchRows[$deeperMember->id]['make_top_url']);
    }

    public function test_hierarchy_down_arrow_returns_to_logged_in_user_when_they_are_direct_upline(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            TaskScenarioSeeder::class,
        ]);

        app(DownlineHierarchyService::class)->rebuild();

        $arielle = User::where('email', 'agency-owner@gwtracks.com')->firstOrFail();
        $aaron = User::where('email', 'aaron.us@example.com')->firstOrFail();

        $this->assertSame($arielle->id, $aaron->sponsor_id);

        $branchRows = collect($this->actingAs($arielle)
            ->get(route('team.member.hierarchy', $aaron))
            ->assertOk()
            ->viewData('rows'))->keyBy('id');

        $this->assertSame(route('team.hierarchy'), $branchRows[$aaron->id]['upline_hierarchy_url']);

        $ownRows = collect($this->actingAs($arielle)
            ->get(route('team.hierarchy'))
            ->assertOk()
            ->viewData('rows'))->keyBy('id');

        $this->assertNull($ownRows[$arielle->id]['upline_hierarchy_url']);
        $this->assertTrue($ownRows->has($aaron->id));
    }

    public function test_hierarchy_member_summary_includes_production_metric(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            DownlineManagementSeeder::class,
        ]);

        $owner = User::where('email', 'downline-owner@gwtracks.com')->firstOrFail();

        $rows = collect($this->actingAs($owner)->get(route('team.hierarchy'))->assertOk()->viewData('rows'));

        $ownerRow = $rows->firstWhere('id', $owner->id);

        $this->assertIsArray($ownerRow['profile']['metrics']);
        $this->assertArrayHasKey('production', $ownerRow['profile']['metrics']);
        $this->assertArrayHasKey('production_formatted', $ownerRow['profile']['metrics']);
        $this->assertSame(
            '$'.number_format($ownerRow['profile']['metrics']['production']),
            $ownerRow['profile']['metrics']['production_formatted']
        );
    }

    public function test_genealogy_tree_search_members_can_become_tree_root(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            DownlineManagementSeeder::class,
        ]);

        $owner = User::where('email', 'downline-owner@gwtracks.com')->firstOrFail();
        $directLeader = User::where('sponsor_id', $owner->id)->firstOrFail();

        $ownerSearchTerm = strtok($owner->name, ' ') ?: substr($owner->name, 0, 3);
        $leaderSearchTerm = strtok($directLeader->name, ' ') ?: substr($directLeader->name, 0, 3);

        $ownerMatch = collect(
            $this->actingAs($owner)
                ->getJson(route('team.tree.search', ['q' => $ownerSearchTerm]))
                ->assertOk()
                ->json('members')
        )->firstWhere('id', $owner->id);

        $leaderMatch = collect(
            $this->actingAs($owner)
                ->getJson(route('team.tree.search', ['q' => $leaderSearchTerm]))
                ->assertOk()
                ->json('members')
        )->firstWhere('id', $directLeader->id);

        $this->assertNotNull($ownerMatch);
        $this->assertNotNull($leaderMatch);

        $this->assertSame(route('team.tree'), $ownerMatch['tree_top_url']);
        $this->assertSame(route('team.member.tree', $directLeader), $leaderMatch['tree_top_url']);

        $this->actingAs($owner)
            ->get(route('team.member.tree', $directLeader))
            ->assertOk()
            ->assertSee($directLeader->name)
            ->assertSee(MemberDisplayName::for($directLeader), false);
    }

    public function test_genealogy_tree_branch_endpoint_loads_visible_direct_recruits_and_protects_other_branches(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            DownlineManagementSeeder::class,
        ]);

        $owner = User::where('email', 'downline-owner@gwtracks.com')->firstOrFail();
        $directLeader = User::where('sponsor_id', $owner->id)->firstOrFail();
        $deeperMember = User::where('sponsor_id', $directLeader->id)->firstOrFail();
        $owner->forceFill(['email_verified_at' => now()])->save();

        $response = $this->actingAs($owner)
            ->getJson(route('team.tree.branch', $directLeader));
        $this->assertSame(200, $response->status(), $response->getContent());
        $response->assertJsonStructure(['html']);

        $this->assertStringContainsString($deeperMember->name, $response->json('html'));
        $this->assertStringContainsString('Expand branch', $response->json('html'));

        $outsider = User::factory()->create(['name' => 'Outside Branch']);
        $outsider->forceFill(['email_verified_at' => now()])->save();
        $outsider->givePermissionTo('view team tree');

        $this->actingAs($outsider)
            ->getJson(route('team.tree.branch', $directLeader))
            ->assertForbidden();
    }

    public function test_genealogy_tree_branch_endpoint_does_not_expose_members_beyond_viewer_depth(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            DownlineManagementSeeder::class,
        ]);

        $owner = User::where('email', 'downline-owner@gwtracks.com')->firstOrFail();
        $directLeader = User::where('sponsor_id', $owner->id)->firstOrFail();
        $deeperMember = User::where('sponsor_id', $directLeader->id)->firstOrFail();
        $hiddenMember = User::factory()->create([
            'name' => 'Beyond Direct Visibility',
            'sponsor_id' => $deeperMember->id,
        ]);
        $directLeader->forceFill(['email_verified_at' => now()])->save();
        $directViewerRole = Role::findOrCreate('direct-tree-viewer', 'web');
        $directViewerRole->syncPermissions(['view team tree', 'view direct downline']);
        $directLeader->syncRoles($directViewerRole);
        app(DownlineHierarchyService::class)->rebuild();

        $response = $this->actingAs($directLeader)
            ->getJson(route('team.tree.branch', $directLeader))
            ->assertOk();

        $this->assertStringContainsString($deeperMember->name, $response->json('html'));
        $this->assertStringNotContainsString($hiddenMember->name, $response->json('html'));
    }

    public function test_team_dashboard_and_member_metrics_respect_direct_downline_visibility(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            DownlineManagementSeeder::class,
        ]);

        $owner = User::where('email', 'downline-owner@gwtracks.com')->firstOrFail();
        $viewer = User::factory()->create([
            'name' => 'Direct Visibility Viewer',
            'sponsor_id' => $owner->id,
            'email_verified_at' => now(),
        ]);
        $directViewerRole = Role::findOrCreate('direct-viewer', 'web');
        $directViewerRole->syncPermissions(['view own team', 'view team tree', 'view team table', 'view direct downline']);
        $viewer->syncRoles($directViewerRole);
        $visibleMember = User::factory()->create([
            'name' => 'Visible Direct Member',
            'sponsor_id' => $viewer->id,
        ]);
        $hiddenMember = User::factory()->create([
            'name' => 'Outside Viewer Depth',
            'sponsor_id' => $visibleMember->id,
        ]);
        app(DownlineHierarchyService::class)->rebuild();

        $response = $this->actingAs($viewer)
            ->get(route('team.index'))
            ->assertOk();

        $stats = $response->viewData('stats');
        $hierarchy = app(DownlineHierarchyService::class);
        $visibleDirectCount = $hierarchy->directRecruitsQuery($viewer)
            ->whereIn('users.id', $hierarchy->visibleMembersQuery($viewer)->select('users.id'))
            ->count();
        $this->assertSame(
            $visibleDirectCount,
            $stats['total_team'],
            json_encode(['visible_direct_count' => $visibleDirectCount, 'stats_total' => $stats['total_team']])
        );
        $this->assertSame(
            $stats['total_team'],
            array_sum($response->viewData('rankDistribution'))
        );

        $metrics = $hierarchy->visibleMemberMetrics($viewer, $visibleMember);
        $this->assertSame(0, $metrics['direct_recruits']);
        $this->assertSame(0, $metrics['total_downline']);
        $this->assertFalse($hierarchy->visibleMembersQuery($viewer)->whereKey($hiddenMember->id)->exists());
    }

    public function test_unrelated_member_cannot_view_another_branch_member(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            DownlineManagementSeeder::class,
        ]);

        $target = User::where('email', 'avery.stone@gwtracks.com')->firstOrFail();
        $outsider = User::factory()->create(['name' => 'Outside Member']);
        $outsider->assignRole('member');

        app(DownlineHierarchyService::class)->rebuild();

        $this->actingAs($outsider)
            ->get(route('team.member', $target))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->get(route('team.member.profile', $target))
            ->assertForbidden();
    }

    public function test_tree_upline_button_stops_at_logged_in_user_boundary(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            DownlineManagementSeeder::class,
        ]);

        $owner = User::where('email', 'downline-owner@gwtracks.com')->firstOrFail();
        $directLeader = User::where('sponsor_id', $owner->id)->firstOrFail();
        $deeperMember = User::where('sponsor_id', $directLeader->id)->firstOrFail();

        $this->actingAs($owner)
            ->get(route('team.member.tree', $directLeader))
            ->assertOk()
            ->assertSee($deeperMember->name)
            ->assertDontSee('Go up one upline')
            ->assertSee('Show direct upline')
            ->assertSee('href="'.route('team.member.tree', $owner).'"', false);

        $this->actingAs($owner)
            ->get(route('team.tree'))
            ->assertOk()
            ->assertDontSee('Show direct upline')
            ->assertDontSee('Go up one upline')
            ->assertSee('Make this member the top card');
    }

    public function test_my_directs_page_lists_only_direct_recruits_with_search_filter_and_pagination(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            DownlineManagementSeeder::class,
        ]);

        $owner = User::where('email', 'downline-owner@gwtracks.com')->firstOrFail();
        $owner->forceFill(['email_verified_at' => now()])->save();
        $direct = User::where('sponsor_id', $owner->id)->firstOrFail();
        $indirect = User::where('sponsor_id', $direct->id)->firstOrFail();

        $this->actingAs($owner)
            ->get(route('team.directs'))
            ->assertOk()
            ->assertSee('My Directs', false)
            ->assertSee('Search &amp; Filter', false)
            ->assertSee('Total Directs', false)
            ->assertSee('View profile details', false)
            ->assertSee($direct->name, false)
            ->assertDontSee($indirect->name, false);

        $this->actingAs($owner)
            ->get(route('team.directs', ['search' => $direct->name]))
            ->assertOk()
            ->assertSee($direct->name, false);

        $this->actingAs($owner)
            ->get(route('team.directs', ['search' => 'no-such-direct-xyz']))
            ->assertOk()
            ->assertSee('No direct members found', false);

        $this->actingAs($owner)
            ->get(route('team.directs'))
            ->assertOk()
            ->assertSee(route('team.member.profile', $direct), false);
    }

    public function test_all_downlines_page_shows_stat_cards(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            DownlineManagementSeeder::class,
        ]);

        $owner = User::where('email', 'downline-owner@gwtracks.com')->firstOrFail();
        $owner->forceFill(['email_verified_at' => now()])->save();
        $direct = User::where('sponsor_id', $owner->id)->firstOrFail();

        $this->actingAs($owner)
            ->get(route('team.downlines'))
            ->assertOk()
            ->assertSee('All Downlines', false)
            ->assertSee('Total Downlines', false)
            ->assertSee('Active', false)
            ->assertSee('Licensed', false)
            ->assertSee('CFM Assigned', false)
            ->assertSee('New This Month', false)
            ->assertSee('Building Teams', false)
            ->assertSee('Assign CFM', false)
            ->assertSee('Submit Assignments', false)
            ->assertSee($direct->name, false);
    }

    public function test_downline_export_requires_export_permission(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            DownlineManagementSeeder::class,
        ]);

        $owner = User::where('email', 'downline-owner@gwtracks.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('team.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $member = User::where('email', 'genealogy.leaf.dana.01@gwtracks.com')->firstOrFail();

        $this->actingAs($member)
            ->get(route('team.export'))
            ->assertForbidden();
    }
}
