<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\DashboardSchedulePhoneCallModal;
use App\Livewire\Dashboard\SchedulePhoneCallForm;
use App\Livewire\Dashboard\UpcomingPhoneCallsPanel;
use App\Models\CalendarEvent;
use App\Models\Prospect;
use App\Models\ProspectActivity;
use App\Models\ScheduledPhoneCall;
use App\Models\User;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardSchedulePhoneCallTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_phone_call_quick_action_opens_dashboard_modal(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Schedule Phone Call', false)
            ->assertSee('open-dashboard-schedule-phone-call-modal', false)
            ->assertSee('Upcoming Phone Calls', false);

        Livewire::actingAs($user)
            ->test(DashboardSchedulePhoneCallModal::class)
            ->dispatch('open-dashboard-schedule-phone-call-modal')
            ->assertSet('show', true)
            ->assertSet('step', 'list');
    }

    public function test_modal_lists_upcoming_calls_with_actions(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $call = ScheduledPhoneCall::query()->create([
            'user_id' => $user->id,
            'person_type' => 'external',
            'contact_name' => 'Listed Contact',
            'contact_phone' => '6045550133',
            'scheduled_at' => now()->addHours(2),
            'timezone' => config('app.timezone'),
            'status' => 'scheduled',
            'purpose' => 'Check in',
        ]);

        Livewire::actingAs($user)
            ->test(DashboardSchedulePhoneCallModal::class)
            ->dispatch('open-dashboard-schedule-phone-call-modal')
            ->assertSet('step', 'list')
            ->assertSee('Listed Contact')
            ->assertSee('Check in')
            ->assertSee('View')
            ->assertSee('Reschedule')
            ->assertSee('Edit')
            ->assertSee('Delete')
            ->call('viewCall', $call->id)
            ->assertSet('step', 'view')
            ->assertSee('Listed Contact');
    }

    public function test_user_can_edit_reschedule_and_delete_call_from_modal(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CalendarModuleSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $call = ScheduledPhoneCall::query()->create([
            'user_id' => $user->id,
            'person_type' => 'external',
            'contact_name' => 'Manage Me',
            'contact_phone' => '6045550144',
            'scheduled_at' => now()->addDay()->setTime(9, 0),
            'timezone' => config('app.timezone'),
            'status' => 'scheduled',
            'purpose' => 'Initial outreach',
        ]);

        $newTime = now()->addDays(2)->setTime(14, 30)->format('Y-m-d\TH:i');

        Livewire::actingAs($user)
            ->test(DashboardSchedulePhoneCallModal::class)
            ->dispatch('open-dashboard-schedule-phone-call-modal')
            ->call('editCall', $call->id)
            ->assertSet('step', 'edit');

        Livewire::actingAs($user)
            ->test(SchedulePhoneCallForm::class, ['callId' => $call->id])
            ->set('purpose', 'Updated purpose')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('scheduled-phone-call-saved');

        $call->refresh();
        $this->assertSame('Updated purpose', $call->purpose);

        Livewire::actingAs($user)
            ->test(DashboardSchedulePhoneCallModal::class)
            ->dispatch('open-dashboard-schedule-phone-call-modal')
            ->call('startReschedule', $call->id)
            ->set('rescheduleAt', $newTime)
            ->call('saveReschedule')
            ->assertHasNoErrors()
            ->assertSet('step', 'list');

        $call->refresh();
        $this->assertSame($newTime, $call->scheduled_at->format('Y-m-d\TH:i'));

        Livewire::actingAs($user)
            ->test(DashboardSchedulePhoneCallModal::class)
            ->dispatch('open-dashboard-schedule-phone-call-modal')
            ->call('deleteCall', $call->id)
            ->assertSet('step', 'list');

        $this->assertSoftDeleted('scheduled_phone_calls', ['id' => $call->id]);
    }

    public function test_user_can_select_same_person_again_after_deleting_call(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Repeat',
            'last_name' => 'Caller',
            'phone' => '6045550166',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        $personKey = 'prospect:'.$prospect->id;
        $scheduledAt = now()->addDay()->setTime(11, 0)->format('Y-m-d\TH:i');

        $call = ScheduledPhoneCall::query()->create([
            'user_id' => $user->id,
            'person_type' => 'prospect',
            'prospect_id' => $prospect->id,
            'contact_name' => 'Repeat Caller',
            'contact_phone' => '6045550166',
            'scheduled_at' => now()->addHours(3),
            'timezone' => config('app.timezone'),
            'status' => 'scheduled',
        ]);

        Livewire::actingAs($user)
            ->test(DashboardSchedulePhoneCallModal::class)
            ->dispatch('open-dashboard-schedule-phone-call-modal')
            ->call('editCall', $call->id)
            ->assertSet('step', 'edit')
            ->call('backToList')
            ->call('deleteCall', $call->id)
            ->assertSet('step', 'list')
            ->call('startScheduleNew')
            ->call('selectPerson', $personKey)
            ->assertSet('step', 'detail')
            ->assertSet('selectedPersonKey', $personKey)
            ->assertSet('showScheduleForm', true)
            ->assertSet('editingCallId', null);

        Livewire::actingAs($user)
            ->test(SchedulePhoneCallForm::class, [
                'person' => [
                    'person_type' => 'prospect',
                    'prospect_id' => $prospect->id,
                    'related_user_id' => null,
                    'contact_name' => 'Repeat Caller',
                    'contact_phone' => '6045550166',
                    'contact_email' => null,
                ],
            ])
            ->set('scheduledAt', $scheduledAt)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('scheduled_phone_calls', [
            'user_id' => $user->id,
            'prospect_id' => $prospect->id,
            'status' => 'scheduled',
            'contact_name' => 'Repeat Caller',
        ]);
    }

    public function test_user_can_schedule_phone_call_for_prospect_with_calendar_sync(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            CalendarModuleSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Call',
            'last_name' => 'Target',
            'phone' => '6045550100',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        $scheduledAt = now()->addDay()->setTime(10, 0)->format('Y-m-d\TH:i');

        Livewire::actingAs($user)
            ->test(DashboardSchedulePhoneCallModal::class)
            ->dispatch('open-dashboard-schedule-phone-call-modal')
            ->call('startScheduleNew')
            ->call('selectPerson', 'prospect:'.$prospect->id);

        Livewire::actingAs($user)
            ->test(SchedulePhoneCallForm::class, [
                'person' => [
                    'person_type' => 'prospect',
                    'prospect_id' => $prospect->id,
                    'related_user_id' => null,
                    'contact_name' => 'Call Target',
                    'contact_phone' => '6045550100',
                    'contact_email' => null,
                ],
            ])
            ->set('scheduledAt', $scheduledAt)
            ->set('purpose', 'Discovery follow-up')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('scheduled-phone-call-saved');

        $this->assertDatabaseHas('scheduled_phone_calls', [
            'user_id' => $user->id,
            'prospect_id' => $prospect->id,
            'person_type' => 'prospect',
            'contact_name' => 'Call Target',
            'status' => 'scheduled',
            'purpose' => 'Discovery follow-up',
        ]);

        $call = ScheduledPhoneCall::query()->first();
        $this->assertNotNull($call?->calendar_event_id);
        $this->assertDatabaseHas('calendar_events', [
            'id' => $call->calendar_event_id,
            'title' => 'Phone call: Call Target',
        ]);
    }

    public function test_user_can_schedule_external_phone_call(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        Livewire::actingAs($user)
            ->test(SchedulePhoneCallForm::class, [
                'person' => [
                    'person_type' => 'external',
                    'prospect_id' => null,
                    'related_user_id' => null,
                    'contact_name' => 'Outside Contact',
                    'contact_phone' => '6045550199',
                    'contact_email' => 'outside@example.com',
                ],
            ])
            ->set('scheduledAt', now()->addDays(2)->setTime(15, 0)->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('scheduled_phone_calls', [
            'user_id' => $user->id,
            'person_type' => 'external',
            'contact_name' => 'Outside Contact',
            'contact_phone' => '6045550199',
            'status' => 'scheduled',
        ]);
    }

    public function test_user_can_record_completed_call_result_and_log_activity(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Result',
            'last_name' => 'Prospect',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        $call = ScheduledPhoneCall::query()->create([
            'user_id' => $user->id,
            'person_type' => 'prospect',
            'prospect_id' => $prospect->id,
            'contact_name' => 'Result Prospect',
            'contact_phone' => '6045550111',
            'scheduled_at' => now()->addHour(),
            'timezone' => config('app.timezone'),
            'status' => 'scheduled',
        ]);

        Livewire::actingAs($user)
            ->test(UpcomingPhoneCallsPanel::class)
            ->call('openResultForm', $call->id)
            ->set('result', 'completed')
            ->set('resultNotes', 'Discussed next steps.')
            ->call('saveResult')
            ->assertHasNoErrors();

        $call->refresh();
        $this->assertSame('completed', $call->status);
        $this->assertSame('completed', $call->result);
        $this->assertNotNull($call->prospect_activity_id);
        $this->assertDatabaseHas('prospect_activities', [
            'id' => $call->prospect_activity_id,
            'prospect_id' => $prospect->id,
            'activity_type' => 'phone_call',
            'outcome' => 'Completed',
        ]);
    }

    public function test_view_modal_saves_result_and_syncs_crm_for_prospect(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Modal',
            'last_name' => 'Result',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        $call = ScheduledPhoneCall::query()->create([
            'user_id' => $user->id,
            'person_type' => 'prospect',
            'prospect_id' => $prospect->id,
            'contact_name' => 'Modal Result',
            'contact_phone' => '6045550155',
            'scheduled_at' => now()->addHour(),
            'timezone' => config('app.timezone'),
            'status' => 'scheduled',
        ]);

        Livewire::actingAs($user)
            ->test(DashboardSchedulePhoneCallModal::class)
            ->dispatch('open-dashboard-schedule-phone-call-modal')
            ->call('viewCall', $call->id)
            ->assertSet('step', 'view')
            ->assertSee('Results')
            ->set('result', 'not_contacted')
            ->set('resultNotes', 'No answer, left voicemail.')
            ->call('saveCallResult')
            ->assertHasNoErrors()
            ->assertSet('step', 'list');

        $call->refresh();
        $this->assertSame('scheduled', $call->status);
        $this->assertSame('not_contacted', $call->result);
        $this->assertDatabaseHas('prospect_activities', [
            'prospect_id' => $prospect->id,
            'activity_type' => 'phone_call',
            'outcome' => 'Not contacted',
        ]);

        Livewire::actingAs($user)
            ->test(DashboardSchedulePhoneCallModal::class)
            ->dispatch('open-dashboard-schedule-phone-call-modal')
            ->assertSee('Modal Result');
    }

    public function test_overdue_call_appears_in_follow_up_list(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        ScheduledPhoneCall::query()->create([
            'user_id' => $user->id,
            'person_type' => 'external',
            'contact_name' => 'Overdue Caller',
            'contact_phone' => '6045550177',
            'scheduled_at' => now()->subHours(2),
            'timezone' => config('app.timezone'),
            'status' => 'scheduled',
        ]);

        Livewire::actingAs($user)
            ->test(DashboardSchedulePhoneCallModal::class)
            ->dispatch('open-dashboard-schedule-phone-call-modal')
            ->assertSee('Follow up')
            ->assertSee('Overdue Caller');
    }

    public function test_reschedule_moves_call_from_follow_up_to_active_list(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CalendarModuleSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $call = ScheduledPhoneCall::query()->create([
            'user_id' => $user->id,
            'person_type' => 'external',
            'contact_name' => 'Move Me Back',
            'contact_phone' => '6045550188',
            'scheduled_at' => now()->subHour(),
            'timezone' => config('app.timezone'),
            'status' => 'scheduled',
        ]);

        $futureTime = now()->addDays(2)->setTime(10, 0)->format('Y-m-d\TH:i');

        Livewire::actingAs($user)
            ->test(DashboardSchedulePhoneCallModal::class)
            ->dispatch('open-dashboard-schedule-phone-call-modal')
            ->assertSee('Follow up')
            ->call('startReschedule', $call->id)
            ->set('rescheduleAt', $futureTime)
            ->call('saveReschedule')
            ->assertHasNoErrors();

        $call->refresh();
        $this->assertTrue($call->isActive());
        $this->assertSame($futureTime, $call->scheduled_at->format('Y-m-d\TH:i'));

        Livewire::actingAs($user)
            ->test(DashboardSchedulePhoneCallModal::class)
            ->dispatch('open-dashboard-schedule-phone-call-modal')
            ->assertSee('Move Me Back')
            ->assertDontSee('Follow up');
    }

    public function test_callback_result_reschedules_same_call_and_keeps_it_open(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $call = ScheduledPhoneCall::query()->create([
            'user_id' => $user->id,
            'person_type' => 'external',
            'contact_name' => 'Retry Contact',
            'contact_phone' => '6045550122',
            'scheduled_at' => now()->addHour(),
            'timezone' => config('app.timezone'),
            'status' => 'scheduled',
        ]);

        $callbackAt = now()->addDays(3)->setTime(11, 0)->format('Y-m-d\TH:i');

        Livewire::actingAs($user)
            ->test(UpcomingPhoneCallsPanel::class)
            ->call('openResultForm', $call->id)
            ->set('result', 'callback')
            ->set('callbackAt', $callbackAt)
            ->call('saveResult')
            ->assertHasNoErrors();

        $call->refresh();
        $this->assertSame('scheduled', $call->status);
        $this->assertSame('callback', $call->result);
        $this->assertSame(1, ScheduledPhoneCall::query()->count());
        $this->assertSame($callbackAt, $call->scheduled_at->format('Y-m-d\TH:i'));
        $this->assertTrue($call->isActive());
    }

    public function test_calendar_page_shows_upcoming_phone_calls_panel(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CalendarModuleSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('Upcoming Phone Calls', false)
            ->assertSee('Upcoming Appointments', false)
            ->assertSee('My Tasks', false)
            ->assertSee('Delegation Tracker', false);
    }

    public function test_panel_can_delete_call_without_opening_modal(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('member');

        $call = ScheduledPhoneCall::query()->create([
            'user_id' => $user->id,
            'person_type' => 'external',
            'contact_name' => 'Delete From Panel',
            'contact_phone' => '6045550199',
            'scheduled_at' => now()->addHour(),
            'timezone' => config('app.timezone'),
            'status' => 'scheduled',
        ]);

        Livewire::actingAs($user)
            ->test(UpcomingPhoneCallsPanel::class)
            ->assertSee('Delete From Panel')
            ->call('deleteCall', $call->id)
            ->assertHasNoErrors();

        $this->assertSoftDeleted('scheduled_phone_calls', ['id' => $call->id]);
    }

    public function test_panel_can_reschedule_call_without_opening_modal(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            CalendarModuleSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $call = ScheduledPhoneCall::query()->create([
            'user_id' => $user->id,
            'person_type' => 'external',
            'contact_name' => 'Reschedule From Panel',
            'contact_phone' => '6045550200',
            'scheduled_at' => now()->subHour(),
            'timezone' => config('app.timezone'),
            'status' => 'scheduled',
        ]);

        $futureTime = now()->addDays(2)->setTime(14, 30)->format('Y-m-d\TH:i');

        Livewire::actingAs($user)
            ->test(UpcomingPhoneCallsPanel::class)
            ->assertSee('Follow up')
            ->assertSee('Reschedule From Panel')
            ->call('startReschedule', $call->id)
            ->set('rescheduleAt', $futureTime)
            ->call('saveReschedule')
            ->assertHasNoErrors();

        $call->refresh();
        $this->assertTrue($call->isActive());
        $this->assertSame($futureTime, $call->scheduled_at->format('Y-m-d\TH:i'));
    }
}
