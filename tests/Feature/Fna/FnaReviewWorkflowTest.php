<?php

namespace Tests\Feature\Fna;

use App\Livewire\Fna\FnaReviewPanel;
use App\Livewire\Fna\FnaSubmitForReviewModal;
use App\Models\FnaRecord;
use App\Models\MentorAssignment;
use App\Models\Notification;
use App\Models\User;
use App\Services\Fna\FnaCompletenessService;
use App\Services\Fna\FnaRecordService;
use App\Services\Fna\FnaReviewService;
use App\Services\Fna\FnaWorkflowService;
use Database\Seeders\NotificationConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SystemTaskAssignorSeeder;
use Database\Seeders\TaskCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FnaReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

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

    protected function fillFnaForSubmission(FnaRecord $fna): void
    {
        $fna->update([
            'client_name' => 'Review Client',
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

        $fna->update(['completeness_score' => app(FnaCompletenessService::class)->score($fna->fresh())]);
    }

    public function test_full_submit_approve_workflow_with_notifications(): void
    {
        $this->seed([RolePermissionSeeder::class, NotificationConfigSeeder::class, TaskCategorySeeder::class, SystemTaskAssignorSeeder::class]);

        [$trainee, $cfm] = $this->createTraineeWithCfm();

        $fna = app(FnaRecordService::class)->create($trainee, ['client_name' => 'Workflow Client']);
        $this->fillFnaForSubmission($fna);

        $submitted = app(FnaReviewService::class)->submitForReview($fna->fresh(), $trainee);

        $this->assertSame('submitted_to_cfm', $submitted->status);
        $this->assertSame($cfm->id, $submitted->cfm_user_id);
        $this->assertNotNull($submitted->submitted_at);

        $this->assertTrue(
            Notification::query()
                ->where('notifiable_id', $cfm->id)
                ->where('data->trigger', 'fna_submitted')
                ->exists()
        );

        $approved = app(FnaReviewService::class)->approve(
            $submitted->fresh(),
            $cfm,
            'Great work — ready for client meeting.',
        );

        $this->assertSame('approved_by_cfm', $approved->status);
        $this->assertNotNull($approved->approved_at);

        $this->assertTrue(
            Notification::query()
                ->where('notifiable_id', $trainee->id)
                ->where('data->trigger', 'fna_approved')
                ->exists()
        );

        $this->assertDatabaseHas('fna_review_comments', [
            'fna_record_id' => $fna->id,
            'comment_type' => 'approval',
        ]);

        $task = \App\Models\TaskUser::query()->where('related_fna_id', $fna->id)->first();
        $this->assertNotNull($task);
        $this->assertSame(\App\Support\SystemTaskAssignor::USER_ID, $task->assignor_id);
        $this->assertSame('FNA', $task->taskCategory?->name);
    }

    public function test_cfm_can_request_revision_with_required_comment(): void
    {
        $this->seed([RolePermissionSeeder::class, NotificationConfigSeeder::class, TaskCategorySeeder::class, SystemTaskAssignorSeeder::class]);

        [$trainee, $cfm] = $this->createTraineeWithCfm();

        $fna = app(FnaRecordService::class)->create($trainee, ['client_name' => 'Revise Client']);
        $this->fillFnaForSubmission($fna);

        $submitted = app(FnaReviewService::class)->submitForReview($fna->fresh(), $trainee);

        $revised = app(FnaReviewService::class)->requestRevision(
            $submitted,
            $cfm,
            'Please add spouse income details and complete DIME notes.',
        );

        $this->assertSame('revision_requested', $revised->status);

        $this->assertTrue(
            Notification::query()
                ->where('notifiable_id', $trainee->id)
                ->where('data->trigger', 'fna_revision_requested')
                ->exists()
        );

        $this->assertDatabaseHas('fna_review_comments', [
            'fna_record_id' => $fna->id,
            'comment_type' => 'revision',
        ]);
    }

    public function test_submit_modal_rejects_without_cfm_assignment(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $trainee = User::factory()->create();
        $trainee->assignRole('associate');

        $fna = app(FnaRecordService::class)->create($trainee, ['client_name' => 'No CFM']);
        $this->fillFnaForSubmission($fna);

        Livewire::actingAs($trainee)
            ->test(FnaSubmitForReviewModal::class)
            ->dispatch('open-fna-submit-modal', fnaId: $fna->id)
            ->call('submit')
            ->assertSet('show', true)
            ->assertSee('No active CFM mentor assignment');
    }

    public function test_cfm_review_panel_approves_via_livewire(): void
    {
        $this->seed([RolePermissionSeeder::class, NotificationConfigSeeder::class, TaskCategorySeeder::class, SystemTaskAssignorSeeder::class]);

        [$trainee, $cfm] = $this->createTraineeWithCfm();

        $fna = app(FnaRecordService::class)->create($trainee, ['client_name' => 'Livewire Approve']);
        $this->fillFnaForSubmission($fna);

        $submitted = app(FnaReviewService::class)->submitForReview($fna->fresh(), $trainee);

        Livewire::actingAs($cfm)
            ->test(FnaReviewPanel::class, ['fna' => $submitted])
            ->set('comment', 'Approved for presentation.')
            ->call('approve')
            ->assertHasNoErrors()
            ->assertSee('FNA approved');

        $this->assertSame('approved_by_cfm', $submitted->fresh()->status);
    }

    public function test_status_history_records_full_workflow(): void
    {
        $this->seed([RolePermissionSeeder::class, NotificationConfigSeeder::class, TaskCategorySeeder::class, SystemTaskAssignorSeeder::class]);

        [$trainee, $cfm] = $this->createTraineeWithCfm();

        $fna = app(FnaRecordService::class)->create($trainee, ['client_name' => 'History Client']);
        $this->fillFnaForSubmission($fna);

        $workflow = app(FnaWorkflowService::class);
        $reviews = app(FnaReviewService::class);

        $fna = $workflow->transition($fna, $trainee, 'ready_for_review');
        $fna = $reviews->submitForReview($fna, $trainee);
        $fna = $reviews->approve($fna, $cfm, 'OK');

        $this->assertDatabaseHas('fna_status_histories', [
            'fna_record_id' => $fna->id,
            'to_status' => 'submitted_to_cfm',
        ]);

        $this->assertDatabaseHas('fna_status_histories', [
            'fna_record_id' => $fna->id,
            'to_status' => 'approved_by_cfm',
        ]);
    }
}
