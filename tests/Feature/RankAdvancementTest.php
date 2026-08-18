<?php

namespace Tests\Feature;

use App\Livewire\RankAdvancementTracker;
use App\Models\Rank;
use App\Models\User;
use App\Models\UserRankProgress;
use Database\Seeders\RankRequirementSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class RankAdvancementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(RankSeeder::class);
        $this->seed(RankRequirementSeeder::class);
    }

    public function test_member_can_view_rank_advancement_tracker(): void
    {
        $member = $this->memberWithRank('FA');

        $this->actingAs($member)
            ->get(route('rank-advancement.index'))
            ->assertOk()
            ->assertSee('Rank Advancement')
            ->assertSee('Rank ladder')
            ->assertSee('Senior Field Associate');
    }

    public function test_member_can_start_and_submit_requirement(): void
    {
        $member = $this->memberWithRank('FA');
        $progress = $this->firstProgressFor($member);

        Livewire::actingAs($member)
            ->test(RankAdvancementTracker::class)
            ->call('startRequirement', $progress->id)
            ->call('openRequirement', $progress->id)
            ->set('memberNotes', 'Completed all licensing steps.')
            ->call('submitRequirement', $progress->id);

        $progress->refresh();
        $this->assertSame('ready_for_review', $progress->status);
        $this->assertSame('Completed all licensing steps.', $progress->member_notes);
    }

    public function test_team_leader_can_approve_submitted_requirement(): void
    {
        $leader = User::factory()->create();
        $leader->assignRole('team-leader');

        $member = $this->memberWithRank('FA');
        $member->update(['sponsor_id' => $leader->id]);

        DB::table('user_hierarchy_paths')->insert([
            ['ancestor_id' => $leader->id, 'descendant_id' => $leader->id, 'depth' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['ancestor_id' => $leader->id, 'descendant_id' => $member->id, 'depth' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $progress = $this->firstProgressFor($member);
        $progress->update([
            'status' => 'ready_for_review',
            'submitted_at' => now(),
        ]);

        Livewire::actingAs($leader)
            ->test(RankAdvancementTracker::class, ['member' => $member->id])
            ->call('approveRequirement', $progress->id);

        $progress->refresh();
        $this->assertSame('completed', $progress->status);
        $this->assertNotNull($progress->completed_at);
    }

    public function test_seeder_creates_requirements_for_advancement_ranks(): void
    {
        $sfaRankId = Rank::query()->where('code', 'SFA')->value('id');

        $this->assertGreaterThan(
            0,
            \App\Models\RankRequirement::query()->where('rank_id', $sfaRankId)->count()
        );
    }

    private function memberWithRank(string $code): User
    {
        $rankId = Rank::query()->where('code', $code)->value('id');
        $member = User::factory()->create(['rank_id' => $rankId]);
        $member->assignRole('associate');

        return $member;
    }

    private function firstProgressFor(User $member): UserRankProgress
    {
        app(\App\Services\RankAdvancementService::class)->ensureProgressRecords($member);

        return UserRankProgress::query()
            ->where('user_id', $member->id)
            ->firstOrFail();
    }
}
