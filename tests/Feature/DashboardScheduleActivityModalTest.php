<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\DashboardScheduleActivityModal;
use App\Livewire\Prospects\ProspectScheduleActivityForm;
use App\Support\ProspectScheduledActivityForm;
use App\Models\CalendarEvent;
use App\Models\Prospect;
use App\Models\ProspectAppointment;
use App\Models\TaskUser;
use App\Models\User;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardScheduleActivityModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_activity_quick_action_opens_dashboard_modal(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('open-dashboard-prospect-activity-modal', false);

        Livewire::actingAs($user)
            ->test(DashboardScheduleActivityModal::class)
            ->dispatch('open-dashboard-schedule-activity-modal')
            ->assertSet('show', true)
            ->assertSet('viewTab', 'schedule');
    }

    public function test_user_can_schedule_activity_for_prospect_with_calendar_and_task_sync(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
            CalendarModuleSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $appointmentTypeId = (int) DB::table('appointment_types')->where('slug', 'discovery-call')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Schedule',
            'last_name' => 'Target',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        $scheduledAt = now()->addDays(2)->setTime(14, 0)->format('Y-m-d\TH:i');

        Livewire::actingAs($user)
            ->test(DashboardScheduleActivityModal::class)
            ->dispatch('open-dashboard-schedule-activity-modal')
            ->call('selectPerson', 'prospect:'.$prospect->id, 'Schedule Target', 'Prospect')
            ->call('openScheduleForm')
            ->assertSet('showScheduleForm', true);

        Livewire::actingAs($user)
            ->test(ProspectScheduleActivityForm::class, ['prospectId' => (string) $prospect->id])
            ->set('state.appointment_type_id', $appointmentTypeId)
            ->set('state.scheduled_at', $scheduledAt)
            ->set('state.duration_minutes', 45)
            ->set('state.purpose', 'Discovery conversation')
            ->set('state.sync_calendar', true)
            ->set('state.create_prep_task', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('prospect-scheduled-activity-saved');

        $this->assertDatabaseHas('prospect_appointments', [
            'prospect_id' => $prospect->id,
            'owner_id' => $user->id,
            'appointment_type_id' => $appointmentTypeId,
            'status' => 'scheduled',
            'purpose' => 'Discovery conversation',
        ]);

        $appointment = ProspectAppointment::query()->where('prospect_id', $prospect->id)->first();
        $this->assertNotNull($appointment->calendar_event_id);

        $this->assertDatabaseHas('calendar_events', [
            'id' => $appointment->calendar_event_id,
            'related_prospect_id' => $prospect->id,
            'organizer_id' => $user->id,
        ]);

        $event = CalendarEvent::query()->find($appointment->calendar_event_id);
        $this->assertNotNull($event->ends_at);
        $this->assertTrue($event->starts_at->diffInMinutes($event->ends_at) === 45.0);

        $this->assertTrue(
            TaskUser::query()
                ->where('assignee_id', $user->id)
                ->where('related_prospect_id', $prospect->id)
                ->whereHas('task', fn ($query) => $query->where('title', 'like', 'Prepare for scheduled touchpoint:%'))
                ->exists()
        );

        $this->assertNotNull($prospect->fresh()->appointment_at);
    }

    public function test_schedule_form_requires_future_datetime(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $appointmentTypeId = (int) DB::table('appointment_types')->where('slug', 'discovery-call')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Past',
            'last_name' => 'Date',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        Livewire::actingAs($user)
            ->test(ProspectScheduleActivityForm::class, ['prospectId' => (string) $prospect->id])
            ->set('state.appointment_type_id', $appointmentTypeId)
            ->set('state.scheduled_at', now()->subDay()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasErrors(['state.scheduled_at']);
    }

    public function test_user_can_add_new_purpose_and_location_options_from_schedule_form(): void
    {
        $this->seed([
            RolePermissionSeeder::class,
            TeamSeeder::class,
            ProspectLookupSeeder::class,
            ProspectFunnelSeeder::class,
        ]);

        $user = User::factory()->create();
        $user->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $appointmentTypeId = (int) DB::table('appointment_types')->where('slug', 'discovery-call')->value('id');

        $prospect = Prospect::create([
            'owner_id' => $user->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Custom',
            'last_name' => 'Options',
            'status' => 'active',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);

        $scheduledAt = now()->addDays(3)->setTime(11, 0)->format('Y-m-d\TH:i');
        $newPurpose = 'Quarterly business review';
        $newLocation = 'https://zoom.us/j/custom-room-123';

        Livewire::actingAs($user)
            ->test(ProspectScheduleActivityForm::class, ['prospectId' => (string) $prospect->id])
            ->set('state.appointment_type_id', $appointmentTypeId)
            ->set('state.scheduled_at', $scheduledAt)
            ->set('state.purpose', ProspectScheduledActivityForm::CREATE_OPTION_VALUE)
            ->set('state.purpose_new', $newPurpose)
            ->set('state.location_or_link', ProspectScheduledActivityForm::CREATE_OPTION_VALUE)
            ->set('state.location_or_link_new', $newLocation)
            ->set('state.sync_calendar', false)
            ->set('state.create_prep_task', false)
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('prospect-scheduled-activity-saved');

        $this->assertDatabaseHas('prospect_appointments', [
            'prospect_id' => $prospect->id,
            'purpose' => $newPurpose,
            'location_or_link' => $newLocation,
        ]);

        $this->assertDatabaseHas('schedule_activity_options', [
            'category' => 'purpose',
            'value' => $newPurpose,
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('schedule_activity_options', [
            'category' => 'location',
            'value' => $newLocation,
            'created_by' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(ProspectScheduleActivityForm::class, ['prospectId' => (string) $prospect->id])
            ->assertSee($newPurpose)
            ->assertSee($newLocation);
    }
}
