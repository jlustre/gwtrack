<?php

namespace Tests\Feature;

use App\Livewire\Activities\DailyActivitiesPage;
use App\Livewire\Goals\ActivityScorecard;
use App\Models\GoalActivityLog;
use App\Models\Profile;
use App\Models\User;
use App\Services\Activities\DailyActivityService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DailyActivitiesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_my_daily_activities_page(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');
        Profile::query()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('daily-activities.index'))
            ->assertOk()
            ->assertSeeLivewire(DailyActivitiesPage::class)
            ->assertSee('My Daily Activities', false)
            ->assertSee('Leads / Prospects', false)
            ->assertSee('Production', false)
            ->assertSee('Initial phone calls', false)
            ->assertSee('Actual vs Goal', false)
            ->assertDontSee('wire:click="increment(\'new_prospects\')"', false)
            ->assertDontSee('FNA started', false);
    }

    public function test_sidebar_includes_my_daily_activities_link(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');
        Profile::query()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My Daily Activities', false);
    }

    public function test_member_can_increment_and_decrement_manual_activities(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');
        Profile::query()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(DailyActivitiesPage::class)
            ->call('increment', 'contacts')
            ->call('increment', 'contacts')
            ->call('decrement', 'contacts')
            ->assertSet('statusMessage', 'Activity updated for today.');

        $this->assertSame(1.0, (float) GoalActivityLog::query()
            ->where('user_id', $user->id)
            ->where('activity_key', 'contacts')
            ->where('source', 'manual')
            ->sum('value'));

        $service = app(DailyActivityService::class);
        $actual = $service->dashboardFor($user, DailyActivityService::PERIOD_DAILY);

        $contacts = collect($actual['manual'])->firstWhere('key', 'contacts');
        $this->assertNotNull($contacts);
        $this->assertSame(1.0, (float) $contacts['actual']);
        $this->assertSame(1.0, (float) $contacts['today_manual']);
    }

    public function test_decrement_does_not_go_below_zero_for_today_tally(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');
        Profile::query()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(DailyActivitiesPage::class)
            ->call('decrement', 'presentations');

        $this->assertSame(0, GoalActivityLog::query()->where('user_id', $user->id)->count());
    }

    public function test_fna_started_is_available_only_when_licensed(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');
        Profile::query()->create([
            'user_id' => $user->id,
            'license_number' => 'LIC-12345',
        ]);

        Livewire::actingAs($user)
            ->test(DailyActivitiesPage::class)
            ->assertSee('FNA started', false)
            ->call('increment', 'fna_started')
            ->assertSet('statusMessage', 'Activity updated for today.');

        $this->assertDatabaseHas('goal_activity_logs', [
            'user_id' => $user->id,
            'activity_key' => 'fna_started',
            'source' => 'manual',
            'value' => 1,
        ]);
    }

    public function test_goals_scorecard_uses_shared_daily_activity_metrics(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');
        Profile::query()->create(['user_id' => $user->id]);

        app(DailyActivityService::class)->increment($user, 'followups_completed');

        Livewire::actingAs($user)
            ->test(ActivityScorecard::class)
            ->assertSee('Follow-ups', false)
            ->assertSee('Log today’s activities', false)
            ->assertSee('Actual vs Goal', false)
            ->assertDontSee('Applications', false);
    }
}
