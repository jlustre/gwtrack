<?php

namespace Tests\Feature;

use App\Models\TrainingPath;
use App\Models\User;
use App\Models\UserTrainingPathEnrollment;
use App\Services\Training\TrainingCoursePlayerService;
use App\Services\Training\TrainingPathService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrainingAcademySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrainingPathTest extends TestCase
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

    public function test_member_can_view_learning_paths_index(): void
    {
        $this->actingAs($this->user)
            ->get(route('training.paths.index'))
            ->assertOk()
            ->assertSee('Learning Paths')
            ->assertSee('New Associate Path');
    }

    public function test_member_can_view_path_detail_and_enroll(): void
    {
        $path = TrainingPath::query()->where('code', 'new-associate')->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('training.paths.show', $path))
            ->assertOk()
            ->assertSee($path->name)
            ->assertSee('Prospecting Fundamentals');

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Training\PathDetail::class, ['path' => $path])
            ->call('enroll');

        $this->assertDatabaseHas('user_training_path_enrollments', [
            'user_id' => $this->user->id,
            'training_path_id' => $path->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_path_progress_updates_when_courses_are_completed(): void
    {
        $path = TrainingPath::query()->where('code', 'licensing')->firstOrFail();
        app(TrainingPathService::class)->enroll($this->user, $path);

        $module = $path->modules()->published()->where('slug', 'compliance-foundations')->firstOrFail();
        $player = app(TrainingCoursePlayerService::class);

        foreach ($module->lessons as $lesson) {
            $player->markLessonComplete($this->user, $module->load('lessons'), $lesson);
        }

        $enrollment = UserTrainingPathEnrollment::query()
            ->where('user_id', $this->user->id)
            ->where('training_path_id', $path->id)
            ->firstOrFail();

        $this->assertGreaterThan(0, $enrollment->fresh()->progress_percent);
    }
}
