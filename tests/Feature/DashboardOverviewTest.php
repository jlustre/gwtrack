<?php

namespace Tests\Feature;

use App\Models\Rank;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use App\Support\LocationOptions;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\ProfileCompletionFieldSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateProvinceSeeder;
use Database\Seeders\TeamSeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardOverviewTest extends TestCase
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
        ]);
    }

    public function test_authenticated_member_sees_dashboard_sections(): void
    {
        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'FA')->value('id');
        $user = $this->createUser('member.journey@example.com', 'Journey Member', 'member', $teamId, $rankId);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Welcome Message', false)
            ->assertSee('Performance Statistics', false)
            ->assertSee('Onboarding Progress', false)
            ->assertSee('Goal Progress', false)
            ->assertSee('Licensing Progress', false)
            ->assertSee('FAP Progress', false)
            ->assertSee('Training Progress', false)
            ->assertSee('Team Profile Completion', false)
            ->assertSee('My Progress', false);
    }

    public function test_seeded_onboarding_steps_appear_in_licensing_progress_section(): void
    {
        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'FA')->value('id');
        $user = $this->createUser('onboarding.hub@example.com', 'Onboarding Hub Member', 'member', $teamId, $rankId);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Onboarding Progress', false)
            ->assertSee('Licensing Progress', false)
            ->assertSee('Notifications', false)
            ->assertSee('Progress Trackers', false);
    }

    public function test_dashboard_still_renders_team_stat_cards_with_view_buttons(): void
    {
        $teamId = (int) DB::table('teams')->value('id');
        $rankId = Rank::where('code', 'FA')->value('id');
        $user = $this->createUser('viewer@example.com', 'Viewer User', 'member', $teamId, $rankId);

        app(DownlineHierarchyService::class)->rebuild();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Team Profile Completion')
            ->assertSee('Team Onboarding')
            ->assertSee('Team Licensing')
            ->assertSee('Team FAP')
            ->assertSee('Team CFM Training')
            ->assertSee('My Profile Completion')
            ->assertSee('View', false)
            ->assertDontSee('Next Rank Requirements', false)
            ->assertDontSee('Weekly leadership call reminder', false);
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
}
