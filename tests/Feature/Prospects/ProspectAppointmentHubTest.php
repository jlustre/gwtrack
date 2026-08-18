<?php

namespace Tests\Feature\Prospects;

use App\Livewire\Prospects\ProspectAppointmentHub;
use App\Models\CalendarEvent;
use App\Models\Prospect;
use App\Models\ProspectAppointment;
use App\Models\User;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ProspectAppointmentHubTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Prospect $prospect;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RolePermissionSeeder::class,
            ProspectLookupSeeder::class,
            CalendarModuleSeeder::class,
        ]);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('member');

        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');
        $this->prospect = Prospect::create([
            'owner_id' => $this->owner->id,
            'pipeline_stage_id' => $stageId,
            'first_name' => 'Appt',
            'last_name' => 'Hub',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);
    }

    public function test_appointments_page_renders_livewire_hub(): void
    {
        $this->actingAs($this->owner)
            ->get(route('team.prospects.appointments'))
            ->assertOk()
            ->assertSee('Appointment Calendar')
            ->assertSeeLivewire(ProspectAppointmentHub::class);
    }

    public function test_create_appointment_persists_and_syncs_calendar_event(): void
    {
        $typeId = DB::table('appointment_types')->where('slug', 'discovery-call')->value('id');
        $scheduledAt = now()->addDays(2)->setTime(15, 30)->format('Y-m-d\TH:i');

        Livewire::actingAs($this->owner)
            ->test(ProspectAppointmentHub::class)
            ->call('openCreateForm', $this->prospect->id)
            ->assertSet('showForm', true)
            ->set('appointmentTypeId', $typeId)
            ->set('scheduledAt', $scheduledAt)
            ->set('locationOrLink', 'https://zoom.us/j/hub-test')
            ->set('purpose', 'Discovery conversation')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $appointment = ProspectAppointment::query()->where('prospect_id', $this->prospect->id)->first();
        $this->assertNotNull($appointment);
        $this->assertSame($this->owner->id, (int) $appointment->owner_id);
        $this->assertNotNull($appointment->calendar_event_id);

        $event = CalendarEvent::query()->find($appointment->calendar_event_id);
        $this->assertNotNull($event);
        $this->assertSame($this->prospect->id, $event->related_prospect_id);
        $this->assertSame('https://zoom.us/j/hub-test', $event->meeting_link);
    }

    public function test_cancel_appointment_updates_status_and_calendar_event(): void
    {
        $appointment = ProspectAppointment::create([
            'prospect_id' => $this->prospect->id,
            'owner_id' => $this->owner->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'scheduled',
        ]);

        app(\App\Services\Prospects\ProspectCalendarBridge::class)
            ->pushAppointment($appointment->fresh(['prospect', 'type', 'owner']));

        Livewire::actingAs($this->owner)
            ->test(ProspectAppointmentHub::class)
            ->call('cancelAppointment', $appointment->id)
            ->assertHasNoErrors();

        $appointment->refresh();
        $this->assertSame('cancelled', $appointment->status);
        $this->assertSame('cancelled', CalendarEvent::query()->find($appointment->calendar_event_id)?->status);
    }
}
