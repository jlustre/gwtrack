<?php

namespace Tests\Feature;

use App\Models\TrainingAssignment;
use App\Models\TrainingCategory;
use App\Models\TrainingLesson;
use App\Models\TrainingModule;
use App\Models\User;
use App\Services\Training\TrainingCoursePlayerService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrainingAcademySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrainingCoursePlayerTest extends TestCase
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

    public function test_member_can_view_course_outline(): void
    {
        $module = TrainingModule::query()->where('slug', 'prospecting-fundamentals')->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('training.courses.show', $module))
            ->assertOk()
            ->assertSee($module->title)
            ->assertSee('Welcome to Prospecting')
            ->assertSee('Daily Activity Planning');
    }

    public function test_member_can_view_first_lesson_and_mark_complete(): void
    {
        $module = TrainingModule::query()->where('slug', 'prospecting-fundamentals')->firstOrFail();
        $lesson = $module->lessons()->orderBy('sort_order')->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('training.lessons.show', [$module, $lesson]))
            ->assertOk()
            ->assertSee($lesson->title);

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Training\LessonPlayer::class, [
                'module' => $module,
                'lesson' => $lesson,
            ])
            ->call('markComplete')
            ->assertRedirect(route('training.lessons.show', [$module, $module->lessons()->orderBy('sort_order')->skip(1)->firstOrFail()]));

        $this->assertDatabaseHas('training_progress', [
            'user_id' => $this->user->id,
            'training_lesson_id' => $lesson->id,
            'status' => 'completed',
        ]);
    }

    public function test_sequential_lock_blocks_second_lesson_until_first_is_complete(): void
    {
        $module = TrainingModule::query()->where('slug', 'prospecting-fundamentals')->firstOrFail();
        $lessons = $module->lessons()->orderBy('sort_order')->get();
        $secondLesson = $lessons[1];

        $this->actingAs($this->user)
            ->get(route('training.lessons.show', [$module, $secondLesson]))
            ->assertForbidden();
    }

    public function test_drip_lock_blocks_lessons_until_scheduled_day(): void
    {
        Carbon::setTestNow('2026-06-17 10:00:00');

        $category = TrainingCategory::query()->firstOrFail();
        $module = TrainingModule::query()->create([
            'training_category_id' => $category->id,
            'title' => 'Drip Demo Course',
            'slug' => 'drip-demo-course',
            'description' => 'Drip scheduling test course.',
            'sort_order' => 999,
            'is_published' => true,
            'status' => 'published',
            'course_type' => 'video',
            'difficulty' => 'beginner',
            'sequential_required' => false,
            'drip_enabled' => true,
        ]);

        TrainingLesson::query()->create([
            'training_module_id' => $module->id,
            'title' => 'Drip Lesson One',
            'lesson_type' => 'document',
            'sort_order' => 10,
            'is_required' => true,
            'content' => 'Day zero lesson.',
        ]);

        $secondLesson = TrainingLesson::query()->create([
            'training_module_id' => $module->id,
            'title' => 'Drip Lesson Two',
            'lesson_type' => 'document',
            'sort_order' => 20,
            'is_required' => true,
            'content' => 'Day one lesson.',
        ]);

        TrainingAssignment::query()->create([
            'user_id' => $this->user->id,
            'training_module_id' => $module->id,
            'status' => 'in_progress',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->user)
            ->get(route('training.lessons.show', [$module, $secondLesson]))
            ->assertForbidden();

        Carbon::setTestNow('2026-06-18 10:00:00');

        $this->actingAs($this->user)
            ->get(route('training.lessons.show', [$module, $secondLesson]))
            ->assertOk()
            ->assertSee($secondLesson->title);

        Carbon::setTestNow();
    }

    public function test_course_completion_syncs_assignment_status(): void
    {
        $module = TrainingModule::query()->where('slug', 'compliance-foundations')->firstOrFail();
        $lesson = $module->lessons()->firstOrFail();
        $player = app(TrainingCoursePlayerService::class);

        $player->markLessonComplete($this->user, $module->load('lessons'), $lesson);

        $this->assertDatabaseHas('training_assignments', [
            'user_id' => $this->user->id,
            'training_module_id' => $module->id,
            'status' => 'completed',
        ]);
    }
}
