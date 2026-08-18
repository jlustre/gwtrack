<?php

namespace Tests\Feature\Goals;

use App\Livewire\Goals\TeamGoalsPanel;
use App\Models\Goal;
use App\Models\GoalCategory;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use Database\Seeders\GoalCategorySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeamGoalsTest extends TestCase
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

    public function test_team_leader_can_view_team_goals_page(): void
    {
        $leader = User::factory()->create();
        $leader->assignRole('team-leader');

        $this->actingAs($leader)
            ->get(route('goals.team'))
            ->assertRedirect(route('goals.index', ['tab' => 'team']));

        $this->actingAs($leader)
            ->get(route('goals.index', ['tab' => 'team']))
            ->assertOk()
            ->assertSee('Team Goals', false)
            ->assertSee('Team goal visibility', false)
            ->assertSee('Members with goals', false);
    }

    public function test_user_without_team_goals_permission_cannot_view_team_goals_page(): void
    {
        $recruit = User::factory()->create();
        $recruit->assignRole('new-recruit');

        $recruit->syncRoles(['new-recruit']);

        $this->actingAs($recruit)
            ->get(route('goals.team'))
            ->assertForbidden();

        $this->actingAs($recruit)
            ->get(route('goals.index', ['tab' => 'team']))
            ->assertForbidden();
    }

    public function test_team_panel_lists_downline_member_goals(): void
    {
        $leader = User::factory()->create();
        $leader->assignRole('team-leader');

        $trainee = User::factory()->create(['sponsor_id' => $leader->id]);
        $categoryId = GoalCategory::query()->where('slug', 'recruiting')->value('id');

        Goal::query()->create([
            'user_id' => $trainee->id,
            'goal_category_id' => $categoryId,
            'hierarchy_level' => 'monthly',
            'name' => 'Recruit two associates',
            'measurement_type' => 'number',
            'target_value' => 2,
            'actual_value' => 1,
            'status' => 'active',
            'starts_at' => now()->startOfMonth(),
            'deadline_at' => now()->endOfMonth(),
        ]);

        app(DownlineHierarchyService::class)->rebuild();

        Livewire::actingAs($leader)
            ->test(TeamGoalsPanel::class)
            ->assertSee('Recruit two associates', false)
            ->assertSee($trainee->name, false)
            ->call('setViewMode', 'members')
            ->assertSee('View all goals', false);
    }

    public function test_team_panel_filters_by_direct_scope(): void
    {
        $leader = User::factory()->create();
        $leader->assignRole('team-leader');

        $direct = User::factory()->create(['sponsor_id' => $leader->id]);
        $indirect = User::factory()->create(['sponsor_id' => $direct->id]);
        $categoryId = GoalCategory::query()->value('id');

        Goal::query()->create([
            'user_id' => $direct->id,
            'goal_category_id' => $categoryId,
            'hierarchy_level' => 'monthly',
            'name' => 'Direct recruit goal',
            'measurement_type' => 'number',
            'target_value' => 1,
            'actual_value' => 0,
            'status' => 'active',
            'starts_at' => now(),
            'deadline_at' => now()->addMonth(),
        ]);

        Goal::query()->create([
            'user_id' => $indirect->id,
            'goal_category_id' => $categoryId,
            'hierarchy_level' => 'monthly',
            'name' => 'Indirect downline goal',
            'measurement_type' => 'number',
            'target_value' => 1,
            'actual_value' => 0,
            'status' => 'active',
            'starts_at' => now(),
            'deadline_at' => now()->addMonth(),
        ]);

        Livewire::actingAs($leader)
            ->test(TeamGoalsPanel::class)
            ->set('scope', 'directs')
            ->assertSee('Direct recruit goal', false)
            ->assertDontSee('Indirect downline goal', false);
    }
}
