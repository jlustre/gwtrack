<?php

namespace Tests\Feature\Fna;

use App\Livewire\Fna\FnaMeetingPrepPanel;
use App\Livewire\Fna\FnaSubmitForReviewModal;
use App\Models\FnaRecord;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\Fna\FnaAiAssistantService;
use App\Services\Fna\FnaRecordService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FnaAiAssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        config([
            'fna.ai.enabled' => true,
            'fna.ai.use_llm' => false,
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

    public function test_completeness_suggestions_returned_for_incomplete_fna(): void
    {
        [$trainee] = $this->createTraineeWithCfm();

        $fna = app(FnaRecordService::class)->create($trainee, ['client_name' => 'Incomplete Client']);
        $fna->debtDetail()->updateOrCreate([], ['credit_card_debt' => 8000, 'total_debt' => 8000]);

        $suggestions = app(FnaAiAssistantService::class)->completenessSuggestions($fna->fresh());

        $this->assertNotEmpty($suggestions);
        $this->assertTrue(
            collect($suggestions)->contains(fn (array $item): bool => str_contains($item['message'], 'DIME'))
        );
        $this->assertTrue(
            collect($suggestions)->contains(fn (array $item): bool => str_contains($item['message'], 'income'))
        );
    }

    public function test_protection_gap_summary_includes_gap_context_when_dime_completed(): void
    {
        [$trainee] = $this->createTraineeWithCfm();

        $fna = app(FnaRecordService::class)->create($trainee, [
            'client_name' => 'Gap Client',
            'recommended_next_action' => 'Schedule protection review',
        ]);

        $fna->update([
            'dime_completed' => true,
            'protection_gap' => 250000,
        ]);

        $fna->goals()->updateOrCreate([], ['selected_goals' => ['income_protection']]);
        $fna->riskAssessment()->updateOrCreate([], [
            'main_financial_concern' => 'Family income',
            'urgency_level' => 'high',
            'risk_tolerance' => 'moderate',
        ]);

        $fna->dimeAnalysis()->updateOrCreate([], [
            'total_dime_need' => 500000,
            'existing_life_insurance' => 100000,
            'liquid_assets_allocated' => 150000,
            'estimated_protection_gap' => 250000,
        ]);

        $summary = app(FnaAiAssistantService::class)->protectionGapSummary($fna->fresh(), $trainee);

        $this->assertNotNull($summary);
        $this->assertStringContainsString('protection gap', strtolower($summary));
        $this->assertStringContainsString('$250,000', $summary);
        $this->assertStringContainsString('Family income', $summary);
    }

    public function test_meeting_prep_points_for_approved_fna(): void
    {
        [$trainee] = $this->createTraineeWithCfm();

        $fna = app(FnaRecordService::class)->create($trainee, [
            'client_name' => 'Approved Client',
            'main_needs_identified' => 'Income replacement',
            'recommended_next_action' => 'Present term options',
        ]);

        $fna->update([
            'status' => 'approved_by_cfm',
            'dime_completed' => true,
            'protection_gap' => 180000,
        ]);

        $fna->goals()->updateOrCreate([], ['selected_goals' => ['income_protection']]);
        $fna->riskAssessment()->updateOrCreate([], [
            'main_financial_concern' => 'Mortgage protection',
            'urgency_level' => 'medium',
            'risk_tolerance' => 'moderate',
        ]);

        $points = app(FnaAiAssistantService::class)->meetingTalkingPoints($fna->fresh(), $trainee);

        $this->assertNotEmpty($points);
        $this->assertTrue(
            collect($points)->contains(fn (string $point): bool => str_contains($point, 'Income Protection'))
        );
        $this->assertTrue(
            collect($points)->contains(fn (string $point): bool => str_contains($point, 'Mortgage protection'))
        );
    }

    public function test_ai_disabled_via_config_returns_empty_state(): void
    {
        config(['fna.ai.enabled' => false]);

        [$trainee] = $this->createTraineeWithCfm();
        $fna = app(FnaRecordService::class)->create($trainee, ['client_name' => 'Disabled AI Client']);
        $fna->update([
            'status' => 'approved_by_cfm',
            'dime_completed' => true,
            'protection_gap' => 50000,
        ]);

        $ai = app(FnaAiAssistantService::class);

        $this->assertFalse($ai->isEnabled());
        $this->assertSame([], $ai->completenessSuggestions($fna));
        $this->assertNull($ai->protectionGapSummary($fna));
        $this->assertSame([], $ai->meetingTalkingPoints($fna));
    }

    public function test_compliance_notice_present_in_submit_modal_output(): void
    {
        [$trainee] = $this->createTraineeWithCfm();
        $fna = app(FnaRecordService::class)->create($trainee, ['client_name' => 'Modal Client']);

        Livewire::actingAs($trainee)
            ->test(FnaSubmitForReviewModal::class)
            ->dispatch('open-fna-submit-modal', fnaId: $fna->id)
            ->assertSee('AI Completeness Hints')
            ->assertSee('coaching and planning support only');
    }

    public function test_compliance_notice_present_in_meeting_prep_panel(): void
    {
        [$trainee] = $this->createTraineeWithCfm();

        $fna = app(FnaRecordService::class)->create($trainee, [
            'client_name' => 'Prep Client',
            'recommended_next_action' => 'Review coverage options',
        ]);

        $fna->update(['status' => 'approved_by_cfm']);

        $fna->goals()->updateOrCreate([], ['selected_goals' => ['mortgage_protection']]);
        $fna->riskAssessment()->updateOrCreate([], [
            'main_financial_concern' => 'Home payoff',
            'urgency_level' => 'high',
            'risk_tolerance' => 'conservative',
        ]);

        Livewire::actingAs($trainee)
            ->test(FnaMeetingPrepPanel::class, ['fna' => $fna->fresh()])
            ->assertSee('Client Meeting Preparation')
            ->assertSee('coaching and planning support only');
    }

    public function test_financial_details_masked_in_summary_when_policy_denies(): void
    {
        Role::findByName('associate')->revokePermissionTo('view fna financial details');

        [$trainee] = $this->createTraineeWithCfm();

        $fna = app(FnaRecordService::class)->create($trainee, ['client_name' => 'Masked Client']);
        $fna->update([
            'dime_completed' => true,
            'protection_gap' => 320000,
        ]);

        $fna->goals()->updateOrCreate([], ['selected_goals' => ['final_expense']]);
        $fna->riskAssessment()->updateOrCreate([], [
            'main_financial_concern' => 'Legacy planning',
            'urgency_level' => 'medium',
            'risk_tolerance' => 'moderate',
        ]);

        $fna->dimeAnalysis()->updateOrCreate([], [
            'total_dime_need' => 400000,
            'existing_life_insurance' => 50000,
            'liquid_assets_allocated' => 30000,
            'estimated_protection_gap' => 320000,
        ]);

        $summary = app(FnaAiAssistantService::class)->protectionGapSummary($fna->fresh(), $trainee);

        $this->assertNotNull($summary);
        $this->assertStringNotContainsString('$320,000', $summary);
        $this->assertStringNotContainsString('$400,000', $summary);
        $this->assertStringContainsString('without sharing specific dollar amounts', $summary);
    }
}
