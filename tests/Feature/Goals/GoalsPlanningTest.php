<?php

namespace Tests\Feature\Goals;

use App\Livewire\Goals\PerformancePlannerWizard;
use App\Models\User;
use App\Services\Goals\GoalFunnelEngine;
use App\Services\Goals\GoalPlanningService;
use App\Services\Goals\GoalWhatIfService;
use Database\Seeders\GoalCategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GoalsPlanningTest extends TestCase
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

    public function test_income_funnel_reverse_engineers_activity_stages(): void
    {
        $user = User::factory()->create();

        $funnel = app(GoalFunnelEngine::class)->buildFunnel($user, 'income', 100000);

        $this->assertNotEmpty($funnel);
        $this->assertSame('annual_income', $funnel[0]['key']);
        $this->assertGreaterThan(0, $funnel[0]['annual_target']);
        $this->assertTrue(collect($funnel)->contains(fn (array $stage) => $stage['key'] === 'daily_contacts'));
    }

    public function test_recruiting_funnel_calculates_monthly_targets(): void
    {
        $user = User::factory()->create();

        $funnel = app(GoalFunnelEngine::class)->buildFunnel($user, 'recruiting', 24);
        $monthly = collect($funnel)->firstWhere('key', 'monthly_recruits');

        $this->assertNotNull($monthly);
        $this->assertEquals(2.0, $monthly['annual_target']);
    }

    public function test_planning_service_creates_blueprint_with_linked_goals(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $blueprint = app(GoalPlanningService::class)->createBlueprint($user, 'income', 100000, [
            'name' => '2026 Income Plan',
            'deadline_at' => now()->endOfYear()->toDateString(),
        ]);

        $this->assertDatabaseHas('goal_blueprints', ['id' => $blueprint->id, 'user_id' => $user->id]);
        $this->assertGreaterThan(3, $blueprint->goals()->count());
        $this->assertGreaterThan(0, \App\Models\GoalDependency::query()->count());
    }

    public function test_what_if_simulation_returns_funnel_summary(): void
    {
        $user = User::factory()->create();

        $results = app(GoalWhatIfService::class)->simulate($user, [
            'planning_type' => 'income',
            'target_value' => 200000,
        ]);

        $this->assertArrayHasKey('funnel', $results);
        $this->assertArrayHasKey('summary', $results);
        $this->assertGreaterThan(0, $results['summary']['annual_production'] ?? 0);
    }

    public function test_planner_calculate_funnel_advances_to_preview_step(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(PerformancePlannerWizard::class)
            ->set('targetValue', '100000')
            ->call('calculateFunnel')
            ->assertSet('step', 2)
            ->assertNotSet('previewFunnel', []);
    }

    public function test_performance_planner_page_loads(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('goals.plan'))
            ->assertRedirect(route('goals.index', ['tab' => 'plan']));

        $this->actingAs($user)
            ->get(route('goals.index', ['tab' => 'plan']))
            ->assertOk()
            ->assertSee('Performance Planner', false)
            ->assertSee('Build your Success Blueprint', false);
    }
}
