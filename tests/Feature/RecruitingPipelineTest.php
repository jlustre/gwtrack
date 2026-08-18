<?php

namespace Tests\Feature;

use App\Livewire\Prospects\ProspectCreate;
use App\Livewire\Recruiting\RecruitingFunnelBoard;
use App\Livewire\Recruiting\RecruitingPipelineHub;
use App\Models\Prospect;
use App\Models\RegistrationInvitation;
use App\Models\User;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class RecruitingPipelineTest extends TestCase
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

    public function test_recruiting_pipeline_hub_renders_for_members(): void
    {
        $this->makeRecruitingProspect([
            'first_name' => 'Pipeline',
            'last_name' => 'Candidate',
        ]);

        $this->actingAs($this->owner)
            ->get(route('team.recruiting.index'))
            ->assertOk()
            ->assertSee('Recruiting Pipeline', false)
            ->assertSee('Pipeline Candidate', false)
            ->assertSee('Candidate queue', false)
            ->assertSee('Active recruit journey', false);

        Livewire::actingAs($this->owner)
            ->test(RecruitingPipelineHub::class)
            ->assertSee('Recruiting kanban', false);
    }

    public function test_recruiting_kanban_only_shows_recruiting_candidates(): void
    {
        $recruitingStageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $insuranceFunnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');
        $recruitingFunnelId = DB::table('prospect_funnels')->where('key', 'recruiting')->value('id');

        $this->makeRecruitingProspect([
            'first_name' => 'Recruit',
            'last_name' => 'Only',
            'pipeline_stage_id' => $recruitingStageId,
            'prospect_funnel_id' => $recruitingFunnelId,
        ]);

        Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => $insuranceFunnelId,
            'pipeline_stage_id' => $recruitingStageId,
            'funnel_type' => 'insurance',
            'first_name' => 'Sales',
            'last_name' => 'Lead',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        Livewire::actingAs($this->owner)
            ->test(RecruitingFunnelBoard::class)
            ->assertSee('Recruit Only', false)
            ->assertDontSee('Sales Lead', false);
    }

    public function test_dual_pipeline_prospect_appears_on_sales_and_recruiting_pages(): void
    {
        $insuranceStageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $recruitingStageId = DB::table('pipeline_stages')->where('slug', 'lead-generated')->value('id');
        $insuranceFunnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');
        $recruitingFunnelId = DB::table('prospect_funnels')->where('key', 'recruiting')->value('id');

        Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => $insuranceFunnelId,
            'pipeline_stage_id' => $insuranceStageId,
            'funnel_type' => 'both',
            'first_name' => 'Dual',
            'last_name' => 'Pipeline',
            'interest_level' => 'hot',
            'priority' => 'high',
            'status' => 'active',
        ]);

        $this->makeRecruitingProspect([
            'first_name' => 'Recruit',
            'last_name' => 'Only',
            'pipeline_stage_id' => $recruitingStageId,
            'prospect_funnel_id' => $recruitingFunnelId,
        ]);

        $this->actingAs($this->owner)
            ->get(route('team.prospects'))
            ->assertOk()
            ->assertSee('Dual Pipeline', false)
            ->assertSee('Sales', false)
            ->assertSee('Both', false)
            ->assertDontSee('Recruit Only', false);

        $this->actingAs($this->owner)
            ->get(route('team.recruiting.index'))
            ->assertOk()
            ->assertSee('Dual Pipeline', false)
            ->assertSee('Recruit Only', false);
    }

    public function test_recruiting_kanban_move_updates_stage_with_recruiting_source(): void
    {
        $initialStageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $nextStageId = DB::table('pipeline_stages')->where('slug', 'invitation-sent')->value('id');

        $prospect = $this->makeRecruitingProspect([
            'pipeline_stage_id' => $initialStageId,
        ]);

        Livewire::actingAs($this->owner)
            ->test(RecruitingFunnelBoard::class)
            ->call('moveProspect', $prospect->id, (int) $nextStageId)
            ->assertHasNoErrors();

        $prospect->refresh();

        $this->assertSame((int) $nextStageId, (int) $prospect->pipeline_stage_id);
        $this->assertDatabaseHas('prospect_stage_history', [
            'prospect_id' => $prospect->id,
            'from_stage_id' => $initialStageId,
            'to_stage_id' => $nextStageId,
            'changed_by' => $this->owner->id,
            'change_source' => 'recruiting_kanban',
        ]);
    }

    public function test_pending_invitations_appear_on_hub(): void
    {
        $prospect = $this->makeRecruitingProspect([
            'first_name' => 'Invite',
            'last_name' => 'Pending',
        ]);

        RegistrationInvitation::factory()->for($this->owner, 'sponsor')->create([
            'prospect_id' => $prospect->id,
            'email' => 'invite.pending@example.com',
        ]);

        Livewire::actingAs($this->owner)
            ->test(RecruitingPipelineHub::class)
            ->assertSee('Invite Pending', false)
            ->assertSee('invite.pending@example.com', false);
    }

    public function test_prospect_create_presets_recruiting_funnel_from_query(): void
    {
        Livewire::actingAs($this->owner)
            ->withQueryParams(['funnel_type' => 'recruiting'])
            ->test(ProspectCreate::class)
            ->assertSet('funnel_type', 'recruiting');
    }

    public function test_user_without_permission_cannot_access_recruiting_pipeline(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('team.recruiting.index'))
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRecruitingProspect(array $overrides = []): Prospect
    {
        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $funnelId = DB::table('prospect_funnels')->where('key', 'recruiting')->value('id');

        return Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $stageId,
            'funnel_type' => 'recruiting',
            'first_name' => 'Recruit',
            'last_name' => 'Candidate',
            'interest_level' => 'hot',
            'priority' => 'high',
            'status' => 'active',
            ...$overrides,
        ]);
    }
}
