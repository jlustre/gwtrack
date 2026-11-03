<?php

namespace Tests\Feature;

use App\Livewire\Admin\RankAdministrationManager;
use App\Models\Rank;
use App\Models\RankRequirement;
use App\Models\User;
use App\Models\UserRankProgress;
use App\Services\RankAdvancementService;
use Database\Seeders\RankRequirementSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RankAdministrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(RankSeeder::class);
        $this->seed(RankRequirementSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    public function test_admin_can_create_and_edit_rank_ladder_settings(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RankAdministrationManager::class)
            ->call('createRank')
            ->set('rankForm.code', 'TEST')
            ->set('rankForm.name', 'Test Builder')
            ->set('rankForm.track', 'builder')
            ->set('rankForm.sort_order', 60000)
            ->set('rankForm.total_fyo_rate', '135.50')
            ->set('rankForm.is_field_leader', true)
            ->call('saveRank')
            ->assertHasNoErrors()
            ->assertSet('showRankForm', false);

        $rank = Rank::query()->where('code', 'TEST')->firstOrFail();
        $this->assertSame('Test Builder', $rank->name);
        $this->assertSame('135.50', $rank->total_fyo_rate);
        $this->assertTrue($rank->is_field_leader);

        Livewire::actingAs($this->admin)
            ->test(RankAdministrationManager::class)
            ->call('editRank', $rank->id)
            ->set('rankForm.name', 'Updated Builder')
            ->set('rankForm.sort_order', 60001)
            ->call('saveRank')
            ->assertHasNoErrors();

        $this->assertSame('Updated Builder', $rank->fresh()->name);
        $this->assertSame(60001, $rank->fresh()->sort_order);

        Livewire::actingAs($this->admin)
            ->test(RankAdministrationManager::class)
            ->call('editRank', $rank->id)
            ->set('rankForm.code', 'RENAMED')
            ->call('saveRank')
            ->assertStatus(422);

        $this->assertSame('TEST', $rank->fresh()->code);
    }

    public function test_admin_can_manage_requirements_without_erasing_member_progress(): void
    {
        $rank = Rank::query()->where('code', 'ASC')->firstOrFail();
        $member = User::factory()->create(['rank_id' => Rank::query()->where('code', 'TA')->value('id')]);

        Livewire::actingAs($this->admin)
            ->test(RankAdministrationManager::class)
            ->call('selectRank', $rank->id)
            ->set('requirementForm.title', 'Complete leadership orientation')
            ->set('requirementForm.description', 'Attend the required orientation session.')
            ->set('requirementForm.category', 'leadership')
            ->set('requirementForm.sort_order', 900)
            ->call('saveRequirement')
            ->assertHasNoErrors();

        $requirement = RankRequirement::query()
            ->where('rank_id', $rank->id)
            ->where('title', 'Complete leadership orientation')
            ->firstOrFail();

        app(RankAdvancementService::class)->ensureProgressRecords($member);
        $progress = UserRankProgress::query()
            ->where('user_id', $member->id)
            ->where('rank_requirement_id', $requirement->id)
            ->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(RankAdministrationManager::class)
            ->call('selectRank', $rank->id)
            ->call('toggleRequirementActive', $requirement->id);

        $this->assertFalse($requirement->fresh()->is_active);
        $this->assertDatabaseHas('user_rank_progress', ['id' => $progress->id]);
        $this->assertDatabaseHas('rank_requirements', [
            'id' => $requirement->id,
            'is_active' => false,
        ]);

        $tracker = app(RankAdvancementService::class)->trackerFor($member, $member);
        $visibleTitles = collect($tracker['requirement_groups'])
            ->flatMap(fn (array $group) => collect($group['items'])->pluck('title'));
        $this->assertFalse($visibleTitles->contains('Complete leadership orientation'));
    }

    public function test_admin_can_reorder_ranks_without_duplicate_sort_values(): void
    {
        $rank = Rank::query()->where('code', 'SA')->firstOrFail();
        $previousRank = Rank::query()
            ->where('sort_order', '<', $rank->sort_order)
            ->orderByDesc('sort_order')
            ->firstOrFail();
        $rankOrder = $rank->sort_order;
        $previousOrder = $previousRank->sort_order;

        Livewire::actingAs($this->admin)
            ->test(RankAdministrationManager::class)
            ->call('moveRank', $rank->id, 'up');

        $this->assertSame($previousOrder, $rank->fresh()->sort_order);
        $this->assertSame($rankOrder, $previousRank->fresh()->sort_order);
    }

    public function test_entry_rank_cannot_be_deactivated(): void
    {
        $entryRank = Rank::query()->where('code', config('greatway-compensation.entry_rank'))->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(RankAdministrationManager::class)
            ->call('toggleRankActive', $entryRank->id)
            ->assertStatus(422);

        $this->assertTrue($entryRank->fresh()->is_active);
    }

    public function test_user_management_cannot_assign_a_retired_rank(): void
    {
        $retiredRank = Rank::query()->where('code', 'SA')->firstOrFail();
        $retiredRank->update(['is_active' => false]);
        $member = User::factory()->create();
        $member->assignRole('member');

        $this->actingAs($this->admin)
            ->patch(route('admin.users.update', $member), [
                'name' => $member->name,
                'email' => $member->email,
                'password' => '',
                'password_confirmation' => '',
                'role' => 'member',
                'rank_id' => $retiredRank->id,
                'team_id' => '',
                'sponsor_id' => '',
                'mentor_id' => '',
                'is_active' => '1',
                'joined_at' => '',
            ])
            ->assertSessionHasErrors('rank_id');
    }

    public function test_rank_seeder_does_not_overwrite_admin_configuration(): void
    {
        $rank = Rank::query()->where('code', 'ASC')->firstOrFail();
        $rank->update([
            'name' => 'Administrator Edited Associate',
            'total_fyo_rate' => 123.45,
        ]);
        $requirement = RankRequirement::query()->where('rank_id', $rank->id)->firstOrFail();
        $requirement->update(['title' => 'Administrator Edited Requirement']);

        $this->seed(RankSeeder::class);
        $this->seed(RankRequirementSeeder::class);

        $this->assertSame('Administrator Edited Associate', $rank->fresh()->name);
        $this->assertSame('123.45', $rank->fresh()->total_fyo_rate);
        $this->assertSame('Administrator Edited Requirement', $requirement->fresh()->title);
    }

    public function test_rank_administration_requires_manage_ranks_permission(): void
    {
        $member = User::factory()->create();
        $member->assignRole('member');

        Livewire::actingAs($member)
            ->test(RankAdministrationManager::class)
            ->assertForbidden();
    }
}
