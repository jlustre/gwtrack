<?php

namespace Tests\Feature;

use App\Livewire\Prospects\ProspectProfileTabs;
use App\Models\CalendarEvent;
use App\Models\Prospect;
use App\Models\ProspectAppointment;
use App\Models\User;
use App\Services\Prospects\ProspectCalendarBridge;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\ProspectFunnelSeeder;
use Database\Seeders\ProspectLookupSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CrmCalendarPhaseTwoTest extends TestCase
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
            CalendarModuleSeeder::class,
        ]);

        $this->owner = User::factory()->create([
            'name' => 'CRM Calendar Owner',
            'email' => 'crm-calendar-owner@example.com',
        ]);
        $this->owner->assignRole('member');
    }

    public function test_sales_appointment_syncs_to_semantic_calendar_event(): void
    {
        $prospect = $this->prospect('insurance', 'Sales', 'Prospect');
        $appointment = $this->appointment($prospect, 'discovery-call');

        $event = app(ProspectCalendarBridge::class)->pushAppointment($appointment);

        $this->assertSame('sales-appointment', $event->type?->slug);
        $this->assertSame($prospect->id, $event->related_prospect_id);
        $this->assertStringContainsString('Prospect: Sales Prospect', (string) $event->notes);
        $this->assertDatabaseHas('calendar_event_attendees', [
            'calendar_event_id' => $event->id,
            'prospect_id' => $prospect->id,
            'attendee_type' => 'prospect',
        ]);
    }

    public function test_recruiting_appointment_syncs_to_recruiting_calendar_event_type(): void
    {
        $prospect = $this->prospect('recruiting', 'Recruiting', 'Candidate');
        $appointment = $this->appointment($prospect, 'career-overview');

        $event = app(ProspectCalendarBridge::class)->pushAppointment($appointment);

        $this->assertSame('recruiting-presentation', $event->type?->slug);
        $this->assertSame('CRM Appointment: Career Overview - Recruiting Candidate', $event->title);
    }

    public function test_calendar_event_changes_pull_back_to_prospect_appointment(): void
    {
        $prospect = $this->prospect('insurance', 'Pullback', 'Prospect');
        $appointment = $this->appointment($prospect, 'financial-needs-analysis');
        $event = app(ProspectCalendarBridge::class)->pushAppointment($appointment);

        $event->update([
            'starts_at' => now()->addDays(5)->setTime(9, 15),
            'timezone' => 'America/Toronto',
            'meeting_link' => 'https://zoom.us/j/pullback',
            'status' => 'completed',
        ]);

        app(ProspectCalendarBridge::class)->pullChanges($event->fresh());

        $appointment->refresh();
        $this->assertSame('completed', $appointment->status);
        $this->assertSame('America/Toronto', $appointment->timezone);
        $this->assertSame('https://zoom.us/j/pullback', $appointment->location_or_link);
        $this->assertSame($event->starts_at->toDateTimeString(), $appointment->scheduled_at->toDateTimeString());
    }

    public function test_linked_calendar_context_appears_on_calendar_and_prospect_profile(): void
    {
        $prospect = $this->prospect('insurance', 'Visible', 'Context');
        $appointment = $this->appointment($prospect, 'product-presentation');
        $event = app(ProspectCalendarBridge::class)->pushAppointment($appointment);

        $this->actingAs($this->owner)
            ->get(route('calendar.events.show', $event))
            ->assertOk()
            ->assertSee('Linked Prospect')
            ->assertSee('Visible Context');

        Livewire::actingAs($this->owner)
            ->test(ProspectProfileTabs::class, ['prospect' => $prospect, 'initialTab' => 'calendar'])
            ->assertSet('activeTab', 'calendar')
            ->assertSee($event->title)
            ->assertSee('Sales Appointment');
    }

    private function prospect(string $funnelType, string $firstName, string $lastName): Prospect
    {
        $funnelId = DB::table('prospect_funnels')->where('key', $funnelType)->value('id');
        $stageId = DB::table('pipeline_stages')->where('slug', 'new-lead')->value('id');

        return Prospect::create([
            'owner_id' => $this->owner->id,
            'prospect_funnel_id' => $funnelId,
            'pipeline_stage_id' => $stageId,
            'funnel_type' => $funnelType,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower($firstName).'.'.strtolower($lastName).'@example.com',
            'phone' => '555-1212',
            'interest_level' => 'warm',
            'priority' => 'medium',
        ]);
    }

    private function appointment(Prospect $prospect, string $appointmentTypeSlug): ProspectAppointment
    {
        return ProspectAppointment::create([
            'prospect_id' => $prospect->id,
            'owner_id' => $this->owner->id,
            'appointment_type_id' => DB::table('appointment_types')->where('slug', $appointmentTypeSlug)->value('id'),
            'scheduled_at' => now()->addDays(3)->setTime(14, 0),
            'timezone' => 'America/Vancouver',
            'location_or_link' => 'https://zoom.us/j/crm-phase-two',
            'purpose' => 'Phase two sync test',
            'status' => 'scheduled',
        ]);
    }
}
