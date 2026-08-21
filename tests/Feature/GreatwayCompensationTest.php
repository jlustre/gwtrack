<?php

namespace Tests\Feature;

use App\Models\Rank;
use App\Models\User;
use App\Services\CompensationService;
use App\Support\GreatwayRankCatalog;
use Database\Seeders\RankRequirementSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GreatwayCompensationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            RankSeeder::class,
            RankRequirementSeeder::class,
        ]);
    }

    public function test_seeder_creates_greatway_ranks_with_fyo_rates(): void
    {
        $this->assertSame(11, Rank::query()->count());
        $this->assertSame(0, Rank::query()->whereIn('code', ['FA', 'SFA', 'SM', 'ED', 'SED', 'NED', 'SNED', 'EP'])->count());

        $trainingAssociate = Rank::query()->where('code', 'TA')->firstOrFail();
        $this->assertSame('Training Associate', $trainingAssociate->name);
        $this->assertSame('producer', $trainingAssociate->track);
        $this->assertSame(80.0, (float) $trainingAssociate->total_fyo_rate);
        $this->assertFalse($trainingAssociate->is_field_leader);

        $nevc = Rank::query()->where('code', 'NEVC')->firstOrFail();
        $this->assertSame('National Executive Vice Chairman', $nevc->name);
        $this->assertSame('builder', $nevc->track);
        $this->assertSame(165.0, (float) $nevc->total_fyo_rate);
        $this->assertSame(20.0, (float) $nevc->ppb_rate);
        $this->assertTrue($nevc->is_field_leader);

        $this->assertTrue(Rank::query()->where('code', 'SVP')->firstOrFail()->isAtLeast('SVP'));
        $this->assertFalse(Rank::query()->where('code', 'AVP')->firstOrFail()->isAtLeast('SVP'));
    }

    public function test_seeder_remaps_legacy_rank_codes(): void
    {
        Rank::query()->where('code', 'TA')->update(['code' => 'FA', 'name' => 'Legacy Rank FA']);
        Rank::query()->where('code', 'ASC')->update(['code' => 'SFA', 'name' => 'Legacy Rank SFA']);

        $this->seed(RankSeeder::class);

        $this->assertNotNull(Rank::query()->where('code', 'TA')->first());
        $this->assertNotNull(Rank::query()->where('code', 'ASC')->first());
        $this->assertNull(Rank::query()->where('code', 'FA')->first());
        $this->assertNull(Rank::query()->where('code', 'SFA')->first());
    }

    public function test_compensation_snapshot_includes_next_rank_and_field_leader_overrides(): void
    {
        $member = User::factory()->create([
            'rank_id' => Rank::query()->where('code', 'TA')->value('id'),
        ]);

        $snapshot = app(CompensationService::class)->snapshotForUser($member);

        $this->assertSame('TA', $snapshot['current']['code']);
        $this->assertSame(80.0, $snapshot['current']['total_fyo_rate']);
        $this->assertSame('ASC', $snapshot['next']['code']);
        $this->assertSame(100.0, $snapshot['next']['total_fyo_rate']);
        $this->assertFalse($snapshot['is_field_leader']);
        $this->assertSame([], $snapshot['generational']);

        $fieldLeader = User::factory()->create([
            'rank_id' => Rank::query()->where('code', 'SVP')->value('id'),
        ]);

        $leaderSnapshot = app(CompensationService::class)->snapshotForUser($fieldLeader);

        $this->assertTrue($leaderSnapshot['is_field_leader']);
        $this->assertCount(6, $leaderSnapshot['generational']);
        $this->assertSame(25, $leaderSnapshot['generational'][0]['fyo_rate']);
        $this->assertNotEmpty($leaderSnapshot['bonus_pools']);
    }

    public function test_rank_advancement_page_shows_greatway_compensation(): void
    {
        $member = User::factory()->create([
            'rank_id' => Rank::query()->where('code', 'TA')->value('id'),
        ]);
        $member->assignRole('associate');

        $this->actingAs($member)
            ->get(route('rank-advancement.index'))
            ->assertOk()
            ->assertSee('Training Associate')
            ->assertSee('Associate')
            ->assertSee('Compensation')
            ->assertSee('Producer')
            ->assertSee('80%');
    }

    public function test_catalog_maps_legacy_rank_codes(): void
    {
        $this->assertSame('TA', GreatwayRankCatalog::resolveCode('FA'));
        $this->assertSame('SVP', GreatwayRankCatalog::resolveCode('ED'));
        $this->assertSame('SEVC', GreatwayRankCatalog::resolveCode('SNED'));
        $this->assertSame('TA', GreatwayRankCatalog::entryCode());
        $this->assertSame('SVP', GreatwayRankCatalog::agencyLeaderCode());
    }
}
