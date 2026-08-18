<?php

namespace Tests\Feature\Fna;

use App\Jobs\Fna\RollupFnaAnalytics;
use App\Models\FnaRecord;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\Fna\FnaAnalyticsService;
use App\Services\Fna\FnaRecordService;
use App\Services\Fna\FnaReviewService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FnaAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_summary_metrics_for_owned_fnas(): void
    {
        $user = User::factory()->create();
        $user->assignRole('associate');

        app(FnaRecordService::class)->create($user, ['client_name' => 'Draft Client']);
        $approved = app(FnaRecordService::class)->create($user, ['client_name' => 'Approved Client']);
        $approved->update([
            'status' => 'approved_by_cfm',
            'dime_completed' => true,
            'protection_gap' => 50000,
            'approved_at' => now(),
        ]);

        $summary = app(FnaAnalyticsService::class)->summaryFor($user);

        $this->assertSame(2, $summary['total_fnas']);
        $this->assertSame(1, $summary['draft_fnas']);
        $this->assertSame(1, $summary['approved_fnas']);
        $this->assertSame(1, $summary['dime_completed']);
        $this->assertSame(50000.0, $summary['avg_protection_gap']);
        $this->assertNull($summary['avg_cfm_review_hours']);
    }

    public function test_cfm_summary_includes_trainee_awaiting_review(): void
    {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $trainee = User::factory()->create();
        $trainee->assignRole('associate');

        MentorAssignment::create([
            'mentor_id' => $cfm->id,
            'apprentice_id' => $trainee->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        $fna = app(FnaRecordService::class)->create($trainee, ['client_name' => 'Trainee Client']);
        $fna->update([
            'status' => 'submitted_to_cfm',
            'cfm_user_id' => $cfm->id,
            'submitted_at' => now(),
        ]);

        $summary = app(FnaAnalyticsService::class)->summaryFor($cfm);

        $this->assertSame(1, $summary['awaiting_review']);
        $this->assertNull($summary['avg_cfm_review_hours']);
    }

    public function test_cfm_avg_review_hours_from_approved_fnas(): void
    {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $trainee = User::factory()->create();
        $trainee->assignRole('associate');

        MentorAssignment::create([
            'mentor_id' => $cfm->id,
            'apprentice_id' => $trainee->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        $fna = app(FnaRecordService::class)->create($trainee, ['client_name' => 'Reviewed Client']);
        $fna->update([
            'status' => 'approved_by_cfm',
            'cfm_user_id' => $cfm->id,
            'submitted_at' => now()->subHours(4),
            'approved_at' => now(),
        ]);

        $summary = app(FnaAnalyticsService::class)->summaryFor($cfm);

        $this->assertNotNull($summary['avg_cfm_review_hours']);
        $this->assertGreaterThan(0, $summary['avg_cfm_review_hours']);
    }

    public function test_rollup_job_writes_snapshots(): void
    {
        $user = User::factory()->create();
        $user->assignRole('associate');

        app(FnaRecordService::class)->create($user, ['client_name' => 'Snapshot Client']);

        (new RollupFnaAnalytics)->handle(app(FnaAnalyticsService::class));

        $this->assertDatabaseHas('fna_analytics_snapshots', [
            'user_id' => $user->id,
            'metric_key' => 'total_fnas',
            'value' => 1,
        ]);
    }

    public function test_completion_progress_returns_status_segments(): void
    {
        $user = User::factory()->create();
        $user->assignRole('associate');

        app(FnaRecordService::class)->create($user, ['client_name' => 'Progress A']);
        $revision = app(FnaRecordService::class)->create($user, ['client_name' => 'Progress B']);
        $revision->update(['status' => 'revision_requested']);

        $progress = app(FnaAnalyticsService::class)->completionProgress($user);

        $this->assertSame(2, $progress['total']);
        $this->assertSame(1, collect($progress['segments'])->firstWhere('key', 'draft')['count']);
        $this->assertSame(1, collect($progress['segments'])->firstWhere('key', 'revision')['count']);
    }
}
