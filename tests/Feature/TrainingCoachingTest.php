<?php

namespace Tests\Feature;

use App\Models\MentorAssignment;
use App\Models\TrainingSession;
use App\Models\User;
use App\Models\UserChecklistTypeStart;
use App\Services\Training\TrainingCoachingService;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrainingAcademySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrainingCoachingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(TrainingAcademySeeder::class);
    }

    public function test_member_can_view_coaching_center(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        UserChecklistTypeStart::query()->create([
            'user_id' => $user->id,
            'checklist_type_id' => \App\Models\ChecklistType::query()->where('code', 'fap')->value('id'),
            'started_at' => now()->toDateString(),
            'started_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('training.coaching.index'))
            ->assertOk()
            ->assertSee('Coaching Center', false);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Training\CoachingCenter::class)
            ->assertSee('My FAP Progress');
    }

    public function test_mentor_can_submit_coaching_review(): void
    {
        $mentor = User::factory()->create();
        $mentor->assignRole('certified-field-mentor');

        $trainee = User::factory()->create();
        $trainee->assignRole('member');

        MentorAssignment::query()->create([
            'mentor_id' => $mentor->id,
            'apprentice_id' => $trainee->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        Livewire::actingAs($mentor)
            ->test(\App\Livewire\Training\CoachingCenter::class)
            ->set('traineeId', $trainee->id)
            ->set('reviewType', 'coaching')
            ->set('score', 85)
            ->set('feedback', 'Strong week of field activity.')
            ->call('submitReview');

        $this->assertDatabaseHas('mentor_training_reviews', [
            'mentor_id' => $mentor->id,
            'trainee_id' => $trainee->id,
            'review_type' => 'coaching',
            'score' => 85,
        ]);
    }

    public function test_user_can_register_for_coaching_session(): void
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('certified-field-mentor');

        $user = User::factory()->create();
        $user->assignRole('member');

        $session = TrainingSession::query()->create([
            'title' => 'Test Coaching Session',
            'description' => 'Demo session',
            'session_type' => 'live',
            'instructor_id' => $instructor->id,
            'starts_at' => now()->addWeek(),
            'capacity' => 10,
            'is_active' => true,
        ]);

        app(TrainingCoachingService::class)->registerForSession($user, $session);

        $this->assertDatabaseHas('training_session_attendance', [
            'training_session_id' => $session->id,
            'user_id' => $user->id,
            'status' => 'registered',
        ]);
    }

    public function test_mentor_sign_off_fap_creates_approved_review(): void
    {
        $mentor = User::factory()->create();
        $mentor->assignRole('certified-field-mentor');

        $trainee = User::factory()->create();
        $trainee->assignRole('member');

        MentorAssignment::query()->create([
            'mentor_id' => $mentor->id,
            'apprentice_id' => $trainee->id,
            'status' => 'active',
            'started_at' => now()->toDateString(),
        ]);

        UserChecklistTypeStart::query()->create([
            'user_id' => $trainee->id,
            'checklist_type_id' => \App\Models\ChecklistType::query()->where('code', 'fap')->value('id'),
            'started_at' => now()->toDateString(),
            'started_by' => $trainee->id,
        ]);

        $this->mock(\App\Services\DashboardStatsService::class, function ($mock): void {
            $mock->shouldReceive('apprenticeshipPercent')->andReturn(95);
        });

        app(TrainingCoachingService::class)->signOffFap(
            $mentor,
            $trainee,
            'Ready for FAP graduation.',
        );

        $this->assertDatabaseHas('mentor_training_reviews', [
            'mentor_id' => $mentor->id,
            'trainee_id' => $trainee->id,
            'review_type' => 'fap_signoff',
            'status' => 'approved',
        ]);
    }
}
