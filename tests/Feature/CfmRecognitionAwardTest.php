<?php

namespace Tests\Feature;

use App\Jobs\CfmEffectiveness\RollupCfmEffectivenessScores;
use App\Models\CfmEffectiveness\CfmEffectivenessScore;
use App\Models\CfmEffectiveness\CfmLeaderboard;
use App\Models\CfmEffectiveness\CfmRecognitionAward;
use App\Models\CfmEffectiveness\CfmRecognitionBadge;
use App\Models\User;
use App\Services\CfmEffectiveness\CfmRecognitionAwardService;
use Carbon\Carbon;
use Database\Seeders\CfmEffectivenessSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CfmRecognitionAwardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CfmEffectivenessSeeder::class);
    }

    public function test_awards_leaderboard_champion_badges_for_closed_month(): void
    {
        $topCfm = $this->createCfm();
        $runnerUp = $this->createCfm();

        $periodStart = now()->subMonth()->startOfMonth();
        $periodEnd = now()->subMonth()->endOfMonth();

        $this->seedLeaderboard('overall_effectiveness', $periodStart, $periodEnd, [
            ['cfm_id' => $topCfm->id, 'rank' => 1, 'score' => 92],
            ['cfm_id' => $runnerUp->id, 'rank' => 2, 'score' => 88],
        ]);

        $this->seedLeaderboard('fap_completion_rate', $periodStart, $periodEnd, [
            ['cfm_id' => $topCfm->id, 'rank' => 1, 'score' => 95],
        ]);

        $awards = app(CfmRecognitionAwardService::class)->awardFromLeaderboard($periodStart, $periodEnd);

        $mentorBadge = CfmRecognitionBadge::query()->where('code', 'mentor_of_month')->firstOrFail();
        $fastTrackBadge = CfmRecognitionBadge::query()->where('code', 'fast_track_mentor')->firstOrFail();
        $fapBadge = CfmRecognitionBadge::query()->where('code', 'fap_champion')->firstOrFail();

        $this->assertTrue($awards->contains(fn (CfmRecognitionAward $award) => $award->badge_id === $mentorBadge->id && $award->cfm_id === $topCfm->id));
        $this->assertTrue($awards->contains(fn (CfmRecognitionAward $award) => $award->badge_id === $fastTrackBadge->id && $award->cfm_id === $runnerUp->id));
        $this->assertTrue($awards->contains(fn (CfmRecognitionAward $award) => $award->badge_id === $fapBadge->id && $award->cfm_id === $topCfm->id));

        $this->assertDatabaseHas('cfm_recognition_awards', [
            'cfm_id' => $topCfm->id,
            'badge_id' => $mentorBadge->id,
        ]);
    }

    public function test_awards_rising_mentor_to_most_improved_cfm(): void
    {
        $improvedCfm = $this->createCfm();
        $flatCfm = $this->createCfm();

        $periodStart = now()->subMonth()->startOfMonth();
        $periodEnd = now()->subMonth()->endOfMonth();
        $previousStart = $periodStart->copy()->subMonth()->startOfMonth();
        $previousEnd = $periodStart->copy()->subMonth()->endOfMonth();

        $this->seedScore($improvedCfm, $previousStart, $previousEnd, 70);
        $this->seedScore($improvedCfm, $periodStart, $periodEnd, 82);
        $this->seedScore($flatCfm, $previousStart, $previousEnd, 80);
        $this->seedScore($flatCfm, $periodStart, $periodEnd, 82);

        $awards = app(CfmRecognitionAwardService::class)->awardFromLeaderboard($periodStart, $periodEnd);

        $risingBadge = CfmRecognitionBadge::query()->where('code', 'rising_mentor')->firstOrFail();

        $this->assertTrue(
            $awards->contains(fn (CfmRecognitionAward $award) => $award->badge_id === $risingBadge->id && $award->cfm_id === $improvedCfm->id)
        );
    }

    public function test_does_not_duplicate_awards_when_run_twice(): void
    {
        $cfm = $this->createCfm();
        $periodStart = now()->subMonth()->startOfMonth();
        $periodEnd = now()->subMonth()->endOfMonth();

        $this->seedLeaderboard('overall_effectiveness', $periodStart, $periodEnd, [
            ['cfm_id' => $cfm->id, 'rank' => 1, 'score' => 90],
        ]);

        $service = app(CfmRecognitionAwardService::class);
        $service->awardFromLeaderboard($periodStart, $periodEnd);
        $secondRun = $service->awardFromLeaderboard($periodStart, $periodEnd);

        $this->assertCount(0, $secondRun);
        $this->assertSame(
            1,
            CfmRecognitionAward::query()
                ->where('cfm_id', $cfm->id)
                ->where('badge_id', CfmRecognitionBadge::query()->where('code', 'mentor_of_month')->value('id'))
                ->count()
        );
    }

    public function test_monthly_rollup_awards_recognition_badges(): void
    {
        $cfm = $this->createCfm();

        RollupCfmEffectivenessScores::dispatchSync('monthly');

        $this->assertGreaterThan(
            0,
            CfmRecognitionAward::query()->where('cfm_id', $cfm->id)->count()
        );
    }

    private function createCfm(): User
    {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        return $cfm;
    }

    /**
     * @param  array<int, array{cfm_id: int, rank: int, score: float}>  $entries
     */
    private function seedLeaderboard(string $metricKey, Carbon $periodStart, Carbon $periodEnd, array $entries): void
    {
        foreach ($entries as $entry) {
            CfmLeaderboard::query()->create([
                'metric_key' => $metricKey,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'cfm_id' => $entry['cfm_id'],
                'rank_position' => $entry['rank'],
                'score' => $entry['score'],
            ]);
        }
    }

    private function seedScore(User $cfm, Carbon $periodStart, Carbon $periodEnd, float $overallScore): void
    {
        CfmEffectivenessScore::query()->create([
            'cfm_id' => $cfm->id,
            'period_type' => 'monthly',
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'objective_score' => $overallScore,
            'feedback_score' => $overallScore,
            'ao_score' => $overallScore,
            'overall_score' => $overallScore,
            'calculated_at' => now(),
        ]);
    }
}
