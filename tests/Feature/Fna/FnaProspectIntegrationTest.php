<?php

namespace Tests\Feature\Fna;

use App\Models\FnaRecord;
use App\Models\MentorAssignment;
use App\Models\PipelineStage;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Models\User;
use App\Services\Fna\FnaCalendarBridge;
use App\Services\Fna\FnaCompletenessService;
use App\Services\Fna\FnaProspectBridge;
use App\Services\Fna\FnaRecordService;
use App\Services\Fna\FnaReviewService;
use App\Services\Fna\FnaWorkflowService;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\FnaLookupSeeder;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FnaProspectIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
            CalendarModuleSeeder::class,
            FnaLookupSeeder::class,
        ]);
    }

    protected function createTraineeWithCfm(): array
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

        return [$trainee, $cfm];
    }

    protected function createProspect(User $owner): Prospect
    {
        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        return Prospect::create([
            'owner_id' => $owner->id,
            'first_name' => 'Integration',
            'last_name' => 'Prospect',
            'email' => 'integration.prospect@example.com',
            'phone' => '6045550100',
            'pipeline_stage_id' => $stageId,
            'fna_status' => 'not_started',
            'status' => 'active',
            'interest_level' => 'warm',
        ]);
    }

    protected function fillFnaForSubmission(FnaRecord $fna): void
    {
        $fna->update([
            'client_name' => 'Integration Client',
            'client_email' => 'client@test.com',
            'occupation' => 'Engineer',
            'city' => 'Vancouver',
            'state_province' => 'BC',
            'country' => 'Canada',
            'main_needs_identified' => 'Income protection',
            'recommended_next_action' => 'Schedule meeting',
            'associate_recommendation' => 'Term coverage',
            'dime_completed' => true,
        ]);

        $fna->household()->update(['household_income' => 120000, 'children_count' => 1]);
        $fna->incomeDetail()->update(['annual_income' => 80000, 'monthly_income' => 6666]);
        $fna->debtDetail()->update(['credit_card_debt' => 5000, 'total_debt' => 5000]);
        $fna->assetDetail()->update(['checking_savings' => 10000, 'emergency_fund' => 5000]);
        $fna->existingCoverage()->update(['existing_life_insurance_amount' => 100000, 'term_coverage' => 100000]);
        $fna->goals()->update(['selected_goals' => ['income_protection']]);
        $fna->riskAssessment()->update([
            'main_financial_concern' => 'Family income',
            'urgency_level' => 'high',
            'risk_tolerance' => 'moderate',
        ]);

        $fna->update(['completeness_score' => app(FnaCompletenessService::class)->score($fna->fresh())]);
    }

    public function test_approved_fna_syncs_prospect_status_stage_and_timeline(): void
    {
        [$trainee, $cfm] = $this->createTraineeWithCfm();
        $prospect = $this->createProspect($trainee);

        $fna = app(FnaRecordService::class)->create($trainee, [], $prospect);
        $this->fillFnaForSubmission($fna);

        app(FnaWorkflowService::class)->transition($fna->fresh(), $trainee, 'ready_for_review');
        app(FnaReviewService::class)->submitForReview($fna->fresh(), $trainee);
        app(FnaReviewService::class)->approve($fna->fresh(), $cfm);

        $prospect->refresh();
        $financialReviewStageId = PipelineStage::query()->where('slug', 'financial-review')->value('id');

        $this->assertSame('scheduled', $prospect->fna_status);
        $this->assertSame((int) $financialReviewStageId, (int) $prospect->pipeline_stage_id);

        $this->assertTrue(
            ProspectActivity::query()
                ->where('prospect_id', $prospect->id)
                ->where('activity_type', 'financial_review')
                ->where('notes', 'like', '%approved by CFM%')
                ->exists()
        );
    }

    public function test_scheduling_meeting_creates_calendar_event_and_updates_fna_status(): void
    {
        [$trainee, $cfm] = $this->createTraineeWithCfm();
        $prospect = $this->createProspect($trainee);

        $fna = app(FnaRecordService::class)->create($trainee, [], $prospect);
        $this->fillFnaForSubmission($fna);

        app(FnaWorkflowService::class)->transition($fna->fresh(), $trainee, 'ready_for_review');
        app(FnaReviewService::class)->submitForReview($fna->fresh(), $trainee);
        $fna = app(FnaReviewService::class)->approve($fna->fresh(), $cfm);

        $startsAt = Carbon::parse('2026-06-15 14:00:00');

        $event = app(FnaCalendarBridge::class)->scheduleMeeting($fna, $trainee, [
            'meeting_type' => 'fna-client-meeting',
            'starts_at' => $startsAt,
            'duration_minutes' => 60,
            'location_or_link' => 'https://zoom.example/meeting',
        ]);

        $fna->refresh();
        $prospect->refresh();

        $this->assertSame('scheduled_for_client_review', $fna->status);
        $this->assertSame($event->id, $fna->calendar_event_id);
        $this->assertSame($fna->id, $event->related_fna_id);
        $this->assertSame($prospect->id, $event->related_prospect_id);
        $this->assertSame('scheduled', $prospect->fna_status);

        $this->assertDatabaseHas('calendar_events', [
            'id' => $event->id,
            'related_fna_id' => $fna->id,
            'related_prospect_id' => $prospect->id,
            'status' => 'scheduled',
        ]);

        $this->assertTrue(
            ProspectActivity::query()
                ->where('prospect_id', $prospect->id)
                ->where('notes', 'like', '%meeting scheduled%')
                ->exists()
        );
    }

    public function test_prospect_bridge_link_updates_status_and_logs_timeline(): void
    {
        [$trainee] = $this->createTraineeWithCfm();
        $prospect = $this->createProspect($trainee);

        $fna = app(FnaRecordService::class)->create($trainee, ['client_name' => 'Unlinked Client']);

        $bridge = app(FnaProspectBridge::class);
        $bridge->linkProspect($fna, $prospect, $trainee);

        $prospect->refresh();

        $this->assertSame($prospect->id, $fna->fresh()->prospect_id);
        $this->assertSame('not_started', $prospect->fna_status);

        $this->assertTrue(
            ProspectActivity::query()
                ->where('prospect_id', $prospect->id)
                ->where('notes', 'like', '%linked to prospect%')
                ->exists()
        );
    }
}
