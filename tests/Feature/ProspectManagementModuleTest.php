<?php

namespace Tests\Feature;

use App\Livewire\Prospects\LogActivityModal;
use App\Livewire\Prospects\ProspectActivityLogForm;
use App\Livewire\Prospects\ProspectCreate;
use App\Livewire\Prospects\ProspectProfileTabs;
use App\Livewire\Prospects\ProspectQuickLogModal;
use App\Models\Prospect;
use App\Models\User;
use Database\Seeders\ProspectDemoSeeder;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ProspectManagementModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_prospect_lookup_seeder_populates_crm_foundation_data(): void
    {
        $this->seed(ProspectLookupSeeder::class);

        $this->assertDatabaseHas('prospect_sources', ['name' => 'Social Media Lead']);
        $this->assertDatabaseHas('prospect_types', ['name' => 'Recruiting Prospect']);
        $this->assertDatabaseHas('prospect_interests', ['name' => 'Mortgage Protection']);
        $this->assertDatabaseHas('pipeline_stages', ['name' => 'Appointment Scheduled']);
        $this->assertDatabaseHas('communication_types', ['name' => 'Voicemail Left']);
        $this->assertDatabaseHas('appointment_types', ['name' => 'Mentor-Assisted Call']);
        $this->assertDatabaseHas('followup_statuses', ['name' => 'Overdue']);
        $this->assertDatabaseHas('prospect_share_permissions', ['key' => 'full_collaboration']);
    }

    public function test_prospect_management_page_is_available_to_members(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('team.prospects'))
            ->assertOk()
            ->assertSee('Prospect Management', false)
            ->assertSee('Private CRM workspace', false)
            ->assertSee('Sales Pipeline', false)
            ->assertSee('Privacy Rules', false);
    }

    public function test_prospect_demo_seeder_creates_sample_prospects_and_dashboard_modules(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectDemoSeeder::class,
        ]);

        $owner = User::where('email', 'prospects@gwtrack.com')->firstOrFail();

        $this->assertGreaterThanOrEqual(50, Prospect::where('owner_id', $owner->id)->count());
        $this->assertDatabaseHas('prospect_followups', ['assigned_user_id' => $owner->id]);
        $this->assertDatabaseHas('prospect_appointments', ['owner_id' => $owner->id, 'status' => 'scheduled']);
        $this->assertDatabaseHas('prospect_imports', ['user_id' => $owner->id, 'file_name' => 'demo-prospects.csv']);

        $this->actingAs($owner)
            ->get(route('team.prospects'))
            ->assertOk()
            ->assertSee('Pipeline Summary')
            ->assertSee('Follow-Up Center')
            ->assertSee('Appointment Calendar')
            ->assertSee('Communication Timeline')
            ->assertSee('Shared By Me')
            ->assertSee('Import & Duplicates', false)
            ->assertSee('All Prospects')
            ->assertSee('Search name, email, phone, city...')
            ->assertSee('Avery Carter');
    }

    public function test_all_prospects_table_can_be_searched_and_filtered(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectDemoSeeder::class,
        ]);

        $owner = User::where('email', 'prospects@gwtrack.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('team.prospects', ['prospect_search' => 'Jordan']))
            ->assertOk()
            ->assertSee('All Prospects')
            ->assertSee('Jordan Bennett')
            ->assertSee('View prospect')
            ->assertSee('Edit prospect')
            ->assertSee('Archive prospect')
            ->assertSee('Delete prospect');

        $this->actingAs($owner)
            ->get(route('team.prospects', ['prospect_status' => 'archived']))
            ->assertOk()
            ->assertSee('All Prospects')
            ->assertSee('Archived');
    }

    public function test_all_prospect_row_actions_support_record_crud_workflow(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectDemoSeeder::class,
        ]);

        $owner = User::where('email', 'prospects@gwtrack.com')->firstOrFail();
        $prospect = Prospect::where('owner_id', $owner->id)->firstOrFail();

        $this->actingAs($owner)
            ->get(route('team.prospects.records.show', $prospect))
            ->assertOk()
            ->assertSee('Prospect Profile')
            ->assertSee($prospect->displayName());

        $this->actingAs($owner)
            ->get(route('team.prospects.records.edit', $prospect))
            ->assertOk()
            ->assertSee('Edit Prospect')
            ->assertSee('Save Changes');

        $originalStageId = $prospect->pipeline_stage_id;
        $newStageId = DB::table('pipeline_stages')
            ->whereNull('user_id')
            ->where('id', '!=', $originalStageId)
            ->orderBy('sort_order')
            ->value('id');
        $sourceId = DB::table('prospect_sources')->where('slug', 'referral')->value('id');

        $this->actingAs($owner)
            ->patch(route('team.prospects.records.update', $prospect), [
                'first_name' => 'Updated',
                'last_name' => 'Prospect',
                'funnel_type' => $prospect->funnel_type ?? 'insurance',
                'email' => 'updated.prospect@example.com',
                'phone' => '555-444-1212',
                'city' => 'Vancouver',
                'status' => 'active',
                'interest_level' => 'hot',
                'priority' => 'urgent',
                'pipeline_stage_id' => $newStageId,
                'prospect_source_id' => $sourceId,
                'next_follow_up_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'notes_summary' => 'Updated through the row action workflow.',
            ])
            ->assertRedirect(route('team.prospects.records.show', $prospect));

        $this->assertDatabaseHas('prospects', [
            'id' => $prospect->id,
            'first_name' => 'Updated',
            'priority' => 'urgent',
        ]);

        $this->assertDatabaseHas('prospect_stage_history', [
            'prospect_id' => $prospect->id,
            'from_stage_id' => $originalStageId,
            'to_stage_id' => $newStageId,
            'changed_by' => $owner->id,
            'change_source' => 'manual',
        ]);

        $this->actingAs($owner)
            ->patch(route('team.prospects.records.archive', $prospect))
            ->assertRedirect(route('team.prospects'));

        $prospect->refresh();

        $this->assertSame('archived', $prospect->status);
        $this->assertTrue($prospect->is_archived);
        $this->assertNotNull($prospect->archived_at);

        $this->actingAs($owner)
            ->get(route('team.prospects', ['prospect_status' => 'archived']))
            ->assertOk()
            ->assertSee('Restore prospect')
            ->assertSee('Delete prospect');

        $this->actingAs($owner)
            ->patch(route('team.prospects.records.restore', $prospect))
            ->assertRedirect();

        $prospect->refresh();

        $this->assertSame('active', $prospect->status);
        $this->assertFalse($prospect->is_archived);
        $this->assertNull($prospect->archived_at);

        $this->actingAs($owner)
            ->patch(route('team.prospects.records.archive', $prospect))
            ->assertRedirect(route('team.prospects'));

        $this->actingAs($owner)
            ->delete(route('team.prospects.records.destroy', $prospect))
            ->assertRedirect(route('team.prospects'));

        $this->assertSoftDeleted('prospects', ['id' => $prospect->id]);
    }

    public function test_prospect_shortcut_pages_render_module_tables(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectDemoSeeder::class,
        ]);

        $owner = User::where('email', 'prospects@gwtrack.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('team.prospects.follow-ups'))
            ->assertOk()
            ->assertSee('Follow-Up Center');

        $this->actingAs($owner)
            ->get(route('team.prospects.appointments'))
            ->assertOk()
            ->assertSee('Appointment Calendar');

        $this->actingAs($owner)
            ->get(route('team.prospects.shared-with-me'))
            ->assertRedirect(route('team.prospects.screen', 'prospects').'#shared-with-me');

        $this->actingAs($owner)
            ->get(route('team.prospects.shared-by-me'))
            ->assertRedirect(route('team.prospects.screen', 'prospects').'#shared-by-me');

        $this->actingAs($owner)
            ->get(route('team.prospects.screen', 'prospects'))
            ->assertOk()
            ->assertSee('Shared With Me')
            ->assertSee('Shared By Me');

        $this->actingAs($owner)
            ->get(route('team.prospects.access-manager'))
            ->assertOk()
            ->assertSee('Access Manager');

        $this->actingAs($owner)
            ->get(route('team.prospects.import'))
            ->assertOk()
            ->assertSee('Import Prospects');

        foreach ([
            'prospects' => 'All Prospects',
            'activity-logs' => 'CRM Activity Logs',
            'settings' => 'Pipeline Stages',
        ] as $screen => $expectedText) {
            $this->actingAs($owner)
                ->get(route('team.prospects.screen', $screen))
                ->assertOk()
                ->assertSee($expectedText)
                ->assertSee('<table', false);
        }

        $this->actingAs($owner)
            ->get(route('team.prospects.screen', 'prospects'))
            ->assertOk()
            ->assertSee('Quick Add')
            ->assertSee('Import CSV')
            ->assertSee('Download template')
            ->assertSee('Quick Add Prospect', false);
    }

    public function test_quick_add_prospect_persists_essential_fields_and_returns_to_prospects_page(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $sourceId = DB::table('prospect_sources')->where('slug', 'warm-market')->value('id');

        Livewire::actingAs($user)
            ->test(ProspectCreate::class, ['embedded' => true, 'quick' => true])
            ->set('first_name', 'Quick')
            ->set('last_name', 'Lead')
            ->set('phone', '555-0100')
            ->set('pipeline_stage_id', $stageId)
            ->set('prospect_source_id', $sourceId)
            ->set('interest_level', 'hot')
            ->set('priority', 'high')
            ->call('save')
            ->assertRedirect(route('team.prospects.screen', 'prospects'));

        $prospect = Prospect::where('first_name', 'Quick')->where('last_name', 'Lead')->firstOrFail();

        $this->assertSame('555-0100', $prospect->phone);
        $this->assertSame('hot', $prospect->interest_level);
        $this->assertSame('high', $prospect->priority);
        $this->assertSame($sourceId, $prospect->prospect_source_id);
    }

    public function test_prospect_policy_allows_owner_and_active_share_only(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
        ]);

        $owner = User::factory()->create();
        $owner->assignRole('member');

        $sharedUser = User::factory()->create();
        $sharedUser->assignRole('certified-field-mentor');

        $otherUser = User::factory()->create();
        $otherUser->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $sourceId = DB::table('prospect_sources')->where('slug', 'warm-market')->value('id');
        $permissionId = DB::table('prospect_share_permissions')->where('key', 'add_notes')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $owner->id,
            'prospect_source_id' => $sourceId,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Prospect',
            'last_name' => 'Private',
            'email' => 'private.prospect@example.com',
            'interest_level' => 'hot',
            'priority' => 'high',
        ]);

        $this->assertTrue($owner->can('view', $prospect));
        $this->assertFalse($otherUser->can('view', $prospect));

        DB::table('prospect_shares')->insert([
            'prospect_id' => $prospect->id,
            'granted_by' => $owner->id,
            'shared_with' => $sharedUser->id,
            'prospect_share_permission_id' => $permissionId,
            'permission_level' => 'add_notes',
            'granted_at' => now(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue($sharedUser->can('view', $prospect));

        DB::table('prospect_shares')
            ->where('prospect_id', $prospect->id)
            ->where('shared_with', $sharedUser->id)
            ->update([
                'status' => 'revoked',
                'revoked_at' => now(),
            ]);

        $this->assertFalse($sharedUser->fresh()->can('view', $prospect->fresh()));
    }

    public function test_create_prospect_page_renders_livewire_form(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('team.prospects.create'))
            ->assertOk()
            ->assertSee('Add Prospect')
            ->assertSee('Create Prospect');
    }

    public function test_livewire_create_prospect_persists_record_and_stage_history(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        Livewire::actingAs($user)
            ->test(ProspectCreate::class)
            ->set('first_name', 'New')
            ->set('last_name', 'Prospect')
            ->set('email', 'new.prospect@example.com')
            ->set('pipeline_stage_id', $stageId)
            ->call('save')
            ->assertRedirect();

        $prospect = Prospect::where('email', 'new.prospect@example.com')->firstOrFail();

        $this->assertSame($user->id, $prospect->owner_id);
        $this->assertDatabaseHas('prospect_stage_history', [
            'prospect_id' => $prospect->id,
            'to_stage_id' => $stageId,
            'change_source' => 'create',
        ]);
    }

    public function test_pipeline_route_renders_kanban_board(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
            ProspectDemoSeeder::class,
        ]);

        $owner = User::where('email', 'prospects@gwtrack.com')->firstOrFail();

        $this->actingAs($owner)
            ->get(route('team.prospects.pipeline'))
            ->assertOk()
            ->assertSee('Sales Pipeline')
            ->assertSee('New Lead');
    }

    public function test_prospect_profile_supports_notes_and_timeline(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $funnelId = DB::table('prospect_funnels')->where('key', 'insurance')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $stageId,
            'funnel_type' => 'insurance',
            'first_name' => 'Profile',
            'last_name' => 'Tabs',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        $this->actingAs($user)
            ->get(route('team.prospects.records.show', $prospect))
            ->assertOk()
            ->assertSee('Timeline')
            ->assertSee('Notes')
            ->assertSee('Activities')
            ->assertSee('Calls & Comms')
            ->assertSee('Log Call')
            ->assertSee('Interest Level')
            ->assertSee('Referral Source');

        Livewire::actingAs($user)
            ->test(ProspectProfileTabs::class, ['prospect' => $prospect])
            ->call('openLogCall')
            ->assertHasNoErrors()
            ->assertDispatched('open-prospect-quick-log-modal');

        Livewire::actingAs($user)
            ->test(ProspectQuickLogModal::class)
            ->dispatch('open-prospect-quick-log-modal', prospectId: $prospect->id, tab: 'activity', activityType: 'phone_call')
            ->assertSet('show', true)
            ->assertSet('prospectName', $prospect->displayName());

        Livewire::actingAs($user)
            ->test(ProspectProfileTabs::class, ['prospect' => $prospect])
            ->set('noteBody', 'Met at networking event.')
            ->call('addNote')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('prospect_notes', [
            'prospect_id' => $prospect->id,
            'user_id' => $user->id,
            'note' => 'Met at networking event.',
        ]);

        Livewire::actingAs($user)
            ->test(LogActivityModal::class)
            ->dispatch('open-log-activity-modal', prospectId: $prospect->id)
            ->assertSet('show', true);

        Livewire::actingAs($user)
            ->test(ProspectActivityLogForm::class, [
                'prospectId' => (string) $prospect->id,
                'embedded' => true,
            ])
            ->set('state.activity_type', 'phone_call')
            ->set('state.activity_occurred_at', now()->format('Y-m-d\TH:i'))
            ->set('state.activity_outcome', 'Connected')
            ->set('state.activity_notes', 'Discussed coverage needs.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('prospect_activities', [
            'prospect_id' => $prospect->id,
            'user_id' => $user->id,
            'activity_type' => 'phone_call',
            'outcome' => 'Connected',
        ]);
    }

    public function test_prospect_quick_log_modal_logs_activity_from_dashboard(): void
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

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Quick',
            'last_name' => 'Log',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        $this->actingAs($user)
            ->get(route('team.prospects'))
            ->assertOk()
            ->assertSee('View prospect', false);

        Livewire::actingAs($user)
            ->test(ProspectQuickLogModal::class)
            ->dispatch('open-prospect-quick-log-modal', prospectId: $prospect->id)
            ->assertSet('show', true)
            ->assertSet('prospectName', $prospect->displayName());

        Livewire::actingAs($user)
            ->test(ProspectActivityLogForm::class, ['prospectId' => (string) $prospect->id, 'embedded' => true])
            ->set('state.activity_type', 'presentation')
            ->set('state.activity_occurred_at', now()->format('Y-m-d\TH:i'))
            ->set('state.activity_outcome', 'Completed')
            ->set('state.activity_notes', 'Walked through product overview.')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('prospect_activities', [
            'prospect_id' => $prospect->id,
            'user_id' => $user->id,
            'activity_type' => 'presentation',
            'outcome' => 'Completed',
        ]);
    }

    public function test_prospect_activity_page_shows_timeline_and_logging_actions(): void
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

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $communicationTypeId = DB::table('communication_types')->where('name', 'Call')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Activity',
            'last_name' => 'Hub',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        DB::table('prospect_activities')->insert([
            'prospect_id' => $prospect->id,
            'user_id' => $user->id,
            'activity_type' => 'phone_call',
            'occurred_at' => now(),
            'outcome' => 'Connected',
            'notes' => 'Discussed coverage.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('prospect_communications')->insert([
            'prospect_id' => $prospect->id,
            'user_id' => $user->id,
            'communication_type_id' => $communicationTypeId,
            'direction' => 'outbound',
            'contacted_at' => now(),
            'outcome' => 'Left voicemail',
            'notes' => 'Will try again tomorrow.',
            'duration_minutes' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('team.prospects.records.activity', $prospect))
            ->assertOk()
            ->assertSee('Prospect Activity')
            ->assertSee('Activity Hub')
            ->assertSee('Log Call')
            ->assertSee('Connected')
            ->assertSee('All events');

        Livewire::actingAs($user)
            ->test(ProspectProfileTabs::class, ['prospect' => $prospect, 'initialTab' => 'communications'])
            ->assertSet('activeTab', 'communications')
            ->assertSee('Left voicemail')
            ->assertSee('Call');
    }

    public function test_prospect_activities_can_be_managed_from_api(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $owner = User::factory()->create();
        $owner->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $sourceId = DB::table('prospect_sources')->where('slug', 'warm-market')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $owner->id,
            'prospect_source_id' => $sourceId,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Activity',
            'last_name' => 'Prospect',
            'email' => 'activity.prospect@example.com',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        $this->actingAs($owner)
            ->getJson(route('team.prospects.activities.index', $prospect))
            ->assertOk()
            ->assertJson(['activities' => []]);

        $create = $this->actingAs($owner)
            ->postJson(route('team.prospects.activities.store', $prospect), [
                'activity_type' => 'call',
                'subject' => 'Intro call',
                'notes' => 'Discussed career opportunity.',
                'occurred_at' => now()->toIso8601String(),
                'outcome' => 'Interested',
                'next_action' => 'Send invite link',
                'next_follow_up_at' => now()->addDays(2)->toIso8601String(),
            ])
            ->assertCreated()
            ->assertJsonPath('activity.subject', 'Intro call');

        $activityId = $create->json('activity.id');

        $this->assertDatabaseHas('prospect_activities', [
            'id' => $activityId,
            'prospect_id' => $prospect->id,
        ]);

        $this->actingAs($owner)
            ->deleteJson(route('team.prospects.activities.destroy', [$prospect, $activityId]))
            ->assertOk();

        $this->assertSoftDeleted('prospect_activities', ['id' => $activityId]);
    }
}
