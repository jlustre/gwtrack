<?php

namespace Tests\Feature\Prospects;

use App\Livewire\Prospects\ProspectAiCoach;
use App\Livewire\Prospects\ProspectAiCoachPanel;
use App\Models\Prospect;
use App\Models\ProspectAppointment;
use App\Models\ProspectFollowUp;
use App\Models\User;
use App\Services\Prospects\CrmRelationshipExpansionService;
use App\Services\Prospects\ProspectAiCoachService;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ProspectAiCoachTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
            CalendarModuleSeeder::class,
        ]);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('member');
    }

    public function test_ai_coach_page_renders_recommendations(): void
    {
        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        Prospect::create([
            'owner_id' => $this->owner->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Coach',
            'last_name' => 'Target',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
            'last_contacted_at' => now()->subDays(10),
        ]);

        $this->actingAs($this->owner)
            ->get(route('team.prospects.ai-coach'))
            ->assertOk()
            ->assertSee('AI Coach')
            ->assertSee('Read-only recommendations');

        Livewire::actingAs($this->owner)
            ->test(ProspectAiCoach::class)
            ->assertSee('Coach Target')
            ->assertSee('Prospect has had no contact for over 7 days');
    }

    public function test_ai_coach_panel_shows_top_suggestions_on_profile(): void
    {
        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $this->owner->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Panel',
            'last_name' => 'Prospect',
            'status' => 'active',
            'interest_level' => 'hot',
            'priority' => 'high',
            'last_activity_at' => now()->subDays(5),
            'last_contacted_at' => now()->subDays(5),
        ]);

        Livewire::actingAs($this->owner)
            ->test(ProspectAiCoachPanel::class, ['prospect' => $prospect])
            ->assertSee('AI Coach Suggestions')
            ->assertSee('Hot prospect has had no recent activity');
    }

    public function test_dashboard_includes_ai_coach_shortcut(): void
    {
        $this->actingAs($this->owner)
            ->get(route('team.prospects'))
            ->assertOk()
            ->assertSee('AI Coach');
    }

    public function test_phase_seven_ai_intelligence_scores_actions_summaries_and_drafts(): void
    {
        $funnelId = DB::table('prospect_funnels')->where('key', 'recruiting')->value('id');
        $stageId = DB::table('pipeline_stages')->where('slug', 'registration-link-sent')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $stageId,
            'funnel_type' => 'recruiting',
            'first_name' => 'Smart',
            'last_name' => 'Recruit',
            'email' => 'smart.recruit@example.com',
            'status' => 'active',
            'interest_level' => 'hot',
            'interest_score' => 9,
            'priority' => 'high',
            'qualification_traits' => [
                'business_minded' => true,
                'employed_full_time' => true,
                'legal_resident' => true,
            ],
            'last_contacted_at' => now()->subDay(),
            'last_activity_at' => now()->subDay(),
        ]);

        app(CrmRelationshipExpansionService::class)->syncProspect($prospect);

        ProspectAppointment::create([
            'prospect_id' => $prospect->id,
            'owner_id' => $this->owner->id,
            'scheduled_at' => now()->addDay(),
            'timezone' => 'America/Vancouver',
            'purpose' => 'Opportunity review',
            'status' => 'scheduled',
        ]);

        ProspectFollowUp::create([
            'prospect_id' => $prospect->id,
            'assigned_user_id' => $this->owner->id,
            'due_at' => now()->subDay(),
            'followup_type' => 'registration_check',
            'priority' => 'high',
            'status' => 'overdue',
            'notes' => 'Registration check is overdue.',
        ]);

        $intelligence = app(ProspectAiCoachService::class)->intelligenceForProspect($prospect->fresh(), $this->owner);

        $this->assertGreaterThanOrEqual(75, $intelligence['score']['lead_score']);
        $this->assertGreaterThanOrEqual(60, $intelligence['score']['recruiting_probability']);
        $this->assertSame('Complete overdue follow-up', $intelligence['next_best_action']['label']);
        $this->assertSame(1, $intelligence['meeting_summary']['upcoming']);
        $this->assertStringContainsString('quick call', $intelligence['drafts']['sms']);
        $this->assertSame('Strong', $intelligence['forecast']['label']);

        $prospect->refresh();
        $this->assertGreaterThanOrEqual(75, (int) $prospect->engagement_score);
        $this->assertGreaterThanOrEqual(60, $prospect->recruitingOpportunity->probability);

        Livewire::actingAs($this->owner)
            ->test(ProspectAiCoach::class)
            ->assertSee('Lead Scoring & Forecasting', false)
            ->assertSee('Smart Recruit')
            ->assertSee('Recruiting probability');

        Livewire::actingAs($this->owner)
            ->test(ProspectAiCoachPanel::class, ['prospect' => $prospect->fresh()])
            ->assertSee('AI Relationship Intelligence')
            ->assertSee('Next Best Action')
            ->assertSee('Draft Assistance')
            ->assertSee('Complete overdue follow-up');
    }
}
