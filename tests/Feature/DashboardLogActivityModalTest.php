<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\DashboardLogActivityModal;
use App\Livewire\Prospects\ProspectActivityLogForm;
use App\Models\Prospect;
use App\Models\User;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardLogActivityModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_activity_quick_action_opens_dashboard_modal(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('open-dashboard-prospect-activity-modal', false);

        Livewire::actingAs($user)
            ->test(DashboardLogActivityModal::class)
            ->dispatch('open-dashboard-prospect-activity-modal')
            ->assertSet('show', true)
            ->assertSet('step', 'search')
            ->assertSet('viewTab', 'log')
            ->assertSee('Choose a person', false)
            ->assertSee('Prospect Activity', false)
            ->assertSee('Activity Logs', false);
    }

    public function test_legacy_picker_event_opens_dashboard_modal(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(DashboardLogActivityModal::class)
            ->dispatch('open-prospect-log-activity-picker')
            ->assertSet('show', true)
            ->assertSet('step', 'search');
    }

    public function test_selecting_prospect_shows_activity_history(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
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

        DB::table('prospect_activities')->insert([
            'prospect_id' => $prospect->id,
            'user_id' => $user->id,
            'activity_type' => 'phone_call',
            'occurred_at' => now()->subHour(),
            'outcome' => 'Reached voicemail',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::actingAs($user)
            ->test(DashboardLogActivityModal::class)
            ->dispatch('open-dashboard-log-activity-modal')
            ->call('selectPerson', 'prospect:'.$prospect->id, 'Quick Log', 'Prospect')
            ->assertSet('step', 'detail')
            ->assertSet('prospectId', (string) $prospect->id)
            ->assertSee('Reached voicemail')
            ->assertSee('Phone Call');
    }

    public function test_user_can_log_activity_from_dashboard_modal(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Log',
            'last_name' => 'Target',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        Livewire::actingAs($user)
            ->test(DashboardLogActivityModal::class)
            ->dispatch('open-dashboard-log-activity-modal')
            ->call('selectPerson', 'prospect:'.$prospect->id, 'Log Target', 'Prospect')
            ->call('openAddActivityForm')
            ->assertSet('showLogForm', true);

        Livewire::actingAs($user)
            ->test(ProspectActivityLogForm::class, ['prospectId' => (string) $prospect->id])
            ->set('state.activity_type', 'financial_review')
            ->set('state.activity_subject', 'Annual review call')
            ->set('state.activity_occurred_at', now()->format('Y-m-d\TH:i'))
            ->set('state.duration_minutes', 45)
            ->set('state.activity_outcome', 'Connected')
            ->set('state.activity_notes', 'Reviewed policy options.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('prospect-activity-logged');

        $this->assertDatabaseHas('prospect_activities', [
            'prospect_id' => $prospect->id,
            'user_id' => $user->id,
            'activity_type' => 'financial_review',
            'outcome' => 'Connected',
            'duration_minutes' => 45,
        ]);
    }

    public function test_add_new_activity_button_is_available_after_person_is_selected(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Add',
            'last_name' => 'Activity',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        Livewire::actingAs($user)
            ->test(DashboardLogActivityModal::class)
            ->dispatch('open-dashboard-log-activity-modal')
            ->call('selectPerson', 'prospect:'.$prospect->id, 'Add Activity', 'Prospect')
            ->assertSee('Log Activity', false)
            ->call('openAddActivityForm')
            ->assertSet('showLogForm', true);

        Livewire::actingAs($user)
            ->test(ProspectActivityLogForm::class, ['prospectId' => (string) $prospect->id])
            ->assertSee('Phone Call', false)
            ->assertSee('Financial Review', false)
            ->assertSee('Activity Type', false);
    }

    public function test_parent_modal_closes_form_after_child_logs_activity(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Event',
            'last_name' => 'Flow',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        Livewire::actingAs($user)
            ->test(DashboardLogActivityModal::class)
            ->dispatch('open-dashboard-log-activity-modal')
            ->call('selectPerson', 'prospect:'.$prospect->id, 'Event Flow', 'Prospect')
            ->call('openAddActivityForm')
            ->assertSet('showLogForm', true)
            ->dispatch('prospect-activity-logged')
            ->assertSet('showLogForm', false)
            ->assertSet('statusMessage', 'Activity logged successfully.');
    }

    public function test_member_without_crm_record_shows_unresolved_step(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
        ]);

        $teamId = (int) DB::table('teams')->value('id');

        $user = User::factory()->create(['team_id' => $teamId]);
        $user->assignRole('member');

        $member = User::factory()->create([
            'team_id' => $teamId,
            'name' => 'No CRM Record',
            'email' => 'no.crm.record@example.com',
        ]);
        $member->assignRole('member');

        Livewire::actingAs($user)
            ->test(DashboardLogActivityModal::class)
            ->dispatch('open-dashboard-log-activity-modal')
            ->call('selectPerson', 'member:'.$member->id, 'No CRM Record', 'Member')
            ->assertSet('step', 'unresolved')
            ->assertSee('No CRM record found for this team member', false);
    }

    public function test_user_can_view_and_edit_logged_activity_from_dashboard_modal(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'View',
            'last_name' => 'Edit',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        $activity = $prospect->activities()->create([
            'user_id' => $user->id,
            'activity_type' => 'phone_call',
            'occurred_at' => now()->subHour(),
            'outcome' => 'Left voicemail',
            'notes' => 'Will try again tomorrow.',
            'metadata' => ['subject' => 'Intro call'],
        ]);

        Livewire::actingAs($user)
            ->test(DashboardLogActivityModal::class)
            ->dispatch('open-dashboard-prospect-activity-modal')
            ->call('selectPerson', 'prospect:'.$prospect->id, 'View Edit', 'Prospect')
            ->call('viewActivity', $activity->id)
            ->assertSet('viewingActivityId', $activity->id)
            ->assertSee('Activity details', false)
            ->assertSee('Intro call')
            ->call('editActivity', $activity->id)
            ->assertSet('editingActivityId', $activity->id)
            ->assertSee('Edit Activity', false);
    }

    public function test_person_search_returns_prospects_and_members_together(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $teamId = (int) DB::table('teams')->value('id');

        $user = User::factory()->create(['team_id' => $teamId]);
        $user->assignRole('member');

        User::factory()->create([
            'team_id' => $teamId,
            'name' => 'Searchable Member',
            'email' => 'searchable.member@example.com',
        ]);

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Searchable',
            'last_name' => 'Prospect',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        Livewire::actingAs($user)
            ->test(DashboardLogActivityModal::class)
            ->dispatch('open-dashboard-log-activity-modal')
            ->set('search', 'Sea')
            ->assertSee('Searchable Prospect')
            ->assertSee('Searchable Member');
    }
}
