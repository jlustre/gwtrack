<?php

namespace Tests\Feature;

use App\Http\Controllers\TaskController;
use App\Livewire\Dashboard\MyTasksModal;
use App\Livewire\Prospects\ProspectQuickLogModal;
use App\Models\PortalResource;
use App\Models\Prospect;
use App\Models\Rank;
use App\Models\User;
use App\Services\DailyQuoteService;
use App\Services\DashboardActivityService;
use App\Services\DashboardHomeService;
use App\Services\ResourceLinksService;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\DailyQuoteSeeder;
use Database\Seeders\ProfileCompletionFieldSeeder;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\ResourceDocumentSeeder;
use Database\Seeders\ResourceLinkSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateProvinceSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardActivityTest extends TestCase
{
    use RefreshDatabase;

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
            DailyQuoteSeeder::class,
        ]);
    }

    public function test_dashboard_renders_requested_daily_activity_panels(): void
    {
        $user = $this->createMember('activity.hub@example.com');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My Tasks', false)
            ->assertSee('Upcoming Meetings', false)
            ->assertSee('Calendar', false)
            ->assertSee('Notifications', false)
            ->assertSee('Recent Messages', false);
    }

    public function test_dashboard_renders_home_sections_from_database(): void
    {
        $user = $this->createMember('home.dashboard@example.com');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Welcome Message', false)
            ->assertSee('Daily Quote', false)
            ->assertSee('Progress Trackers', false)
            ->assertSee('Onboarding Progress', false)
            ->assertSee('Goal Progress', false)
            ->assertSee('Licensing Progress', false)
            ->assertSee('FAP Progress', false)
            ->assertSee('Training Progress', false)
            ->assertSee('Quick Actions', false)
            ->assertSee('Invite New Member', false)
            ->assertSee('open-invite-member-modal', false)
            ->assertSee('Performance Statistics', false);
    }

    public function test_inactive_member_does_not_see_invite_new_member_quick_action(): void
    {
        $user = $this->createMember('inactive.invite@example.com');
        $user->update(['is_active' => false]);

        $home = app(DashboardHomeService::class)->forUser($user->fresh());

        $this->assertFalse(collect($home['quick_actions'])->contains(
            fn (array $action): bool => $action['label'] === 'Invite New Member'
        ));
    }

    public function test_dashboard_renders_quick_links_panel_from_resource_library(): void
    {
        $this->seed([
            ResourceDocumentSeeder::class,
            ResourceLinkSeeder::class,
        ]);

        $user = $this->createMember('quick.links@example.com');

        PortalResource::query()->create([
            'title' => 'Dashboard Featured Zoom Room',
            'description' => 'Daily team huddle room.',
            'type' => 'link',
            'category' => 'zoom',
            'sort_order' => 1,
            'url' => 'https://zoom.us/j/dashboard-featured',
            'file_format' => 'LINK',
            'is_published' => true,
            'is_featured' => true,
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Quick Links', false)
            ->assertSee('Dashboard Featured Zoom Room', false)
            ->assertSee('View all links', false);
    }

    public function test_resource_links_service_returns_dashboard_links_with_featured_priority(): void
    {
        $featured = PortalResource::query()->create([
            'title' => 'Featured Link',
            'type' => 'link',
            'category' => 'team',
            'sort_order' => 2,
            'url' => 'https://zoom.us/j/featured',
            'file_format' => 'LINK',
            'is_published' => true,
            'is_featured' => true,
        ]);

        PortalResource::query()->create([
            'title' => 'Standard Link',
            'type' => 'link',
            'category' => 'general',
            'sort_order' => 1,
            'url' => 'https://example.com/standard',
            'file_format' => 'LINK',
            'is_published' => true,
            'is_featured' => false,
        ]);

        $links = app(ResourceLinksService::class)->dashboardLinks();

        $this->assertTrue($links->first()->is($featured));
    }

    public function test_home_service_includes_quick_links_payload(): void
    {
        PortalResource::query()->create([
            'title' => 'Home Service Link',
            'type' => 'link',
            'category' => 'tools',
            'sort_order' => 1,
            'url' => 'https://example.com/home-service',
            'file_format' => 'LINK',
            'is_published' => true,
            'is_featured' => true,
        ]);

        $user = $this->createMember('home.quick.links@example.com');
        $home = app(DashboardHomeService::class)->forUser($user);

        $this->assertArrayHasKey('quick_links', $home);
        $this->assertTrue($home['quick_links']['links']->contains('title', 'Home Service Link'));
        $this->assertNotNull($home['quick_links']['library_url']);
    }

    public function test_my_tasks_quick_action_opens_prioritized_tasks_modal(): void
    {
        $this->seed([
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
        ]);

        $user = $this->createMember('my.tasks.modal@example.com');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My Tasks', false)
            ->assertSee('open-my-tasks-modal', false);

        Livewire::actingAs($user)
            ->test(MyTasksModal::class)
            ->dispatch('open-my-tasks-modal')
            ->assertSet('show', true)
            ->assertSee('Assigned to me', false);
    }

    public function test_open_tasks_by_priority_returns_highest_priority_first(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
        ]);

        $agencyOwner = User::where('email', 'agency-owner@gwtrack.com')->firstOrFail();

        $payload = app(TaskController::class)->openTasksByPriorityFor($agencyOwner);

        $this->assertNotEmpty($payload['items']);

        $orders = collect($payload['items'])->pluck('priority_order');

        $this->assertSame($orders->sort()->values()->all(), $orders->values()->all());
    }

    public function test_log_activity_quick_action_opens_picker_and_quick_log_modal(): void
    {
        $this->seed([
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = $this->createMember('log.activity@example.com');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Quick',
            'last_name' => 'Log',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Prospect Activity', false);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Dashboard\DashboardLogActivityModal::class)
            ->dispatch('open-dashboard-log-activity-modal')
            ->assertSet('show', true)
            ->call('selectPerson', 'prospect:'.$prospect->id, 'Quick Log', 'Prospect')
            ->assertSet('step', 'detail')
            ->assertSet('prospectId', (string) $prospect->id);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Prospects\ProspectQuickLogModal::class)
            ->dispatch('open-prospect-quick-log-modal', prospectId: (string) $prospect->id, tab: 'activity', activityType: 'phone_call')
            ->assertSet('show', true)
            ->assertSet('prospectName', $prospect->displayName());
    }

    public function test_activity_service_my_tasks_panel_lists_all_incomplete_assigned_tasks(): void
    {
        $this->seed([
            TaskScenarioSeeder::class,
        ]);

        $agencyOwner = User::where('email', 'agency-owner@gwtrack.com')->firstOrFail();

        $panels = app(DashboardActivityService::class)->panelsFor($agencyOwner);
        $myTasks = $panels['my_tasks'];

        $this->assertSame('My Tasks', $myTasks['title']);
        $this->assertGreaterThan(0, $myTasks['count']);
        $this->assertCount($myTasks['count'], $myTasks['items']);
    }

    public function test_activity_service_returns_database_backed_notifications(): void
    {
        $user = $this->createMember('notify.activity@example.com');

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'database',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'Live dashboard alert',
                'message' => 'This came from the database.',
                'category' => 'General',
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $panels = app(DashboardActivityService::class)->panelsFor($user);

        $this->assertSame(1, $panels['notifications']['count']);
        $this->assertSame('Live dashboard alert', $panels['notifications']['items'][0]['title']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Live dashboard alert', false);
    }

    public function test_daily_quote_service_returns_seeded_quote(): void
    {
        $quote = app(DailyQuoteService::class)->forDate();

        $this->assertNotNull($quote);
        $this->assertNotSame('', $quote['quote']);
        $this->assertArrayHasKey('quotes', $quote);
        $this->assertGreaterThan(1, $quote['total']);
        $this->assertCount($quote['total'], $quote['quotes']);
    }

    public function test_daily_quote_rotates_by_calendar_day(): void
    {
        $service = app(DailyQuoteService::class);

        $dayOne = $service->forDate(now()->startOfYear());
        $dayTwo = $service->forDate(now()->startOfYear()->addDay());

        $this->assertNotNull($dayOne);
        $this->assertNotNull($dayTwo);
        $this->assertNotSame($dayOne['quote'], $dayTwo['quote']);

        $this->assertSame(
            $dayOne['quote'],
            $service->forDate(now()->startOfYear())['quote'],
        );
    }

    public function test_dashboard_daily_quote_exposes_prev_next_controls(): void
    {
        $user = $this->createMember('quote.nav@example.com');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Daily Quote', false)
            ->assertSee('Previous quote', false)
            ->assertSee('Next quote', false)
            ->assertSee('x-on:click="prev()"', false)
            ->assertSee('x-on:click="next()"', false);
    }

    public function test_home_service_returns_progress_trackers(): void
    {
        $user = $this->createMember('progress.dashboard@example.com');
        $home = app(DashboardHomeService::class)->forUser($user);

        $this->assertArrayHasKey('welcome', $home);
        $this->assertArrayHasKey('daily_quote', $home);
        $this->assertCount(5, $home['progress']);
        $this->assertSame('Onboarding Progress', $home['progress'][0]['label']);
        $this->assertSame('Training Progress', $home['progress'][3]['label']);
        $this->assertSame('Goal Progress', $home['progress'][4]['label']);
    }

    private function createMember(string $email): User
    {
        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'TA')->value('id');

        $user = User::factory()->create([
            'name' => 'Activity Member',
            'email' => $email,
            'password' => Hash::make('Password123'),
            'team_id' => $teamId,
            'rank_id' => $rankId,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $user->syncRoles(['member']);

        $user->profile()->create([
            'country_id' => DB::table('countries')->where('code', 'CA')->value('id'),
            'state_province_id' => DB::table('state_provinces')->value('id'),
            'timezone_id' => DB::table('timezones')->value('id'),
            'city' => 'Toronto',
            'gw_associate_id' => 'GW-'.$user->id,
        ]);

        return $user;
    }
}
