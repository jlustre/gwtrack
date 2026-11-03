<?php

namespace Tests\Feature;

use App\Livewire\Admin\Cfm\CertificationReviewManager;
use App\Livewire\Cfm\CertificationApplication;
use App\Models\CfmCertificationRequest;
use App\Models\CfmMentorProfile;
use App\Models\Checklist;
use App\Models\ChecklistProgress;
use App\Models\ChecklistType;
use App\Models\MentorAssignment;
use App\Models\Rank;
use App\Models\User;
use App\Models\UserChecklistTypeStart;
use App\Services\CfmAssignmentWorkflowService;
use App\Services\CfmCertificationWorkflowService;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\NotificationModuleSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class CfmCertificationLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $candidate;

    private User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            NotificationModuleSeeder::class,
        ]);

        $this->candidate = User::factory()->create([
            'rank_id' => Rank::query()->where('code', 'ASC')->value('id'),
        ]);
        $this->candidate->assignRole('member');

        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole('super-admin');
    }

    public function test_cfm_application_requires_associate_rank_started_training_and_all_required_items(): void
    {
        $this->candidate->forceFill([
            'rank_id' => Rank::query()->where('code', 'TA')->value('id'),
        ])->save();
        $workflow = app(CfmCertificationWorkflowService::class);
        $this->assertFalse($workflow->eligibilityFor($this->candidate)['eligible']);

        $this->candidate->forceFill([
            'rank_id' => Rank::query()->where('code', 'ASC')->value('id'),
        ])->save();
        $this->assertFalse($workflow->eligibilityFor($this->candidate)['eligible']);

        $this->startCfmTraining();
        Livewire::actingAs($this->candidate)
            ->test(CertificationApplication::class)
            ->assertSee('0 of')
            ->assertSee('Complete all required CFM Training checklist items.')
            ->assertSee('Submit CFM certification request');
    }

    public function test_eligible_member_can_apply_and_authorized_reviewer_approves_and_grants_cfm_access(): void
    {
        $this->completeRequiredItems('cfm-training');
        Livewire::actingAs($this->candidate)
            ->test(CertificationApplication::class)
            ->set('requestNotes', 'I am ready to support new associates.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('submitted for review');

        $request = CfmCertificationRequest::query()->where('user_id', $this->candidate->id)->firstOrFail();
        $this->assertSame('pending', $request->status);
        $this->assertSame('self_service', $request->source);
        $this->assertFalse($this->candidate->fresh()->hasRole('certified-field-mentor'));

        Livewire::actingAs($this->reviewer)
            ->test(CertificationReviewManager::class)
            ->set("reviewNotes.{$request->id}", 'Approved after training review.')
            ->call('approve', $request->id)
            ->assertSee('CFM certification approved.');

        $this->assertSame('approved', $request->fresh()->status);
        $this->assertSame($this->reviewer->id, $request->fresh()->reviewed_by);
        $this->assertTrue($this->candidate->fresh()->hasRole('certified-field-mentor'));
        $this->assertSame(
            'certified',
            CfmMentorProfile::query()->where('user_id', $this->candidate->id)->value('certification_status')
        );
    }

    public function test_rejection_requires_feedback_and_member_may_resubmit_after_correction(): void
    {
        $this->completeRequiredItems('cfm-training');
        $request = app(CfmCertificationWorkflowService::class)->submit($this->candidate);

        Livewire::actingAs($this->reviewer)
            ->test(CertificationReviewManager::class)
            ->call('reject', $request->id)
            ->assertHasErrors(["reviewNotes.{$request->id}" => 'required']);

        Livewire::actingAs($this->reviewer)
            ->test(CertificationReviewManager::class)
            ->set("reviewNotes.{$request->id}", 'Please add mentoring experience details.')
            ->call('reject', $request->id)
            ->assertHasNoErrors();

        $this->assertSame('rejected', $request->fresh()->status);
        $this->assertSame('Please add mentoring experience details.', $request->fresh()->review_notes);
        $this->assertFalse($this->candidate->fresh()->hasRole('certified-field-mentor'));

        $resubmission = app(CfmCertificationWorkflowService::class)->submit($this->candidate, 'Updated application.');
        $this->assertNotSame($request->id, $resubmission->id);
        $this->assertSame('pending', $resubmission->status);
    }

    public function test_cfm_access_is_not_granted_if_rank_or_training_changes_before_approval(): void
    {
        $this->completeRequiredItems('cfm-training');
        $request = app(CfmCertificationWorkflowService::class)->submit($this->candidate);
        $this->candidate->forceFill([
            'rank_id' => Rank::query()->where('code', 'TA')->value('id'),
        ])->save();

        try {
            app(CfmCertificationWorkflowService::class)->approve($request, $this->reviewer);
            $this->fail('Approval should recheck CFM eligibility.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('application', $exception->errors());
        }

        $this->assertSame('pending', $request->fresh()->status);
        $this->assertFalse($this->candidate->fresh()->hasRole('certified-field-mentor'));
    }

    public function test_mentor_assignment_can_complete_after_fap_or_end_early_with_history(): void
    {
        $mentor = User::factory()->create();
        $mentor->assignRole('certified-field-mentor');
        $this->candidate->forceFill(['mentor_id' => $mentor->id])->save();
        $assignment = MentorAssignment::query()->create([
            'mentor_id' => $mentor->id,
            'apprentice_id' => $this->candidate->id,
            'assigned_by' => $this->reviewer->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
            'confirmed_at' => now(),
        ]);

        try {
            app(CfmAssignmentWorkflowService::class)->completeAssignment($assignment, $mentor, 'Ready for next stage.');
            $this->fail('Completion should require all required FAP checklist items.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('assignment', $exception->errors());
        }

        $this->completeRequiredItems('fap');
        $completed = app(CfmAssignmentWorkflowService::class)->completeAssignment(
            $assignment->fresh(),
            $mentor,
            'FAP milestones confirmed.',
        );
        $this->assertSame('completed', $completed->status);
        $this->assertNull($this->candidate->fresh()->mentor_id);
        $this->assertDatabaseHas('mentor_assignment_events', [
            'mentor_assignment_id' => $assignment->id,
            'event_type' => 'assignment_completed',
            'actor_id' => $mentor->id,
            'notes' => 'FAP milestones confirmed.',
        ]);

        $secondAssignment = MentorAssignment::query()->create([
            'mentor_id' => $mentor->id,
            'apprentice_id' => $this->candidate->id,
            'assigned_by' => $this->reviewer->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
            'confirmed_at' => now(),
        ]);
        $this->candidate->forceFill(['mentor_id' => $mentor->id])->save();

        $ended = app(CfmAssignmentWorkflowService::class)->endAssignment(
            $secondAssignment,
            $mentor,
            'The associate requested a different mentor.',
        );
        $this->assertSame('cancelled', $ended->status);
        $this->assertNull($this->candidate->fresh()->mentor_id);
        $this->assertDatabaseHas('mentor_assignment_events', [
            'mentor_assignment_id' => $secondAssignment->id,
            'event_type' => 'assignment_ended',
        ]);
    }

    public function test_cfm_can_decline_a_pending_assignment_with_a_required_reason(): void
    {
        $mentor = User::factory()->create();
        $mentor->assignRole('certified-field-mentor');
        $assignment = MentorAssignment::query()->create([
            'mentor_id' => $mentor->id,
            'apprentice_id' => $this->candidate->id,
            'assigned_by' => $this->reviewer->id,
            'status' => 'pending',
            'started_at' => now()->toDateString(),
        ]);

        try {
            app(CfmAssignmentWorkflowService::class)->declineAssignment($assignment, $mentor, 'No.');
            $this->fail('A decline reason should have a minimum length.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reason', $exception->errors());
        }

        $declined = app(CfmAssignmentWorkflowService::class)->declineAssignment(
            $assignment,
            $mentor,
            'I am currently at mentor capacity.',
        );

        $this->assertSame('declined', $declined->status);
        $this->assertNull($this->candidate->fresh()->mentor_id);
        $this->assertDatabaseHas('mentor_assignment_events', [
            'mentor_assignment_id' => $assignment->id,
            'event_type' => 'assignment_declined',
            'actor_id' => $mentor->id,
            'notes' => 'I am currently at mentor capacity.',
        ]);
    }

    public function test_workflow_services_enforce_note_limits_without_form_validation(): void
    {
        $this->completeRequiredItems('cfm-training');

        try {
            app(CfmCertificationWorkflowService::class)->submit($this->candidate, str_repeat('x', 2001));
            $this->fail('Certification notes should have a maximum length.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('requestNotes', $exception->errors());
        }

        $mentor = User::factory()->create();
        $mentor->assignRole('certified-field-mentor');
        $assignment = MentorAssignment::query()->create([
            'mentor_id' => $mentor->id,
            'apprentice_id' => $this->candidate->id,
            'assigned_by' => $this->reviewer->id,
            'status' => 'pending',
            'started_at' => now()->toDateString(),
        ]);

        try {
            app(CfmAssignmentWorkflowService::class)->declineAssignment(
                $assignment,
                $mentor,
                str_repeat('x', 2001),
            );
            $this->fail('Assignment reasons should have a maximum length.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('reason', $exception->errors());
        }
    }

    public function test_cfm_certification_review_route_is_a_working_admin_screen(): void
    {
        $this->actingAs($this->reviewer)
            ->get(route('admin.cfm.index'))
            ->assertOk()
            ->assertSee('CFM Certification Review');
    }

    private function startCfmTraining(): void
    {
        UserChecklistTypeStart::query()->create([
            'user_id' => $this->candidate->id,
            'checklist_type_id' => ChecklistType::query()->where('code', 'cfm-training')->value('id'),
            'started_at' => now()->toDateString(),
            'started_by' => $this->candidate->id,
        ]);
    }

    private function completeRequiredItems(string $typeCode): void
    {
        if ($typeCode === 'cfm-training') {
            $this->startCfmTraining();
        }

        $checklists = Checklist::query()
            ->forTypeCode($typeCode)
            ->active()
            ->where('is_required', true)
            ->get();

        foreach ($checklists as $checklist) {
            ChecklistProgress::query()->updateOrCreate(
                [
                    'user_id' => $this->candidate->id,
                    'checklist_id' => $checklist->id,
                    'mentor_assignment_id' => null,
                ],
                [
                    'status' => 'completed',
                    'completed_at' => now(),
                    'completed_by' => $this->candidate->id,
                ],
            );
        }
    }
}
