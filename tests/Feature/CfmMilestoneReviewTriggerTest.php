<?php

namespace Tests\Feature;

use App\Models\Checklist;
use App\Models\ChecklistProgress;
use App\Models\CfmEffectiveness\CfmReview;
use App\Models\CfmPromotion;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Services\CfmAssignmentWorkflowService;
use App\Services\CfmEffectiveness\CfmMilestoneReviewTriggerService;
use App\Services\CfmPortal\CfmPromotionReadinessService;
use App\Services\ChecklistService;
use Database\Seeders\CfmEffectivenessSeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\EmailTemplateSeeder;
use Database\Seeders\NotificationModuleSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\StartsChecklistTypes;
use Tests\TestCase;

class CfmMilestoneReviewTriggerTest extends TestCase
{
    use RefreshDatabase;
    use StartsChecklistTypes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            NotificationModuleSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            CfmEffectivenessSeeder::class,
            EmailTemplateSeeder::class,
        ]);

        Artisan::call('migrate', ['--path' => 'database/migrations/0000_00_00_000089_add_mentor_feedback_requested_notification_trigger.php']);
    }

    public function test_assignment_activation_creates_eligible_day_milestone_reviews(): void
    {
        Queue::fake();

        [$cfm, $trainee, $assignment] = $this->activeAssignment(
            startedAt: now()->subDays(31)->toDateString(),
            status: 'pending',
        );

        app(CfmAssignmentWorkflowService::class)->activateAssignment(
            $assignment->fresh(['mentor', 'apprentice.sponsor', 'assignedBy']),
        );

        $this->assertDatabaseHas('cfm_reviews', [
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'trigger_type' => '30_day',
            'status' => 'pending',
        ]);

        $this->assertDatabaseMissing('cfm_reviews', [
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'trigger_type' => '60_day',
        ]);
    }

    public function test_fap_completion_triggers_milestone_review(): void
    {
        [$cfm, $trainee] = $this->activeAssignment();

        $this->completeChecklistType($trainee, 'fap');

        app(CfmMilestoneReviewTriggerService::class)->maybeTriggerChecklistCompletion($trainee->fresh(), 'fap');

        $this->assertDatabaseHas('cfm_reviews', [
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'trigger_type' => 'fap_completion',
            'status' => 'pending',
        ]);
    }

    public function test_checklist_confirmation_triggers_fap_review_when_type_is_complete(): void
    {
        Queue::fake();

        [$cfm, $trainee] = $this->activeAssignment();
        $this->startChecklistType($trainee, 'fap');

        $fapItems = Checklist::query()->forTypeCode('fap')->active()->get();
        $this->assertNotEmpty($fapItems);

        $lastItem = $fapItems->last();

        foreach ($fapItems->slice(0, -1) as $item) {
            ChecklistProgress::query()->updateOrCreate(
                [
                    'user_id' => $trainee->id,
                    'checklist_id' => $item->id,
                    'mentor_assignment_id' => null,
                ],
                [
                    'status' => 'completed',
                    'completed_at' => now(),
                ],
            );
        }

        $pending = ChecklistProgress::query()->updateOrCreate(
            [
                'user_id' => $trainee->id,
                'checklist_id' => $lastItem->id,
                'mentor_assignment_id' => null,
            ],
            [
                'status' => 'pending_confirmation',
                'submitted_at' => now(),
            ],
        );

        app(ChecklistService::class)->reviewUserProgress($cfm, $pending->id, 'confirmed');

        $this->assertDatabaseHas('cfm_reviews', [
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'trigger_type' => 'fap_completion',
            'status' => 'pending',
        ]);
    }

    public function test_promotion_nomination_triggers_milestone_review(): void
    {
        Queue::fake();

        [$cfm, $trainee, $assignment] = $this->activeAssignment();

        app(CfmMilestoneReviewTriggerService::class)->onPromotionNominated($assignment);

        $this->assertDatabaseHas('cfm_reviews', [
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'trigger_type' => 'promotion',
            'status' => 'pending',
        ]);
    }

    public function test_promotion_status_update_hooks_milestone_review(): void
    {
        Queue::fake();

        [$cfm, $trainee] = $this->activeAssignment();

        $promotion = CfmPromotion::query()->create([
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'readiness_percent' => 100,
            'status' => 'ready',
        ]);

        $this->assertDatabaseHas('mentor_assignments', [
            'mentor_id' => $cfm->id,
            'apprentice_id' => $trainee->id,
            'status' => 'active',
        ]);

        $promotion = app(CfmPromotionReadinessService::class)->updateStatus($cfm, $promotion, 'nominated');

        $this->assertSame('nominated', $promotion->status);

        $this->assertDatabaseHas('cfm_reviews', [
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'trigger_type' => 'promotion',
            'status' => 'pending',
        ]);
    }

    public function test_milestone_reviews_are_not_duplicated(): void
    {
        [$cfm, $trainee, $assignment] = $this->activeAssignment(
            startedAt: now()->subDays(31)->toDateString(),
        );

        $triggers = app(CfmMilestoneReviewTriggerService::class);

        $triggers->onAssignmentActivated($assignment->fresh(['mentor', 'apprentice']));
        $triggers->onAssignmentActivated($assignment->fresh(['mentor', 'apprentice']));

        $this->assertSame(
            1,
            CfmReview::query()
                ->where('cfm_id', $cfm->id)
                ->where('trainee_id', $trainee->id)
                ->where('trigger_type', '30_day')
                ->count(),
        );
    }

    /**
     * @return array{0: User, 1: User, 2: MentorAssignment}
     */
    private function activeAssignment(
        ?string $startedAt = null,
        string $status = 'active',
    ): array {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $trainee = User::factory()->create([
            'mentor_id' => $status === 'active' ? $cfm->id : null,
        ]);
        $trainee->assignRole('associate');

        $assignment = MentorAssignment::query()->create([
            'mentor_id' => $cfm->id,
            'apprentice_id' => $trainee->id,
            'assigned_by' => $cfm->id,
            'status' => $status,
            'started_at' => $startedAt ?? now()->toDateString(),
            'confirmed_at' => $status === 'active' ? now() : null,
        ]);

        return [$cfm, $trainee, $assignment];
    }
}
