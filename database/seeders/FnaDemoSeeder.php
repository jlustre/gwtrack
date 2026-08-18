<?php

namespace Database\Seeders;

use App\Models\FnaRecord;
use App\Models\MentorAssignment;
use App\Models\Prospect;
use App\Models\User;
use App\Services\Fna\DimeCalculatorService;
use App\Services\Fna\FnaCalendarBridge;
use App\Services\Fna\FnaCompletenessService;
use App\Services\Fna\FnaRecordService;
use App\Services\Fna\FnaReviewService;
use App\Services\Fna\FnaWorkflowService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class FnaDemoSeeder extends Seeder
{
    public function run(): void
    {
        $maya = User::query()->where('email', 'maya.fap@example.com')->first();
        $celeste = User::query()->where('email', 'cfm@gwtrack.com')->first();
        $mariaApprentice = User::query()->where('email', 'maria.apprentice1@example.com')->first();
        $mariaCfm = User::query()->where('email', 'maria.cfm@gwtrack.com')->first();

        if (! $maya || ! $celeste) {
            $this->command?->warn('FnaDemoSeeder skipped: run TaskScenarioSeeder and CfmManagementSeeder first.');

            return;
        }

        if (! MentorAssignment::query()
            ->where('apprentice_id', $maya->id)
            ->where('mentor_id', $celeste->id)
            ->where('status', 'active')
            ->exists()) {
            $this->command?->warn('FnaDemoSeeder skipped: no active mentor assignment for maya.fap@example.com.');

            return;
        }

        Notification::fake();

        $this->clearPreviousDemoRecords();

        $records = app(FnaRecordService::class);
        $reviews = app(FnaReviewService::class);
        $workflow = app(FnaWorkflowService::class);
        $completeness = app(FnaCompletenessService::class);
        $dime = app(DimeCalculatorService::class);
        $calendar = app(FnaCalendarBridge::class);

        $prospectAllen = $this->demoProspect($maya, 'Demo', 'Allen', 'demo.allen.fna@example.com');
        $prospectBrooks = $this->demoProspect($maya, 'Demo', 'Brooks', 'demo.brooks.fna@example.com');

        // 1. Minimal draft — wizard / AI completeness hints
        $draft = $records->create($maya, [
            'client_name' => 'Demo Client — Draft',
            'title' => '[Demo] Draft FNA',
        ]);
        $this->markDemo($draft, 'draft');
        $draft->update([
            'client_email' => 'draft.demo@example.com',
            'city' => 'Vancouver',
            'state_province' => 'BC',
            'country' => 'Canada',
            'completeness_score' => $completeness->score($draft->fresh()),
        ]);

        // 2. Ready for review — mostly complete, not yet submitted
        $ready = $records->create($maya, [
            'client_name' => 'Demo Client — Ready',
            'title' => '[Demo] Ready for Review',
        ], $prospectBrooks);
        $this->fillCompleteFna($ready, $completeness, $dime, $maya);
        $ready = $workflow->transition($ready->fresh(), $maya, 'ready_for_review');
        $this->markDemo($ready);

        // 3. Submitted — appears in Celeste CFM review queue
        $submitted = $records->create($maya, [
            'client_name' => 'Demo Client — Submitted',
            'title' => '[Demo] Awaiting CFM Review',
        ]);
        $this->fillCompleteFna($submitted, $completeness, $dime, $maya);
        $submitted = $reviews->submitForReview($submitted->fresh(), $maya);
        $this->markDemo($submitted);

        // 4. Revision requested — trainee must revise
        $revision = $records->create($maya, [
            'client_name' => 'Demo Client — Revision',
            'title' => '[Demo] Revision Requested',
        ]);
        $this->fillCompleteFna($revision, $completeness, $dime, $maya);
        $revision = $reviews->submitForReview($revision->fresh(), $maya);
        $revision = $reviews->requestRevision(
            $revision->fresh(),
            $celeste,
            'Please expand the income section and clarify the client\'s main financial concern before resubmitting.',
        );
        $this->markDemo($revision);

        // 5. Approved — meeting prep, export, schedule meeting
        $approved = $records->create($maya, [
            'client_name' => 'Demo Client — Approved',
            'title' => '[Demo] Approved by CFM',
        ], $prospectAllen);
        $this->fillCompleteFna($approved, $completeness, $dime, $maya, gapBoost: true);
        $approved = $reviews->submitForReview($approved->fresh(), $maya);
        $approved = $reviews->approve(
            $approved->fresh(),
            $celeste,
            'Strong FNA — schedule the client review meeting and walk through the protection gap.',
        );
        $this->markDemo($approved);

        // 6. Scheduled client meeting — calendar + prospect integration
        $scheduled = $records->create($maya, [
            'client_name' => 'Demo Client — Scheduled',
            'title' => '[Demo] Client Meeting Scheduled',
        ], $prospectAllen);
        $this->fillCompleteFna($scheduled, $completeness, $dime, $maya);
        $scheduled = $reviews->submitForReview($scheduled->fresh(), $maya);
        $scheduled = $reviews->approve($scheduled->fresh(), $celeste, 'Approved — client meeting booked.');
        $calendar->scheduleMeeting($scheduled->fresh(), $maya, [
            'meeting_type' => 'fna-client-meeting',
            'starts_at' => now()->addDays(5)->setTime(14, 0),
            'duration_minutes' => 60,
            'location_or_link' => 'https://zoom.us/j/demo-fna-meeting',
            'notes' => 'Demo seeded client FNA review meeting.',
        ]);
        $this->markDemo($scheduled->fresh());

        // 7. Maria apprentice — second CFM queue entry
        if ($mariaApprentice && $mariaCfm) {
            $mariaSubmitted = $records->create($mariaApprentice, [
                'client_name' => 'Demo Client — Maria Queue',
                'title' => '[Demo] Maria CFM Queue',
            ]);
            $this->fillCompleteFna($mariaSubmitted, $completeness, $dime, $mariaApprentice);
            $mariaSubmitted = $reviews->submitForReview($mariaSubmitted->fresh(), $mariaApprentice);
            $this->markDemo($mariaSubmitted);
        }

        $this->logDemoGuide($maya, $celeste, $mariaApprentice, $mariaCfm);
    }

    private function clearPreviousDemoRecords(): void
    {
        FnaRecord::query()
            ->where('summary_notes', 'FnaDemoSeeder')
            ->orWhere('title', 'like', '[Demo]%')
            ->each(function (FnaRecord $fna): void {
                $fna->forceDelete();
            });

        Prospect::query()
            ->whereIn('email', ['demo.allen.fna@example.com', 'demo.brooks.fna@example.com'])
            ->forceDelete();
    }

    private function demoProspect(User $owner, string $firstName, string $lastName, string $email): Prospect
    {
        $stageId = DB::table('pipeline_stages')->where('slug', 'discovery-call')->value('id')
            ?? DB::table('pipeline_stages')->whereNull('user_id')->orderBy('sort_order')->value('id');

        return Prospect::updateOrCreate(
            ['owner_id' => $owner->id, 'email' => $email],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => '6045550199',
                'pipeline_stage_id' => $stageId,
                'status' => 'active',
                'interest_level' => 'warm',
                'priority' => 'medium',
                'fna_status' => 'not_started',
                'city' => 'Vancouver',
                'state_province' => 'BC',
                'country' => 'Canada',
            ],
        );
    }

    private function fillCompleteFna(
        FnaRecord $fna,
        FnaCompletenessService $completeness,
        DimeCalculatorService $dime,
        User $actor,
        bool $gapBoost = false,
    ): void {
        $fna->update([
            'client_email' => str(str($fna->client_name)->slug())->append('.demo@example.com')->toString(),
            'client_phone' => '6045550100',
            'occupation' => 'Software Engineer',
            'city' => 'Vancouver',
            'state_province' => 'BC',
            'country' => 'Canada',
            'main_needs_identified' => 'Income protection for young family',
            'recommended_next_action' => 'Schedule client FNA review meeting',
            'associate_recommendation' => 'Term life coverage aligned with DIME gap',
        ]);

        $fna->household()->update([
            'household_income' => 145000,
            'household_expenses' => 7200,
            'children_count' => 2,
        ]);
        $fna->incomeDetail()->update([
            'annual_income' => 95000,
            'monthly_income' => 7916,
        ]);
        $fna->debtDetail()->update([
            'mortgage_balance' => $gapBoost ? 420000 : 280000,
            'credit_card_debt' => 8000,
            'total_debt' => $gapBoost ? 450000 : 310000,
        ]);
        $fna->assetDetail()->update([
            'checking_savings' => 15000,
            'retirement_accounts' => 45000,
            'emergency_fund' => 10000,
        ]);
        $fna->existingCoverage()->update([
            'existing_life_insurance_amount' => 150000,
            'term_coverage' => 150000,
        ]);
        $fna->goals()->update([
            'selected_goals' => ['income_protection', 'mortgage_protection', 'education_funding'],
        ]);
        $fna->riskAssessment()->update([
            'main_financial_concern' => 'Replacing income if primary earner is unable to work',
            'urgency_level' => 'high',
            'risk_tolerance' => 'moderate',
        ]);

        $dimeInputs = [
            'credit_card_debt' => 8000,
            'personal_loans' => 5000,
            'car_loans' => 12000,
            'final_expenses' => 15000,
            'income_annual_to_replace' => 95000,
            'income_years_to_replace' => 10,
            'mortgage_balance' => $gapBoost ? 420000 : 280000,
            'include_mortgage_payoff' => true,
            'education_children_count' => 2,
            'education_cost_per_child' => 100000,
            'education_years_to_college' => 10,
            'existing_life_insurance' => 150000,
            'liquid_assets_allocated' => 25000,
        ];

        $result = $dime->calculate($dimeInputs);
        $fna->dimeAnalysis()->update([
            'debt_inputs' => $result['debt_inputs'],
            'total_debt' => $result['total_debt'],
            'income_annual_to_replace' => $dimeInputs['income_annual_to_replace'],
            'income_years_to_replace' => $dimeInputs['income_years_to_replace'],
            'total_income_need' => $result['total_income_need'],
            'mortgage_balance' => $dimeInputs['mortgage_balance'],
            'include_mortgage_payoff' => true,
            'total_mortgage_need' => $result['total_mortgage_need'],
            'education_children_count' => 2,
            'total_education_need' => $result['total_education_need'],
            'total_dime_need' => $result['total_dime_need'],
            'existing_life_insurance' => $result['existing_life_insurance'],
            'liquid_assets_allocated' => $result['liquid_assets_allocated'],
            'estimated_protection_gap' => $result['estimated_protection_gap'],
            'recommended_coverage_min' => $result['recommended_coverage_min'],
            'recommended_coverage_max' => $result['recommended_coverage_max'],
            'calculated_at' => now(),
        ]);

        $fna->update([
            'dime_completed' => true,
            'protection_gap' => $result['estimated_protection_gap'],
            'recommended_coverage_min' => $result['recommended_coverage_min'],
            'recommended_coverage_max' => $result['recommended_coverage_max'],
            'completeness_score' => $completeness->score($fna->fresh()),
            'current_step' => 9,
        ]);

        app(FnaRecordService::class)->logActivity($fna->fresh(), $actor, 'dime_calculated', 'Demo DIME analysis seeded.');
    }

    private function markDemo(FnaRecord $fna, ?string $demoKey = null): void
    {
        $fna->update([
            'summary_notes' => 'FnaDemoSeeder',
        ]);
    }

    private function logDemoGuide(
        User $maya,
        User $celeste,
        ?User $mariaApprentice,
        ?User $mariaCfm,
    ): void {
        if (! $this->command) {
            return;
        }

        $count = FnaRecord::query()->where('summary_notes', 'FnaDemoSeeder')->count();

        $this->command->info("FnaDemoSeeder: {$count} demo FNA records created.");
        $this->command->line('  Trainee login: maya.fap@example.com / Password123');
        $this->command->line('  CFM login:     cfm@gwtrack.com / Password123');
        $this->command->line('  Dashboard:     /team/fna');
        $this->command->line('  CFM queue:     /team/fna/cfm/review-queue');
        $this->command->line('  Demo records:  filter My FNAs for titles starting with [Demo]');
        $this->command->line('  Statuses seeded: draft, ready_for_review, submitted_to_cfm, revision_requested, approved_by_cfm, scheduled_for_client_review');

        if ($mariaApprentice && $mariaCfm) {
            $this->command->line('  Alt trainee:   maria.apprentice1@example.com / Password123');
            $this->command->line('  Alt CFM:       maria.cfm@gwtrack.com / Password123');
        }
    }
}
