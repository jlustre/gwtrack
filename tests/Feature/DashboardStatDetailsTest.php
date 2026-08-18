<?php

namespace Tests\Feature;

use App\Models\Prospect;
use App\Models\Rank;
use App\Models\User;
use App\Services\DashboardStatsService;
use App\Services\DownlineHierarchyService;
use App\Support\LocationOptions;
use Database\Seeders\CountrySeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\ProfileCompletionFieldSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateProvinceSeeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\StartsChecklistTypes;
use Tests\TestCase;

class DashboardStatDetailsTest extends TestCase
{
    use RefreshDatabase;
    use StartsChecklistTypes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
            ProfileCompletionFieldSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);
    }

    public function test_agency_owner_sees_downline_members_in_onboarding_modal_data(): void
    {
        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'FA')->value('id');

        $owner = $this->createUser('agency-owner@example.com', 'Agency Owner', 'agency-owner', $teamId, $rankId);
        $onboardingMember = $this->createUser('onboarding.member@example.com', 'Onboarding Member', 'member', $teamId, $rankId, $owner->id);
        $completeMember = $this->createUser('complete.member@example.com', 'Complete Member', 'member', $teamId, $rankId, $owner->id);

        $this->seedOnboardingProgress($onboardingMember->id, completed: false);
        $this->seedOnboardingProgress($completeMember->id, completed: true);
        $this->startChecklistType($onboardingMember, 'onboarding');
        $this->startChecklistType($completeMember, 'onboarding');
        $this->startChecklistType($owner, 'onboarding');

        app(DownlineHierarchyService::class)->rebuild();

        $response = $this->actingAs($owner)
            ->getJson(route('dashboard.stat-details', ['type' => 'onboarding']))
            ->assertOk()
            ->assertJsonPath('type', 'onboarding')
            ->assertJsonPath('scope', 'full_downline')
            ->assertJsonPath('display', 'progress');

        $names = collect($response->json('members'))->pluck('name')->all();

        $this->assertContains('Onboarding Member', $names);
        $this->assertContains('Complete Member', $names);
        $this->assertContains('Agency Owner', $names);

        $completeRow = collect($response->json('members'))->firstWhere('name', 'Complete Member');
        $this->assertSame(100, $completeRow['percent']);
        $this->assertSame('Complete', $completeRow['status']);
    }

    public function test_agency_owner_does_not_see_members_outside_hierarchy(): void
    {
        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'FA')->value('id');

        $owner = $this->createUser('scoped.owner@example.com', 'Scoped Owner', 'agency-owner', $teamId, $rankId);
        $downlineMember = $this->createUser('scoped.downline@example.com', 'Downline Member', 'member', $teamId, $rankId, $owner->id);
        $outsideMember = $this->createUser('outside.member@example.com', 'Outside Member', 'member', $teamId, $rankId);

        $this->seedOnboardingProgress($downlineMember->id, completed: false);
        $this->seedOnboardingProgress($outsideMember->id, completed: false);

        app(DownlineHierarchyService::class)->rebuild();

        $response = $this->actingAs($owner)
            ->getJson(route('dashboard.stat-details', ['type' => 'onboarding']))
            ->assertOk()
            ->assertJsonPath('scope', 'full_downline');

        $names = collect($response->json('members'))->pluck('name')->all();

        $this->assertContains('Downline Member', $names);
        $this->assertNotContains('Outside Member', $names);
    }

    public function test_team_leader_sees_full_downline_in_onboarding_modal_data(): void
    {
        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'FA')->value('id');

        $leader = $this->createUser('leader@example.com', 'Team Leader', 'team-leader', $teamId, $rankId);
        $onboardingMember = $this->createUser('leader.downline@example.com', 'Leader Downline', 'member', $teamId, $rankId, $leader->id);

        $this->seedOnboardingProgress($onboardingMember->id, completed: false);

        app(DownlineHierarchyService::class)->rebuild();

        $response = $this->actingAs($leader)
            ->getJson(route('dashboard.stat-details', ['type' => 'onboarding']))
            ->assertOk()
            ->assertJsonPath('scope', 'full_downline');

        $names = collect($response->json('members'))->pluck('name')->all();

        $this->assertContains('Leader Downline', $names);
        $this->assertContains('Team Leader', $names);
    }

    public function test_member_without_downline_sees_only_themselves_when_profile_is_incomplete(): void
    {
        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'FA')->value('id');

        $member = $this->createUser('member.only@example.com', 'Solo Member', 'new-recruit', $teamId, $rankId);

        app(DownlineHierarchyService::class)->rebuild();

        $response = $this->actingAs($member)
            ->getJson(route('dashboard.stat-details', ['type' => 'profile']))
            ->assertOk()
            ->assertJsonPath('scope', 'direct_downline');

        $names = collect($response->json('members'))->pluck('name')->all();

        $this->assertSame(['Solo Member'], $names);
    }

    public function test_dashboard_renders_team_stat_cards_with_view_buttons(): void
    {
        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'FA')->value('id');
        $user = $this->createUser('viewer@example.com', 'Viewer User', 'member', $teamId, $rankId);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Team Profile Completion')
            ->assertSee('Team Onboarding')
            ->assertSee('Team Licensing')
            ->assertSee('Team FAP')
            ->assertSee('Team CFM Training')
            ->assertSee('My Progress')
            ->assertSee('My Profile Completion')
            ->assertSee('View Team Profile Completion details', false);
    }

    public function test_dashboard_hides_my_licensing_for_licensed_user(): void
    {
        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'FA')->value('id');
        $user = $this->createUser('licensed.viewer@example.com', 'Licensed Viewer', 'member', $teamId, $rankId);

        $user->profile->update(['license_number' => 'LIC-54321']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Team Licensing')
            ->assertDontSee('My Licensing</h3>', false);
    }

    public function test_dashboard_hides_my_onboarding_when_onboarding_is_complete(): void
    {
        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'FA')->value('id');
        $user = $this->createUser('onboarded.viewer@example.com', 'Onboarded Viewer', 'member', $teamId, $rankId);

        $this->startChecklistType($user, 'onboarding');
        $this->seedOnboardingProgress($user->id, completed: true);

        $service = app(DashboardStatsService::class);
        $user->refresh();

        $this->assertSame(100, $service->onboardingPercent($user));
        $this->assertFalse($service->shouldShowPersonalOnboardingCard($user));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Team Onboarding')
            ->assertDontSee('My Onboarding</h3>', false);
    }

    public function test_licensing_modal_excludes_members_with_license_number(): void
    {
        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'FA')->value('id');

        $owner = $this->createUser('licensing.owner@example.com', 'Licensing Owner', 'agency-owner', $teamId, $rankId);
        $unlicensed = $this->createUser('unlicensed@example.com', 'Unlicensed Member', 'member', $teamId, $rankId, $owner->id);
        $licensed = $this->createUser('licensed@example.com', 'Licensed Member', 'member', $teamId, $rankId, $owner->id);

        $licensed->profile->update(['license_number' => 'LIC-12345']);

        app(DownlineHierarchyService::class)->rebuild();

        $response = $this->actingAs($owner)
            ->getJson(route('dashboard.stat-details', ['type' => 'credentials']))
            ->assertOk()
            ->assertJsonPath('title', 'Team Licensing');

        $names = collect($response->json('members'))->pluck('name')->all();

        $this->assertContains('Unlicensed Member', $names);
        $this->assertContains('Licensed Member', $names);
    }

    public function test_training_modal_excludes_members_who_have_not_started(): void
    {
        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'FA')->value('id');

        $owner = $this->createUser('training.owner@example.com', 'Training Owner', 'agency-owner', $teamId, $rankId);
        $notStarted = $this->createUser('not.started@example.com', 'Not Started Member', 'member', $teamId, $rankId, $owner->id);
        $started = $this->createUser('started@example.com', 'Started Member', 'member', $teamId, $rankId, $owner->id);

        $lessonId = $this->seedPublishedTrainingLesson();

        DB::table('training_progress')->insert([
            'user_id' => $started->id,
            'training_lesson_id' => $lessonId,
            'status' => 'in_progress',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(DownlineHierarchyService::class)->rebuild();

        $response = $this->actingAs($owner)
            ->getJson(route('dashboard.stat-details', ['type' => 'training']))
            ->assertOk()
            ->assertJsonPath('title', 'Team CFM Training');

        $names = collect($response->json('members'))->pluck('name')->all();

        $this->assertContains('Started Member', $names);
        $this->assertContains('Not Started Member', $names);
    }

    public function test_personal_prospects_details_return_list_payload(): void
    {
        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'FA')->value('id');
        $user = $this->createUser('prospects.viewer@example.com', 'Prospects Viewer', 'member', $teamId, $rankId);

        Prospect::query()->create([
            'owner_id' => $user->id,
            'first_name' => 'Jamie',
            'last_name' => 'Lead',
            'status' => 'active',
            'is_archived' => false,
            'interest_level' => 'warm',
        ]);

        $this->actingAs($user)
            ->getJson(route('dashboard.stat-details', ['type' => 'prospects', 'context' => 'personal']))
            ->assertOk()
            ->assertJsonPath('display', 'list')
            ->assertJsonPath('title', 'My Prospects')
            ->assertJsonFragment(['title' => 'Jamie Lead']);
    }

    public function test_personal_activities_details_return_metric_breakdown(): void
    {
        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'FA')->value('id');
        $user = $this->createUser('activities.viewer@example.com', 'Activities Viewer', 'member', $teamId, $rankId);

        $this->actingAs($user)
            ->getJson(route('dashboard.stat-details', ['type' => 'activities', 'context' => 'personal']))
            ->assertOk()
            ->assertJsonPath('display', 'list')
            ->assertJsonPath('title', 'My Activities')
            ->assertJsonFragment(['title' => 'Phone Calls Attempted']);
    }

    public function test_invalid_stat_type_returns_not_found(): void
    {
        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'FA')->value('id');
        $user = $this->createUser('viewer2@example.com', 'Viewer Two', 'member', $teamId, $rankId);

        $this->actingAs($user)
            ->getJson(route('dashboard.stat-details', ['type' => 'invalid']))
            ->assertNotFound();
    }

    private function createUser(string $email, string $name, string $role, int $teamId, ?int $rankId, ?int $sponsorId = null): User
    {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('Password123'),
            'team_id' => $teamId,
            'rank_id' => $rankId,
            'sponsor_id' => $sponsorId,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $user->syncRoles([$role]);

        $user->profile()->create(array_merge(
            LocationOptions::profileLocationIds('Canada'),
            [
                'city' => 'Toronto',
                'gw_associate_id' => 'GW-'.$user->id,
            ]
        ));

        return $user;
    }

    private function seedPublishedTrainingLesson(): int
    {
        $categoryId = DB::table('training_categories')->insertGetId([
            'name' => 'Core',
            'slug' => 'core',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $moduleId = DB::table('training_modules')->insertGetId([
            'training_category_id' => $categoryId,
            'title' => 'Module One',
            'slug' => 'module-one',
            'sort_order' => 1,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('training_lessons')->insertGetId([
            'training_module_id' => $moduleId,
            'title' => 'Lesson One',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedOnboardingProgress(int $userId, bool $completed): void
    {
        $stepIds = DB::table('checklists')
            ->join('checklist_types', 'checklist_types.id', '=', 'checklists.checklist_type_id')
            ->where('checklist_types.code', 'onboarding')
            ->where('checklists.is_active', true)
            ->whereNull('checklists.deleted_at')
            ->pluck('checklists.id');

        foreach ($stepIds as $stepId) {
            DB::table('checklist_progress')->updateOrInsert(
                [
                    'user_id' => $userId,
                    'checklist_id' => $stepId,
                    'mentor_assignment_id' => null,
                ],
                [
                    'status' => $completed ? 'completed' : 'not_started',
                    'completed_at' => $completed ? now() : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
