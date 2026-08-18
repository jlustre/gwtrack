<?php

namespace Tests\Feature;

use App\Events\Prospects\ProspectConverted;
use App\Models\FnaRecord;
use App\Models\Goal;
use App\Models\GoalActivityLog;
use App\Models\GoalCategory;
use App\Models\MemberProductionEntry;
use App\Models\Prospect;
use App\Models\ProspectConversion;
use App\Models\User;
use App\Models\TaskUser;
use App\Support\SystemTaskAssignor;
use App\Services\Fna\FnaRecordService;
use App\Services\Fna\FnaReviewService;
use App\Services\Prospects\ProspectConversionService;
use App\Services\Prospects\ProspectMemberGoalsBridge;
use Database\Seeders\CountrySeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\GoalCategorySeeder;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\StateProvinceSeeder;
use Database\Seeders\SystemTaskAssignorSeeder;
use Database\Seeders\TaskCategorySeeder;
use Database\Seeders\TimezoneSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RevenueBridgeTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
            RankSeeder::class,
            GoalCategorySeeder::class,
            TaskCategorySeeder::class,
            SystemTaskAssignorSeeder::class,
            EmailTemplateSeeder::class,
            NotificationConfigSeeder::class,
            CountrySeeder::class,
            StateProvinceSeeder::class,
            TimezoneSeeder::class,
        ]);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('member');
    }

    public function test_associate_conversion_completed_refreshes_recruit_goal_and_logs_activity(): void
    {
        $recruitGoal = $this->createMetricGoal('recruits', 2);

        $prospect = $this->makeRecruitingProspect();
        $newMember = User::factory()->create(['sponsor_id' => $this->owner->id]);

        $conversion = ProspectConversion::query()->create([
            'prospect_id' => $prospect->id,
            'converted_by' => $this->owner->id,
            'created_user_id' => $newMember->id,
            'conversion_type' => 'associate',
            'converted_at' => now(),
        ]);

        app(ProspectMemberGoalsBridge::class)->handleConversion(new ProspectConverted(
            $prospect->fresh(),
            $this->owner,
            $conversion,
            'completed',
        ));

        $this->assertSame(1.0, (float) $recruitGoal->fresh()->actual_value);
        $this->assertDatabaseHas('goal_activity_logs', [
            'user_id' => $this->owner->id,
            'activity_key' => 'recruits',
            'source' => 'bridge',
        ]);
        $this->assertDatabaseHas('task_users', [
            'assignee_id' => $this->owner->id,
            'assignor_id' => SystemTaskAssignor::USER_ID,
            'related_prospect_id' => $prospect->id,
        ]);
    }

    public function test_client_conversion_creates_production_entry_and_refreshes_application_goal(): void
    {
        $applicationGoal = $this->createMetricGoal('applications', 3);
        $prospect = $this->makeInsuranceProspect();

        app(ProspectConversionService::class)->convertToClient(
            $prospect,
            $this->owner,
            'POL-BRIDGE-001',
            'APP-BRIDGE-001',
            'Issued policy',
        );

        $this->assertSame(1.0, (float) $applicationGoal->fresh()->actual_value);
        $this->assertDatabaseHas('member_production_entries', [
            'user_id' => $this->owner->id,
            'policy_reference' => 'POL-BRIDGE-001',
            'status' => 'posted',
        ]);
        $this->assertDatabaseHas('goal_activity_logs', [
            'user_id' => $this->owner->id,
            'activity_key' => 'applications',
            'source' => 'bridge',
        ]);
    }

    public function test_fna_approval_refreshes_fna_goals_and_creates_follow_up_task(): void
    {
        $trainee = User::factory()->create();
        $trainee->assignRole('associate');
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        \App\Models\MentorAssignment::query()->create([
            'mentor_id' => $cfm->id,
            'apprentice_id' => $trainee->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        $fnaGoal = $this->createMetricGoalFor($trainee, 'fna_approved', 1);

        $fna = app(FnaRecordService::class)->create($trainee, ['client_name' => 'Bridge Client']);
        $this->fillFnaForSubmission($fna);
        app(FnaReviewService::class)->submitForReview($fna->fresh(), $trainee);

        $submitted = FnaRecord::query()->findOrFail($fna->id);
        app(FnaReviewService::class)->approve($submitted, $cfm, 'Looks good.');

        $this->assertSame(1.0, (float) $fnaGoal->fresh()->actual_value);
        $this->assertDatabaseHas('goal_activity_logs', [
            'user_id' => $trainee->id,
            'activity_key' => 'fnas',
            'source' => 'bridge',
        ]);
        $task = TaskUser::query()->where('related_fna_id', $fna->id)->first();
        $this->assertNotNull($task);
        $this->assertSame(SystemTaskAssignor::USER_ID, $task->assignor_id);
        $this->assertSame('FNA', $task->taskCategory?->name);
    }

    public function test_associate_invitation_initiated_syncs_invitation_metrics(): void
    {
        $invitationGoal = $this->createMetricGoal('invitations_sent', 5);
        $prospect = $this->makeRecruitingProspect(['email' => 'invite@example.com']);

        app(ProspectConversionService::class)->convertToAssociate($prospect, $this->owner);

        $this->assertSame(1.0, (float) $invitationGoal->fresh()->actual_value);
        $this->assertGreaterThan(0, GoalActivityLog::query()->where('user_id', $this->owner->id)->count());
    }

    private function createMetricGoal(string $metricKey, float $target): Goal
    {
        return $this->createMetricGoalFor($this->owner, $metricKey, $target);
    }

    private function createMetricGoalFor(User $user, string $metricKey, float $target): Goal
    {
        $categoryId = GoalCategory::query()->where('slug', 'recruiting')->value('id')
            ?? GoalCategory::query()->value('id');

        return Goal::query()->create([
            'user_id' => $user->id,
            'goal_category_id' => $categoryId,
            'hierarchy_level' => 'monthly',
            'goal_type' => 'outcome',
            'name' => ucfirst(str_replace('_', ' ', $metricKey)).' goal',
            'measurement_type' => 'number',
            'metric_key' => $metricKey,
            'target_value' => $target,
            'actual_value' => 0,
            'status' => 'active',
            'starts_at' => now()->startOfMonth(),
            'deadline_at' => now()->endOfMonth(),
        ]);
    }

    protected function fillFnaForSubmission(FnaRecord $fna): void
    {
        $fna->update([
            'client_name' => 'Bridge Client',
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

        $fna->household()->updateOrCreate([], ['household_income' => 120000, 'children_count' => 1]);
        $fna->incomeDetail()->updateOrCreate([], ['annual_income' => 80000, 'monthly_income' => 6666]);
        $fna->debtDetail()->updateOrCreate([], ['credit_card_debt' => 5000, 'total_debt' => 5000]);
        $fna->assetDetail()->updateOrCreate([], ['checking_savings' => 10000, 'emergency_fund' => 5000]);
        $fna->existingCoverage()->updateOrCreate([], ['existing_life_insurance_amount' => 100000, 'term_coverage' => 100000]);
        $fna->goals()->updateOrCreate([], ['selected_goals' => ['income_protection']]);
        $fna->riskAssessment()->updateOrCreate([], [
            'main_financial_concern' => 'Family income',
            'urgency_level' => 'high',
            'risk_tolerance' => 'moderate',
        ]);

        $fna->update(['completeness_score' => app(\App\Services\Fna\FnaCompletenessService::class)->score($fna->fresh())]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRecruitingProspect(array $overrides = []): Prospect
    {
        $stageId = DB::table('pipeline_stages')->where('slug', 'presentation-completed')->value('id');
        $funnelId = DB::table('prospect_funnels')->where('key', 'recruiting')->value('id');
        $sourceId = DB::table('prospect_sources')->where('slug', 'warm-market')->value('id');

        return Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $stageId,
            'prospect_source_id' => $sourceId,
            'funnel_type' => 'recruiting',
            'first_name' => 'Recruit',
            'last_name' => 'Candidate',
            'interest_level' => 'hot',
            'priority' => 'high',
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeInsuranceProspect(array $overrides = []): Prospect
    {
        $stageId = DB::table('pipeline_stages')->where('slug', 'application-submitted')->value('id');
        $funnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');
        $sourceId = DB::table('prospect_sources')->where('slug', 'warm-market')->value('id');

        return Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $stageId,
            'prospect_source_id' => $sourceId,
            'funnel_type' => 'insurance',
            'first_name' => 'Insurance',
            'last_name' => 'Lead',
            'interest_level' => 'warm',
            'priority' => 'medium',
            ...$overrides,
        ]);
    }
}
