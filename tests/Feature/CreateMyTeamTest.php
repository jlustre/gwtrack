<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\CreateMyTeamModal;
use App\Models\Team;
use App\Models\User;
use App\Services\DownlineHierarchyService;
use App\Services\Teams\CreateMyTeamService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CreateMyTeamTest extends TestCase
{
    use RefreshDatabase;

    private User $leader;

    private User $recruit;

    private User $grandchild;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        $this->leader = User::factory()->create();
        $this->leader->assignRole('member');

        $this->recruit = User::factory()->create([
            'sponsor_id' => $this->leader->id,
        ]);
        $this->recruit->assignRole('member');

        $this->grandchild = User::factory()->create([
            'sponsor_id' => $this->recruit->id,
        ]);
        $this->grandchild->assignRole('member');

        app(DownlineHierarchyService::class)->rebuild();
    }

    public function test_dashboard_shows_create_my_team_action(): void
    {
        $this->actingAs($this->leader)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Create My Team', false);
    }

    public function test_user_can_create_team_and_move_hierarchy(): void
    {
        Livewire::actingAs($this->leader)
            ->test(CreateMyTeamModal::class)
            ->call('open')
            ->set('name', 'Leader Growth Team')
            ->set('description', 'Field builders')
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect(route('team.index'));

        $team = Team::query()->where('name', 'Leader Growth Team')->first();

        $this->assertNotNull($team);
        $this->assertSame($this->leader->id, $team->leader_id);
        $this->assertTrue($team->is_active);

        $this->assertSame($team->id, $this->leader->fresh()->team_id);
        $this->assertSame($team->id, $this->recruit->fresh()->team_id);
        $this->assertSame($team->id, $this->grandchild->fresh()->team_id);
    }

    public function test_cannot_create_second_active_team_as_leader(): void
    {
        app(CreateMyTeamService::class)->create($this->leader, 'First Team');

        $this->assertFalse(app(CreateMyTeamService::class)->userMayCreate($this->leader->fresh()));

        Livewire::actingAs($this->leader)
            ->test(CreateMyTeamModal::class)
            ->call('open')
            ->assertForbidden();

        $this->actingAs($this->leader)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Name your team and move your recruit hierarchy onto it.', false);
    }
}
