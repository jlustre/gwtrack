<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\MentorAssignment;
use App\Models\TrainingAssignment;
use App\Models\TrainingCertification;
use App\Models\TrainingModule;
use App\Models\User;
use App\Models\UserTrainingCertification;
use App\Services\Training\TrainingAssignmentService;
use App\Services\Training\TrainingCoursePlayerService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrainingAcademySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrainingCertificationAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(TrainingAcademySeeder::class);

        $this->member = User::factory()->create();
        $this->member->assignRole('member');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('super-admin');
    }

    public function test_admin_can_assign_course_with_due_date(): void
    {
        $module = TrainingModule::query()->where('slug', 'compliance-foundations')->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Training\AssignmentManager::class)
            ->set('userId', $this->member->id)
            ->set('moduleId', $module->id)
            ->set('dueAt', now()->addDays(14)->format('Y-m-d'))
            ->set('notes', 'Complete before licensing review.')
            ->call('assign')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('training_assignments', [
            'user_id' => $this->member->id,
            'training_module_id' => $module->id,
            'status' => 'assigned',
            'notes' => 'Complete before licensing review.',
        ]);
    }

    public function test_member_can_view_assignments_page(): void
    {
        $module = TrainingModule::query()->where('slug', 'compliance-foundations')->firstOrFail();
        app(TrainingAssignmentService::class)->assign($this->member, $module, $this->admin, now()->addDays(7));

        $this->actingAs($this->member)
            ->get(route('training.assignments.index'))
            ->assertOk()
            ->assertSee('Compliance Foundations')
            ->assertSee('Due');
    }

    public function test_passing_assessment_auto_issues_certification(): void
    {
        $prospectingModule = TrainingModule::query()->where('slug', 'prospecting-fundamentals')->firstOrFail();
        $prospectingAssessment = Assessment::query()->where('training_module_id', $prospectingModule->id)->firstOrFail();
        $player = app(TrainingCoursePlayerService::class);

        foreach ($prospectingModule->lessons as $lesson) {
            $player->markLessonComplete($this->member, $prospectingModule->load('lessons'), $lesson);
        }

        $responses = [];
        foreach ($prospectingAssessment->questions()->with('answers')->get() as $question) {
            $responses[$question->id] = ['answer_id' => $question->answers->firstWhere('is_correct', true)->id];
        }

        Livewire::actingAs($this->member)
            ->test(\App\Livewire\Training\AssessmentTaker::class, ['assessment' => $prospectingAssessment])
            ->set('responses', $responses)
            ->call('submit');

        $this->assertDatabaseHas('user_training_certifications', [
            'user_id' => $this->member->id,
            'status' => 'issued',
        ]);

        $record = UserTrainingCertification::query()->where('user_id', $this->member->id)->firstOrFail();
        $this->assertNotNull($record->certificate_number);
    }

    public function test_mentor_approval_certification_stays_pending_until_approved(): void
    {
        $mentor = User::factory()->create();
        $mentor->assignRole('certified-field-mentor');

        MentorAssignment::query()->create([
            'mentor_id' => $mentor->id,
            'apprentice_id' => $this->member->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        $module = TrainingModule::query()->where('slug', 'leadership-essentials')->firstOrFail();
        $assessment = Assessment::query()->where('training_module_id', $module->id)->firstOrFail();
        $player = app(TrainingCoursePlayerService::class);

        foreach ($module->lessons as $lesson) {
            $player->markLessonComplete($this->member, $module->load('lessons'), $lesson);
        }

        $responses = [];
        foreach ($assessment->questions()->with('answers')->get() as $question) {
            $responses[$question->id] = ['answer_id' => $question->answers->firstWhere('is_correct', true)->id];
        }

        Livewire::actingAs($this->member)
            ->test(\App\Livewire\Training\AssessmentTaker::class, ['assessment' => $assessment])
            ->set('responses', $responses)
            ->call('submit');

        $record = UserTrainingCertification::query()
            ->where('user_id', $this->member->id)
            ->whereHas('certification', fn ($query) => $query->where('code', 'leadership-certification'))
            ->firstOrFail();

        $this->assertSame('pending', $record->status);
        $this->assertNull($record->certificate_number);

        Livewire::actingAs($mentor)
            ->test(\App\Livewire\Training\CertificationReviews::class)
            ->call('approve', $record->id);

        $record->refresh();
        $this->assertSame('issued', $record->status);
        $this->assertNotNull($record->certificate_number);
        $this->assertSame($mentor->id, $record->approved_by);
    }

    public function test_overdue_assignment_is_flagged_on_index(): void
    {
        $module = TrainingModule::query()->where('slug', 'compliance-foundations')->firstOrFail();

        TrainingAssignment::query()->create([
            'user_id' => $this->member->id,
            'training_module_id' => $module->id,
            'assigned_by' => $this->admin->id,
            'status' => 'assigned',
            'due_at' => now()->subDay(),
        ]);

        $this->actingAs($this->member)
            ->get(route('training.assignments.index'))
            ->assertOk()
            ->assertSee('Overdue');
    }
}
