<?php

namespace Tests\Feature\Prospects;

use App\Livewire\Prospects\ProspectActivityLogForm;
use App\Livewire\Prospects\ProspectQuickLogModal;
use App\Models\Prospect;
use App\Models\User;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ProspectQuickLogModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_activity_pills_update_activity_type(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id'),
            'first_name' => 'Pill',
            'last_name' => 'Test',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        Livewire::actingAs($user)
            ->test(ProspectQuickLogModal::class)
            ->dispatch('open-prospect-quick-log-modal', prospectId: $prospect->id)
            ->assertSet('show', true)
            ->assertSet('initialActivityType', 'phone_call');

        Livewire::actingAs($user)
            ->test(ProspectActivityLogForm::class, ['prospectId' => (string) $prospect->id, 'embedded' => true])
            ->call('setQuickActivity', 'email')
            ->assertSet('state.activity_type', 'email')
            ->call('setQuickActivity', 'presentation')
            ->assertSet('state.activity_type', 'presentation')
            ->call('setQuickActivity', 'follow_up')
            ->assertSet('state.activity_type', 'follow_up');
    }

    public function test_save_activity_updates_pipeline_stage(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $newLeadStageId = (int) DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $contactedStageId = (int) DB::table('pipeline_stages')->where('slug', 'contacted')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'prospect_funnel_id' => DB::table('prospect_funnels')->where('key', 'insurance')->value('id'),
            'pipeline_stage_id' => $newLeadStageId,
            'first_name' => 'Quick',
            'last_name' => 'Stage',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
            'funnel_type' => 'insurance',
        ]);

        Livewire::actingAs($user)
            ->test(ProspectQuickLogModal::class)
            ->dispatch('open-prospect-quick-log-modal', prospectId: $prospect->id)
            ->assertSet('pipeline_stage_id', $newLeadStageId);

        Livewire::actingAs($user)
            ->test(ProspectActivityLogForm::class, ['prospectId' => (string) $prospect->id, 'embedded' => true])
            ->set('state.activity_occurred_at', now()->format('Y-m-d\TH:i'))
            ->set('state.pipeline_stage_id', $contactedStageId)
            ->call('save')
            ->assertDispatched('prospect-board-refresh');

        $this->assertDatabaseHas('prospects', [
            'id' => $prospect->id,
            'pipeline_stage_id' => $contactedStageId,
        ]);
    }
}
