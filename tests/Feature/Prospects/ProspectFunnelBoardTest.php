<?php

namespace Tests\Feature\Prospects;

use App\Livewire\Prospects\LogActivityModal;
use App\Livewire\Prospects\ProspectActivityLogForm;
use App\Livewire\Prospects\ProspectFunnelBoard;
use App\Models\Prospect;
use App\Models\User;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ProspectFunnelBoardTest extends TestCase
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
        ]);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('member');
    }

    public function test_pipeline_page_loads_kanban_board(): void
    {
        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $funnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');

        Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $stageId,
            'funnel_type' => 'insurance',
            'first_name' => 'Kanban',
            'last_name' => 'Test',
            'interest_level' => 'hot',
            'priority' => 'high',
        ]);

        $this->actingAs($this->owner)
            ->get(route('team.prospects.pipeline'))
            ->assertOk()
            ->assertSee('Sales Pipeline')
            ->assertSee('Kanban Test')
            ->assertSee('New Lead');
    }

    public function test_move_stage_via_livewire_updates_prospect_and_history(): void
    {
        $initialStageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $nextStageId = DB::table('pipeline_stages')->where('slug', 'contact-attempted')->value('id');
        $funnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $initialStageId,
            'funnel_type' => 'insurance',
            'first_name' => 'Move',
            'last_name' => 'Me',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        Livewire::actingAs($this->owner)
            ->test(ProspectFunnelBoard::class)
            ->call('moveProspect', $prospect->id, (int) $nextStageId)
            ->assertHasNoErrors();

        $prospect->refresh();

        $this->assertSame((int) $nextStageId, (int) $prospect->pipeline_stage_id);
        $this->assertDatabaseHas('prospect_stage_history', [
            'prospect_id' => $prospect->id,
            'from_stage_id' => $initialStageId,
            'to_stage_id' => $nextStageId,
            'changed_by' => $this->owner->id,
            'change_source' => 'kanban',
        ]);
    }

    public function test_user_cannot_move_another_users_prospect(): void
    {
        $otherUser = User::factory()->create();
        $otherUser->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $nextStageId = DB::table('pipeline_stages')->where('slug', 'contact-attempted')->value('id');
        $funnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $otherUser->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $stageId,
            'funnel_type' => 'insurance',
            'first_name' => 'Private',
            'last_name' => 'Prospect',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        Livewire::actingAs($this->owner)
            ->test(ProspectFunnelBoard::class)
            ->call('moveProspect', $prospect->id, (int) $nextStageId)
            ->assertForbidden();

        $this->assertSame((int) $stageId, (int) $prospect->fresh()->pipeline_stage_id);
    }

    public function test_log_activity_modal_can_be_opened_from_board_context(): void
    {
        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $funnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $stageId,
            'funnel_type' => 'insurance',
            'first_name' => 'Activity',
            'last_name' => 'Target',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        Livewire::actingAs($this->owner)
            ->test(LogActivityModal::class)
            ->dispatch('open-log-activity-modal', prospectId: $prospect->id, activityType: 'phone_call')
            ->assertSet('show', true);

        Livewire::actingAs($this->owner)
            ->test(ProspectActivityLogForm::class, [
                'prospectId' => (string) $prospect->id,
                'initialActivityType' => 'phone_call',
                'embedded' => true,
            ])
            ->set('state.activity_occurred_at', now()->format('Y-m-d\TH:i'))
            ->set('state.activity_outcome', 'Connected')
            ->set('state.activity_notes', 'Discussed coverage.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('prospect_activities', [
            'prospect_id' => $prospect->id,
            'user_id' => $this->owner->id,
            'activity_type' => 'phone_call',
            'outcome' => 'Connected',
        ]);
    }

    public function test_stage_change_automation_creates_follow_up(): void
    {
        $initialStageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $applicationStageId = DB::table('pipeline_stages')->where('slug', 'application-submitted')->value('id');
        $funnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $initialStageId,
            'funnel_type' => 'insurance',
            'first_name' => 'Auto',
            'last_name' => 'FollowUp',
            'interest_level' => 'hot',
            'priority' => 'high',
        ]);

        Livewire::actingAs($this->owner)
            ->test(ProspectFunnelBoard::class)
            ->call('moveProspect', $prospect->id, (int) $applicationStageId);

        $this->assertDatabaseHas('prospect_followups', [
            'prospect_id' => $prospect->id,
            'assigned_user_id' => $this->owner->id,
            'followup_type' => 'underwriting_check',
            'priority' => 'high',
            'status' => 'pending',
        ]);
    }

    public function test_stage_info_modal_shows_detailed_stage_content(): void
    {
        $funnelStageId = DB::table('prospect_funnel_stages')
            ->where('slug', 'new-lead')
            ->whereIn('prospect_funnel_id', DB::table('prospect_funnels')->where('key', 'insurance')->pluck('id'))
            ->value('id');

        Livewire::actingAs($this->owner)
            ->test(ProspectFunnelBoard::class)
            ->call('openStageInfo', (int) $funnelStageId)
            ->assertSet('showStageInfoModal', true)
            ->assertSet('stageInfo.title', 'New Lead')
            ->assertSee('Overview')
            ->assertSee('A new prospect has entered your pipeline but has not yet been contacted.', false)
            ->assertSee('When to advance')
            ->call('closeStageInfo')
            ->assertSet('showStageInfoModal', false)
            ->assertSet('stageInfo', []);
    }
}
