<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\TrainingBadge;
use App\Models\TrainingModule;
use App\Models\User;
use App\Models\UserTrainingBadge;
use App\Models\UserTrainingGamificationProfile;
use App\Services\Training\TrainingAssessmentService;
use App\Services\Training\TrainingCoursePlayerService;
use App\Services\Training\TrainingGamificationService;
use Carbon\Carbon;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrainingAcademySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrainingGamificationTest extends TestCase
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

    public function test_lesson_completion_awards_points_and_updates_streak(): void
    {
        Carbon::setTestNow('2026-06-17 10:00:00');

        $module = TrainingModule::query()->where('slug', 'compliance-foundations')->firstOrFail();
        $lesson = $module->lessons()->firstOrFail();

        app(TrainingCoursePlayerService::class)->markLessonComplete(
            $this->user,
            $module->load('lessons'),
            $lesson,
        );

        $profile = UserTrainingGamificationProfile::query()->where('user_id', $this->user->id)->first();

        $this->assertNotNull($profile);
        $this->assertSame(1, $profile->current_streak);
        $this->assertSame(1, $profile->lessons_completed_total);
        $this->assertGreaterThanOrEqual(1, $profile->total_points);

        Carbon::setTestNow('2026-06-18 10:00:00');

        $secondModule = TrainingModule::query()->where('slug', 'prospecting-fundamentals')->firstOrFail();
        $secondLesson = $secondModule->lessons()->orderBy('sort_order')->firstOrFail();

        app(TrainingCoursePlayerService::class)->markLessonComplete(
            $this->user,
            $secondModule->load('lessons'),
            $secondLesson,
        );

        $profile->refresh();

        $this->assertSame(2, $profile->current_streak);

        Carbon::setTestNow();
    }

    public function test_course_completion_awards_first_course_badge(): void
    {
        $module = TrainingModule::query()->where('slug', 'compliance-foundations')->firstOrFail();
        $lesson = $module->lessons()->firstOrFail();

        app(TrainingCoursePlayerService::class)->markLessonComplete(
            $this->user,
            $module->load('lessons'),
            $lesson,
        );

        $badge = TrainingBadge::query()->where('code', 'first-course')->firstOrFail();

        $this->assertDatabaseHas('user_training_badges', [
            'user_id' => $this->user->id,
            'training_badge_id' => $badge->id,
        ]);
    }

    public function test_member_can_view_achievements_hub(): void
    {
        $module = TrainingModule::query()->where('slug', 'compliance-foundations')->firstOrFail();
        $lesson = $module->lessons()->firstOrFail();

        app(TrainingCoursePlayerService::class)->markLessonComplete(
            $this->user,
            $module->load('lessons'),
            $lesson,
        );

        $this->actingAs($this->user)
            ->get(route('training.achievements.index'))
            ->assertOk()
            ->assertSeeText('Achievements & Leaderboard')
            ->assertSee('First Course Completed');

        Livewire::actingAs($this->user)
            ->test(\App\Livewire\Training\AchievementsHub::class)
            ->assertSee('Academy Points')
            ->assertSee('Your Badges');
    }

    public function test_dashboard_shows_gamification_summary(): void
    {
        $module = TrainingModule::query()->where('slug', 'compliance-foundations')->firstOrFail();
        $lesson = $module->lessons()->firstOrFail();

        app(TrainingCoursePlayerService::class)->markLessonComplete(
            $this->user,
            $module->load('lessons'),
            $lesson,
        );

        $this->actingAs($this->user)
            ->get(route('training.index'))
            ->assertOk()
            ->assertSee('Academy Points')
            ->assertSee('Learning Streak')
            ->assertSee('View achievements');
    }

    public function test_perfect_assessment_score_awards_assessment_ace_badge(): void
    {
        $module = TrainingModule::query()->where('slug', 'compliance-foundations')->firstOrFail();
        $assessment = Assessment::query()->where('training_module_id', $module->id)->firstOrFail();
        $player = app(TrainingCoursePlayerService::class);

        foreach ($module->lessons as $lesson) {
            $player->markLessonComplete($this->user, $module->load('lessons'), $lesson);
        }

        $responses = [];

        foreach ($assessment->questions()->with('answers')->orderBy('sort_order')->get() as $question) {
            $correct = $question->answers->firstWhere('is_correct', true);
            $responses[$question->id] = ['answer_id' => $correct->id];
        }

        app(TrainingAssessmentService::class)->submitAttempt($this->user, $assessment, $responses);

        $badge = TrainingBadge::query()->where('code', 'assessment-ace')->firstOrFail();

        $this->assertDatabaseHas('user_training_badges', [
            'user_id' => $this->user->id,
            'training_badge_id' => $badge->id,
        ]);
    }

    public function test_leaderboard_ranks_users_by_points(): void
    {
        $leader = User::factory()->create(['team_id' => $this->user->team_id]);
        $leader->assignRole('member');

        app(TrainingGamificationService::class)->profileFor($leader)->update([
            'total_points' => 120,
            'current_streak' => 5,
        ]);

        app(TrainingGamificationService::class)->profileFor($this->user)->update([
            'total_points' => 40,
            'current_streak' => 2,
        ]);

        $rows = app(TrainingGamificationService::class)->leaderboardRows('organization');

        $this->assertSame($leader->id, $rows[0]['user']->id);
        $this->assertSame(1, $rows[0]['rank']);
        $this->assertSame(120, $rows[0]['points']);
    }

    public function test_seven_day_streak_awards_streak_badge(): void
    {
        $gamification = app(TrainingGamificationService::class);

        foreach (range(0, 6) as $offset) {
            Carbon::setTestNow(Carbon::parse('2026-06-01')->addDays($offset)->setTime(10, 0));
            $gamification->recordLessonCompleted($this->user);
        }

        $badge = TrainingBadge::query()->where('code', 'learning-streak-7')->firstOrFail();

        $this->assertTrue(
            UserTrainingBadge::query()
                ->where('user_id', $this->user->id)
                ->where('training_badge_id', $badge->id)
                ->exists()
        );

        Carbon::setTestNow();
    }
}
