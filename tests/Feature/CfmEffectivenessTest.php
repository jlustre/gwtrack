<?php

namespace Tests\Feature;

use App\Models\CfmEffectiveness\CfmFeedbackQuestion;
use App\Models\CfmEffectiveness\CfmReview;
use App\Models\MentorAssignment;
use App\Models\User;
use Database\Seeders\CfmEffectivenessSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CfmEffectivenessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CfmEffectivenessSeeder::class);
    }

    public function test_cfm_can_view_effectiveness_dashboard(): void
    {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $this->actingAs($cfm)
            ->get(route('cfm.effectiveness.index'))
            ->assertOk()
            ->assertSee('CFM Effectiveness Score')
            ->assertSee('Objective Performance Metrics');
    }

    public function test_trainee_can_submit_anonymous_mentor_feedback(): void
    {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $trainee = User::factory()->create(['mentor_id' => $cfm->id]);
        $trainee->assignRole('associate');

        $assignment = MentorAssignment::query()->create([
            'mentor_id' => $cfm->id,
            'apprentice_id' => $trainee->id,
            'assigned_by' => $cfm->id,
            'status' => 'active',
            'started_at' => now()->subDays(35),
        ]);

        $review = CfmReview::query()->create([
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'mentor_assignment_id' => $assignment->id,
            'trigger_type' => '30_day',
            'status' => 'pending',
            'due_at' => now()->addDays(7),
        ]);

        $questions = CfmFeedbackQuestion::query()->pluck('id');
        $ratings = $questions->mapWithKeys(fn (int $id) => [$id => 5])->all();

        $this->actingAs($trainee)
            ->get(route('cfm.effectiveness.reviews'))
            ->assertOk()
            ->assertSee('Pending Mentor Reviews');

        $this->actingAs($trainee)
            ->get(route('cfm.effectiveness.reviews.show', $review))
            ->assertOk()
            ->assertSee('Anonymous Mentor Feedback');

        \Livewire\Livewire::actingAs($trainee)
            ->test(\App\Livewire\CfmEffectiveness\CfmReviewForm::class, ['review' => $review])
            ->set('ratings', $ratings)
            ->set('helpedMost', 'Encouraging and responsive')
            ->set('improvements', 'More follow-ups on prospecting')
            ->call('submit')
            ->assertRedirect(route('cfm.effectiveness.reviews'));

        $this->assertDatabaseHas('cfm_reviews', [
            'id' => $review->id,
            'status' => 'submitted',
        ]);

        $this->assertDatabaseCount('cfm_feedback_responses', $questions->count());
    }

    public function test_agency_owner_can_access_evaluation_center(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('agency-owner');

        $this->actingAs($owner)
            ->get(route('cfm.effectiveness.evaluations'))
            ->assertOk()
            ->assertSee('Quarterly CFM Evaluation');
    }

    public function test_dashboard_profile_counts_use_database_outcomes(): void
    {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $trainee = User::factory()->create(['mentor_id' => $cfm->id]);
        $trainee->assignRole('associate');

        MentorAssignment::query()->create([
            'mentor_id' => $cfm->id,
            'apprentice_id' => $trainee->id,
            'assigned_by' => $cfm->id,
            'status' => 'active',
            'started_at' => now()->subDays(30)->toDateString(),
            'confirmed_at' => now()->subDays(29),
        ]);

        $dashboard = app(\App\Services\CfmEffectiveness\CfmEffectivenessDashboardService::class)
            ->dashboardFor($cfm, $cfm);

        $this->assertSame(0, $dashboard['cfm']['licensed_trainees']);
        $this->assertSame(0, $dashboard['cfm']['promoted_trainees']);
        $this->assertSame(1, $dashboard['cfm']['active_trainees']);
    }

    public function test_responsiveness_metric_returns_no_data_without_assignments(): void
    {
        $cfm = User::factory()->create();
        $cfm->assignRole('certified-field-mentor');

        $metrics = app(\App\Services\CfmEffectiveness\CfmEffectivenessMetricsService::class)
            ->calculateFor($cfm);

        $this->assertEquals(0, $metrics['responsiveness_score']['score']);
        $this->assertSame('No data', $metrics['responsiveness_score']['meta']['band']);
        $this->assertNull($metrics['responsiveness_score']['meta']['average_hours']);
    }
}
