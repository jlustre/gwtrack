<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CfmMentorProfile;
use App\Models\CfmRankTier;
use App\Models\CfmRecommendationSuggestion;
use App\Models\MentorAssignment;
use App\Models\User;
use App\Support\LocationOptions;
use Database\Seeders\CfmManagementSeeder;
use Database\Seeders\ChecklistSeeder;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TaskScenarioSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CfmManagementSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_cfm_management_seeder_populates_database_backed_demo_data(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ChecklistTypeSeeder::class,
            ChecklistSeeder::class,
            TaskScenarioSeeder::class,
            CfmManagementSeeder::class,
        ]);

        $agencyOwner = User::where('email', 'agency-owner@gwtrack.com')->firstOrFail();
        $celeste = User::where('email', 'cfm@gwtrack.com')->firstOrFail();
        $maria = User::where('email', 'maria.cfm@gwtrack.com')->firstOrFail();
        $john = User::where('email', 'john.cfm@gwtrack.com')->firstOrFail();
        $david = User::where('email', 'external.cfm@gwtrack.com')->firstOrFail();
        $queueOntario = User::where('email', 'fap.queue1@example.com')->firstOrFail();
        $queueManitoba = User::where('email', 'fap.queue4@example.com')->firstOrFail();
        $queueBc = User::where('email', 'fap.queue5@example.com')->firstOrFail();

        $james = User::where('email', 'james.cfm@gwtrack.com')->firstOrFail();
        $lisa = User::where('email', 'lisa.cfm@gwtrack.com')->firstOrFail();
        $queueUsCa = User::where('email', 'fap.queue.us-ca@example.com')->firstOrFail();
        $queueUsWa = User::where('email', 'fap.queue.us-wa@example.com')->firstOrFail();

        $this->assertGreaterThanOrEqual(6, CfmMentorProfile::count());
        $this->assertGreaterThanOrEqual(5, CfmRecommendationSuggestion::count());
        $this->assertGreaterThanOrEqual(10, Booking::count());
        $this->assertGreaterThanOrEqual(15, MentorAssignment::count());

        $this->assertDatabaseHas('cfm_recommendation_suggestions', [
            'cfm_user_id' => $maria->id,
            'status_label' => 'Recommended',
        ]);

        $this->assertSame(9, User::where('mentor_id', $david->id)->count());

        $celeste->load('cfmMentorProfile');
        $maria->load('cfmMentorProfile');
        $john->load('cfmMentorProfile');
        $david->load('cfmMentorProfile');
        $james->load('cfmMentorProfile');
        $lisa->load('cfmMentorProfile');

        $this->assertContains('Canada|Ontario', $celeste->cfmMentorProfile?->licensed_jurisdictions ?? []);
        $this->assertContains('United States|California', $james->cfmMentorProfile?->licensed_jurisdictions ?? []);
        $this->assertContains('United States|Florida', $lisa->cfmMentorProfile?->licensed_jurisdictions ?? []);
        $this->assertContains('Canada|Quebec', $maria->cfmMentorProfile?->licensed_jurisdictions ?? []);
        $this->assertContains('Canada|Alberta', $john->cfmMentorProfile?->licensed_jurisdictions ?? []);
        $this->assertSame(['Canada|British Columbia'], $david->cfmMentorProfile?->licensed_jurisdictions);

        $queueOntario->load('profile');
        $this->assertTrue(LocationOptions::cfmCoversJurisdiction(
            $maria->cfmMentorProfile?->licensed_jurisdictions,
            $queueOntario->profile?->country,
            $queueOntario->profile?->province
        ));
        $this->assertFalse(LocationOptions::cfmCoversJurisdiction(
            $celeste->cfmMentorProfile?->licensed_jurisdictions,
            $queueManitoba->profile?->country,
            $queueManitoba->profile?->province
        ));

        $queueBc->load('profile');
        $this->assertTrue(LocationOptions::cfmCoversJurisdiction(
            $celeste->cfmMentorProfile?->licensed_jurisdictions,
            $queueBc->profile?->country,
            $queueBc->profile?->province
        ));
        $this->assertTrue(LocationOptions::cfmCoversJurisdiction(
            $david->cfmMentorProfile?->licensed_jurisdictions,
            $queueBc->profile?->country,
            $queueBc->profile?->province
        ));

        $queueUsCa->load('profile');
        $this->assertTrue(LocationOptions::cfmCoversJurisdiction(
            $james->cfmMentorProfile?->licensed_jurisdictions,
            $queueUsCa->profile?->country,
            $queueUsCa->profile?->province
        ));
        $this->assertFalse(LocationOptions::cfmCoversJurisdiction(
            $lisa->cfmMentorProfile?->licensed_jurisdictions,
            $queueUsCa->profile?->country,
            $queueUsCa->profile?->province
        ));
        $this->assertFalse(LocationOptions::cfmCoversJurisdiction(
            $james->cfmMentorProfile?->licensed_jurisdictions,
            $queueUsWa->profile?->country,
            $queueUsWa->profile?->province
        ));

        $this->assertSame(2, User::where('mentor_id', $james->id)->where('is_active', true)->count());
        $this->assertSame(2, User::where('mentor_id', $lisa->id)->where('is_active', true)->count());

        $this->assertGreaterThanOrEqual(8, CfmRankTier::count());

        $this->actingAs($agencyOwner)
            ->get(route('team.cfms'))
            ->assertOk()
            ->assertSee('Certified Field Mentors', false)
            ->assertSee('CFM Rank Structure &amp; Advancement Criteria', false)
            ->assertSee('Associate Mentor', false)
            ->assertSee('Hall of Fame', false)
            ->assertSee('View FAP Queue', false)
            ->assertSee('Add CFM', false)
            ->assertSee('Export Report', false)
            ->assertSee('FAP Assignment Queue', false)
            ->assertSee('Maria Santos', false)
            ->assertSee('John Reyes', false)
            ->assertSee('Celeste Navarro', false)
            ->assertSee('David Kim', false)
            ->assertSee('Recommended', false)
            ->assertSee('Use Caution', false)
            ->assertSee('Not Recommended', false)
            ->assertSee('Sofia Reyes', false)
            ->assertSee('FAP Queue — Ontario', false)
            ->assertSee('FAP Queue — Manitoba', false)
            ->assertSee('FAP Queue — BC', false)
            ->assertSee('Celeste, Maria', false)
            ->assertSee('James Whitfield', false)
            ->assertSee('Lisa Morgan', false)
            ->assertSee('FAP Queue — California', false)
            ->assertSee('FAP Queue — Washington', false)
            ->assertSee('Smart Recommendations', false)
            ->assertSee('ON:CAN', false);
    }
}
