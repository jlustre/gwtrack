<?php

namespace Tests\Feature\Goals;

use App\Livewire\Goals\GoalPlanningSettingsPanel;
use App\Models\User;
use App\Services\Goals\GoalFunnelEngine;
use App\Services\Goals\GoalPlanningSettingsService;
use Database\Seeders\GoalCategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GoalsPlanningSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            GoalCategorySeeder::class,
        ]);
    }

    public function test_planning_settings_page_renders_for_member(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('goals.settings'))
            ->assertRedirect(route('goals.index', ['tab' => 'settings']));

        $this->actingAs($user)
            ->get(route('goals.index', ['tab' => 'settings']))
            ->assertOk()
            ->assertSee('Planning assumptions', false)
            ->assertSee('Income commission rate', false)
            ->assertSee('Earn $100,000 at 20% commission', false)
            ->assertSee('Examples', false);
    }

    public function test_custom_commission_rate_changes_income_funnel_production(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        app(GoalPlanningSettingsService::class)->save($user, [
            'income_commission_percent' => 25,
            'avg_annual_premium_per_application' => 2500,
            'working_days_per_month' => 22,
            'working_weeks_per_year' => 48,
            'weeks_per_month' => 4.33,
            'conversion_rates' => [],
        ]);

        $funnel = app(GoalFunnelEngine::class)->buildFunnel($user, 'income', 100000);
        $production = collect($funnel)->firstWhere('key', 'annual_production');

        $this->assertNotNull($production);
        $this->assertEquals(400000.0, $production['annual_target']);
    }

    public function test_settings_panel_saves_and_resets(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(GoalPlanningSettingsPanel::class)
            ->set('incomeCommissionPercent', 30)
            ->set('avgAnnualPremiumPerApplication', 3000)
            ->call('save')
            ->assertHasNoErrors();

        $constants = app(GoalPlanningSettingsService::class)->constantsFor($user);
        $this->assertEquals(0.3, $constants['income_commission_rate']);
        $this->assertEquals(3000.0, $constants['avg_annual_premium_per_application']);

        Livewire::actingAs($user)
            ->test(GoalPlanningSettingsPanel::class)
            ->call('resetDefaults')
            ->assertSet('incomeCommissionPercent', 20.0);

        $constants = app(GoalPlanningSettingsService::class)->constantsFor($user);
        $this->assertEquals(0.2, $constants['income_commission_rate']);
    }
}
