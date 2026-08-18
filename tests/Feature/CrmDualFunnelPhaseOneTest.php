<?php

namespace Tests\Feature;

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

class CrmDualFunnelPhaseOneTest extends TestCase
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

    public function test_phase_one_seeds_expanded_sales_and_recruiting_stage_catalogs(): void
    {
        $insuranceFunnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');
        $recruitingFunnelId = DB::table('prospect_funnels')->where('key', 'recruiting')->value('id');

        $this->assertNotNull($insuranceFunnelId);
        $this->assertNotNull($recruitingFunnelId);

        foreach (['new-lead', 'needs-analysis', 'presentation-completed', 'application-submitted', 'policy-issued', 'closed-won', 'closed-lost'] as $slug) {
            $this->assertDatabaseHas('prospect_funnel_stages', [
                'prospect_funnel_id' => $insuranceFunnelId,
                'slug' => $slug,
            ]);
        }

        foreach (['lead-generated', 'invitation-sent', 'business-presentation', 'contract-submitted', 'active-partner', 'leader', 'closed-lost'] as $slug) {
            $this->assertDatabaseHas('prospect_funnel_stages', [
                'prospect_funnel_id' => $recruitingFunnelId,
                'slug' => $slug,
            ]);
        }

        $this->assertDatabaseHas('prospect_funnels', [
            'key' => 'insurance',
            'name' => 'Sales Funnel',
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('prospect_funnels', [
            'key' => 'recruiting',
            'name' => 'Recruiting Funnel',
            'is_default' => false,
        ]);
    }

    public function test_dual_funnel_board_filters_sales_and_recruiting_prospects(): void
    {
        $insuranceFunnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');
        $recruitingFunnelId = DB::table('prospect_funnels')->where('key', 'recruiting')->value('id');
        $newLeadStageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => $insuranceFunnelId,
            'pipeline_stage_id' => $newLeadStageId,
            'funnel_type' => 'insurance',
            'first_name' => 'Sales',
            'last_name' => 'Only',
            'interest_level' => 'warm',
            'priority' => 'medium',
            'status' => 'active',
        ]);

        Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => $recruitingFunnelId,
            'pipeline_stage_id' => $newLeadStageId,
            'funnel_type' => 'recruiting',
            'first_name' => 'Recruiting',
            'last_name' => 'Only',
            'interest_level' => 'hot',
            'priority' => 'high',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->owner)
            ->withQueryParams(['funnel' => 'insurance'])
            ->test(ProspectFunnelBoard::class)
            ->assertSee('Sales Only', false)
            ->assertDontSee('Recruiting Only', false);

        Livewire::actingAs($this->owner)
            ->withQueryParams(['funnel' => 'recruiting'])
            ->test(ProspectFunnelBoard::class)
            ->assertSee('Recruiting Only', false)
            ->assertDontSee('Sales Only', false);
    }

    public function test_stage_movement_records_history_for_expanded_sales_funnel(): void
    {
        $insuranceFunnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');
        $newLeadStageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $needsAnalysisStageId = DB::table('pipeline_stages')->where('slug', 'financial-review')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => $insuranceFunnelId,
            'pipeline_stage_id' => $newLeadStageId,
            'funnel_type' => 'insurance',
            'first_name' => 'Stage',
            'last_name' => 'Mover',
            'interest_level' => 'warm',
            'priority' => 'medium',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->owner)
            ->withQueryParams(['funnel' => 'insurance'])
            ->test(ProspectFunnelBoard::class)
            ->call('moveProspect', $prospect->id, (int) $needsAnalysisStageId)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('prospect_stage_history', [
            'prospect_id' => $prospect->id,
            'from_stage_id' => $newLeadStageId,
            'to_stage_id' => $needsAnalysisStageId,
            'changed_by' => $this->owner->id,
            'change_source' => 'kanban',
        ]);
    }
}
