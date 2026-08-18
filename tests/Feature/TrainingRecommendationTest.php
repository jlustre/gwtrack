<?php

namespace Tests\Feature;

use App\Models\TrainingAssignment;
use App\Models\TrainingModule;
use App\Models\TrainingRecommendation;
use App\Models\User;
use App\Services\Training\TrainingCoursePlayerService;
use App\Services\Training\TrainingRecommendationService;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrainingAcademySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrainingRecommendationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(TrainingAcademySeeder::class);

        $this->user = User::factory()->create();
        $this->user->assignRole('member');
    }

    public function test_sync_suggests_role_based_learning_path_for_new_member(): void
    {
        app(TrainingRecommendationService::class)->syncForUser($this->user);

        $this->assertDatabaseHas('training_recommendations', [
            'user_id' => $this->user->id,
            'reason_code' => 'enroll_path',
        ]);
    }

    public function test_sync_recommends_continue_course_when_in_progress(): void
    {
        $module = TrainingModule::query()->where('slug', 'prospecting-fundamentals')->firstOrFail();
        $lesson = $module->lessons()->orderBy('sort_order')->firstOrFail();

        TrainingAssignment::query()->create([
            'user_id' => $this->user->id,
            'training_module_id' => $module->id,
            'status' => 'in_progress',
        ]);

        app(TrainingCoursePlayerService::class)->markLessonComplete(
            $this->user,
            $module->load('lessons'),
            $lesson,
        );

        $this->assertDatabaseHas('training_recommendations', [
            'user_id' => $this->user->id,
            'reason_code' => 'continue_course',
            'training_module_id' => $module->id,
        ]);
    }

    public function test_sync_recommends_assessment_after_course_completion(): void
    {
        $module = TrainingModule::query()->where('slug', 'compliance-foundations')->firstOrFail();
        $lesson = $module->lessons()->firstOrFail();

        app(TrainingCoursePlayerService::class)->markLessonComplete(
            $this->user,
            $module->load('lessons'),
            $lesson,
        );

        $this->assertDatabaseHas('training_recommendations', [
            'user_id' => $this->user->id,
            'reason_code' => 'assessment_ready',
            'training_module_id' => $module->id,
        ]);
    }

    public function test_member_can_view_learning_plan_page(): void
    {
        app(TrainingRecommendationService::class)->syncForUser($this->user);

        $this->actingAs($this->user)
            ->get(route('training.plan.index'))
            ->assertOk()
            ->assertSee('My Learning Plan')
            ->assertSee('Priority recommendations');

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Training\LearningPlan::class)
            ->assertSee('Enroll in');
    }

    public function test_member_can_dismiss_recommendation(): void
    {
        app(TrainingRecommendationService::class)->syncForUser($this->user);

        $recommendation = TrainingRecommendation::query()
            ->where('user_id', $this->user->id)
            ->whereNull('dismissed_at')
            ->firstOrFail();

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Training\LearningPlan::class)
            ->call('dismiss', $recommendation->id);

        $this->assertNotNull($recommendation->fresh()->dismissed_at);
    }

    public function test_dashboard_shows_actionable_recommendations(): void
    {
        app(TrainingRecommendationService::class)->syncForUser($this->user);

        $this->actingAs($this->user)
            ->get(route('training.index'))
            ->assertOk()
            ->assertSee('Recommended For You')
            ->assertSee('My learning plan');
    }
}
