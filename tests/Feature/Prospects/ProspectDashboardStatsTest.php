<?php

namespace Tests\Feature\Prospects;

use App\Models\User;
use App\Services\Prospects\ProspectAnalyticsService;
use Database\Seeders\ProspectDashboardTestSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProspectDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_stats_match_analytics_service(): void
    {
        $this->seed(ProspectDashboardTestSeeder::class);

        $user = User::where('email', 'prospects@gwtrack.com')->firstOrFail();
        $expected = app(ProspectAnalyticsService::class)->dashboardStatsFor($user);

        $this->assertGreaterThan(0, $expected['total']);
        $this->assertGreaterThan(0, $expected['hot']);

        $this->actingAs($user)
            ->get(route('team.prospects'))
            ->assertOk()
            ->assertSee('My Prospects', false)
            ->assertSee((string) $expected['total'], false)
            ->assertSee((string) $expected['hot'], false)
            ->assertSee($expected['conversion_rate'].'%', false);
    }

    public function test_tracker_stat_card_renders_zero_value(): void
    {
        $view = $this->blade(
            '<x-tracker-stat-card label="Test Metric" :value="0" theme="gold" subtitle="Example" />'
        );

        $view->assertSee('Test Metric', false);
        $view->assertSee('0', false);
    }
}
