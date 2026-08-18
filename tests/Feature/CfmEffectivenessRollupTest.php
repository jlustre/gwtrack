<?php

namespace Tests\Feature;

use App\Jobs\CfmEffectiveness\RollupCfmEffectivenessScores;
use App\Models\CfmEffectiveness\CfmEffectivenessScore;
use App\Models\CfmEffectiveness\CfmLeaderboard;
use App\Models\User;
use Database\Seeders\CfmEffectivenessSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class CfmEffectivenessRollupTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CfmEffectivenessSeeder::class);
    }

    public function test_daily_rollup_persists_current_month_scores_without_leaderboard_rows(): void
    {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        RollupCfmEffectivenessScores::dispatchSync();

        $this->assertTrue(
            CfmEffectivenessScore::query()
                ->where('cfm_id', $cfm->id)
                ->whereDate('period_start', now()->startOfMonth())
                ->whereDate('period_end', now()->endOfMonth())
                ->exists()
        );

        $this->assertSame(0, CfmLeaderboard::query()->count());
    }

    public function test_monthly_rollup_persists_previous_month_scores_and_leaderboard(): void
    {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        RollupCfmEffectivenessScores::dispatchSync('monthly');

        $this->assertTrue(
            CfmEffectivenessScore::query()
                ->where('cfm_id', $cfm->id)
                ->whereDate('period_start', now()->subMonth()->startOfMonth())
                ->whereDate('period_end', now()->subMonth()->endOfMonth())
                ->exists()
        );

        $this->assertGreaterThan(0, CfmLeaderboard::query()->count());
        $this->assertTrue(
            CfmLeaderboard::query()
                ->where('cfm_id', $cfm->id)
                ->whereDate('period_start', now()->subMonth()->startOfMonth())
                ->exists()
        );
    }

    public function test_rollup_jobs_are_scheduled_daily_and_monthly(): void
    {
        $this->assertTrue(
            collect(Schedule::events())->contains(
                fn ($event) => str_contains((string) ($event->description ?? ''), RollupCfmEffectivenessScores::class)
                    && $event->expression === '45 6 * * *',
            ),
            'Expected daily CFM effectiveness rollup at 06:45.',
        );

        $this->assertTrue(
            collect(Schedule::events())->contains(
                fn ($event) => str_contains((string) ($event->description ?? ''), RollupCfmEffectivenessScores::class)
                    && $event->expression === '0 7 1 * *',
            ),
            'Expected monthly CFM effectiveness rollup on the 1st at 07:00.',
        );
    }
}
