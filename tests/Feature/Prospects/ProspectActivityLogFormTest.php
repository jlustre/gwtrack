<?php

namespace Tests\Feature\Prospects;

use App\Livewire\Prospects\ProspectActivityLogForm;
use App\Models\Prospect;
use App\Models\User;
use App\Support\ProspectActivityForm;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ProspectActivityLogFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_activity_pills_update_activity_type(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $prospect = $this->createProspect($user);

        Livewire::actingAs($user)
            ->test(ProspectActivityLogForm::class, ['prospectId' => (string) $prospect->id])
            ->assertSet('state.activity_type', 'phone_call')
            ->call('setQuickActivity', 'email')
            ->assertSet('state.activity_type', 'email')
            ->call('setQuickActivity', 'presentation')
            ->assertSet('state.activity_type', 'presentation')
            ->call('setQuickActivity', 'follow_up')
            ->assertSet('state.activity_type', 'follow_up');
    }

    public function test_mount_honors_initial_activity_type(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $prospect = $this->createProspect($user);

        Livewire::actingAs($user)
            ->test(ProspectActivityLogForm::class, [
                'prospectId' => (string) $prospect->id,
                'initialActivityType' => 'recruitment_meeting',
            ])
            ->assertSet('state.activity_type', 'recruitment_meeting');
    }

    public function test_save_persists_all_activity_parameters(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
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
            'first_name' => 'Full',
            'last_name' => 'Fields',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
            'funnel_type' => 'insurance',
        ]);

        $followUpAt = now()->addDays(3)->format('Y-m-d\TH:i');

        Livewire::actingAs($user)
            ->test(ProspectActivityLogForm::class, ['prospectId' => (string) $prospect->id])
            ->set('state.activity_type', 'policy_review')
            ->set('state.activity_subject', 'Annual policy review')
            ->set('state.activity_occurred_at', now()->format('Y-m-d\TH:i'))
            ->set('state.duration_minutes', 30)
            ->set('state.pipeline_stage_id', $contactedStageId)
            ->set('state.activity_outcome', 'Connected')
            ->set('state.activity_notes', 'Reviewed current coverage.')
            ->set('state.activity_next_action', 'Send updated quote')
            ->set('state.activity_next_follow_up_at', $followUpAt)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('prospect-activity-logged')
            ->assertDispatched('prospect-timeline-refresh')
            ->assertDispatched('prospect-board-refresh');

        $this->assertDatabaseHas('prospect_activities', [
            'prospect_id' => $prospect->id,
            'user_id' => $user->id,
            'activity_type' => 'policy_review',
            'outcome' => 'Connected',
            'duration_minutes' => 30,
            'next_action' => 'Send updated quote',
        ]);

        $this->assertDatabaseHas('prospects', [
            'id' => $prospect->id,
            'pipeline_stage_id' => $contactedStageId,
        ]);

        $activity = $prospect->activities()->first();
        $this->assertSame('Annual policy review', $activity->metadata['subject'] ?? null);
    }

    public function test_validation_rejects_unknown_activity_type(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $prospect = $this->createProspect($user);

        Livewire::actingAs($user)
            ->test(ProspectActivityLogForm::class, ['prospectId' => (string) $prospect->id])
            ->set('state.activity_type', 'invalid_type')
            ->call('save')
            ->assertHasErrors(['state.activity_type']);
    }

    public function test_cancel_resets_form_and_dispatches_event(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $prospect = $this->createProspect($user);

        Livewire::actingAs($user)
            ->test(ProspectActivityLogForm::class, ['prospectId' => (string) $prospect->id])
            ->set('state.activity_type', 'email')
            ->set('state.activity_outcome', 'Draft saved')
            ->call('cancel')
            ->assertDispatched('cancel-prospect-activity-log-form')
            ->assertSet('state.activity_type', 'phone_call')
            ->assertSet('state.activity_outcome', null);
    }

    public function test_form_renders_all_configured_activity_types(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $prospect = $this->createProspect($user);

        $component = Livewire::actingAs($user)
            ->test(ProspectActivityLogForm::class, ['prospectId' => (string) $prospect->id]);

        foreach (ProspectActivityForm::activityTypes() as $label) {
            $component->assertSee($label, false);
        }
    }

    private function createProspect(User $user): Prospect
    {
        return Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id'),
            'first_name' => 'Form',
            'last_name' => 'Test',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);
    }
}
