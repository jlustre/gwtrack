<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\TrainingModule;
use App\Models\User;
use App\Services\Training\TrainingCoursePlayerService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrainingAcademySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrainingAssessmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(TrainingAcademySeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('member');
    }

    public function test_member_can_view_assessments_index(): void
    {
        $this->actingAs($this->user)
            ->get(route('assessments.index'))
            ->assertOk()
            ->assertSee('Assessments')
            ->assertSee('Prospecting Fundamentals Assessment');
    }

    public function test_course_completion_is_required_before_taking_assessment(): void
    {
        $module = TrainingModule::query()->where('slug', 'prospecting-fundamentals')->firstOrFail();
        $assessment = Assessment::query()->where('training_module_id', $module->id)->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('assessments.take', $assessment))
            ->assertForbidden();

        $this->actingAs($this->user)
            ->get(route('assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Complete all lessons');
    }

    public function test_member_can_submit_assessment_after_completing_course(): void
    {
        $module = TrainingModule::query()->where('slug', 'compliance-foundations')->firstOrFail();
        $assessment = Assessment::query()->where('training_module_id', $module->id)->firstOrFail();
        $player = app(TrainingCoursePlayerService::class);

        foreach ($module->lessons as $lesson) {
            $player->markLessonComplete($this->user, $module->load('lessons'), $lesson);
        }

        $questions = $assessment->questions()->with('answers')->orderBy('sort_order')->get();
        $responses = [];

        foreach ($questions as $question) {
            $correct = $question->answers->firstWhere('is_correct', true);
            $responses[$question->id] = ['answer_id' => $correct->id];
        }

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Training\AssessmentTaker::class, ['assessment' => $assessment])
            ->set('responses', $responses)
            ->call('submit')
            ->assertRedirect();

        $this->assertDatabaseHas('assessment_attempts', [
            'user_id' => $this->user->id,
            'assessment_id' => $assessment->id,
            'score' => 100,
            'passed' => true,
        ]);
    }

    public function test_passed_assessment_cannot_be_retaken_by_default(): void
    {
        $module = TrainingModule::query()->where('slug', 'compliance-foundations')->firstOrFail();
        $assessment = Assessment::query()->where('training_module_id', $module->id)->firstOrFail();
        $player = app(TrainingCoursePlayerService::class);

        foreach ($module->lessons as $lesson) {
            $player->markLessonComplete($this->user, $module->load('lessons'), $lesson);
        }

        $questions = $assessment->questions()->with('answers')->orderBy('sort_order')->get();
        $responses = [];

        foreach ($questions as $question) {
            $correct = $question->answers->firstWhere('is_correct', true);
            $responses[$question->id] = ['answer_id' => $correct->id];
        }

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Training\AssessmentTaker::class, ['assessment' => $assessment])
            ->set('responses', $responses)
            ->call('submit');

        $this->actingAs($this->user)
            ->get(route('assessments.take', $assessment))
            ->assertForbidden();
    }
}
