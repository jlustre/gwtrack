<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\CalendarEventAttendee;
use App\Models\CalendarEventType;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Training\TrainingCalendarService;
use App\Services\Training\TrainingCoachingService;
use Database\Seeders\ChecklistTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrainingAcademySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TrainingCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChecklistTypeSeeder::class);
        $this->seed(TrainingAcademySeeder::class);
    }

    public function test_seeded_sessions_are_linked_to_calendar_events(): void
    {
        $session = TrainingSession::query()->where('title', 'Weekly FAP Coaching Lab')->firstOrFail();

        $this->assertNotNull($session->calendar_event_id);

        $event = CalendarEvent::query()->find($session->calendar_event_id);

        $this->assertNotNull($event);
        $this->assertSame('Weekly FAP Coaching Lab', $event->title);
        $this->assertSame($session->instructor_id, $event->organizer_id);
    }

    public function test_session_sync_uses_field_observation_type_for_field_sessions(): void
    {
        $session = TrainingSession::query()->where('title', 'Field Observation Debrief')->firstOrFail();
        $event = CalendarEvent::query()->findOrFail($session->calendar_event_id);
        $type = CalendarEventType::query()->findOrFail($event->calendar_event_type_id);

        $this->assertSame('field-observation', $type->slug);
    }

    public function test_registration_adds_user_to_calendar_attendees(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $session = TrainingSession::query()->where('title', 'Weekly FAP Coaching Lab')->firstOrFail();

        app(TrainingCoachingService::class)->registerForSession($user, $session);

        $session->refresh();

        $this->assertDatabaseHas('training_session_attendance', [
            'training_session_id' => $session->id,
            'user_id' => $user->id,
            'status' => 'registered',
        ]);

        $this->assertDatabaseHas('calendar_event_attendees', [
            'calendar_event_id' => $session->calendar_event_id,
            'user_id' => $user->id,
            'rsvp_status' => 'accepted',
        ]);
    }

    public function test_member_can_browse_sessions_and_view_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $this->actingAs($user)
            ->get(route('training.sessions.index'))
            ->assertOk()
            ->assertSee('Live Training Sessions')
            ->assertSee('Weekly FAP Coaching Lab');

        $session = TrainingSession::query()->where('title', 'Weekly FAP Coaching Lab')->firstOrFail();

        $this->actingAs($user)
            ->get(route('training.sessions.show', $session))
            ->assertOk()
            ->assertSee('Weekly FAP Coaching Lab')
            ->assertSee('Register for session');
    }

    public function test_registered_member_can_view_calendar_event(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $session = TrainingSession::query()->where('title', 'Weekly FAP Coaching Lab')->firstOrFail();

        app(TrainingCoachingService::class)->registerForSession($user, $session);
        $session->refresh();

        $this->actingAs($user)
            ->get(route('calendar.events.show', $session->calendar_event_id))
            ->assertOk()
            ->assertSee('Weekly FAP Coaching Lab');
    }

    public function test_livewire_registration_syncs_calendar_from_session_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('member');

        $session = TrainingSession::query()->where('title', 'Weekly FAP Coaching Lab')->firstOrFail();

        Livewire::actingAs($user)
            ->test(\App\Livewire\Training\SessionDetail::class, [
                'session' => $session,
                'canManage' => false,
            ])
            ->call('register');

        $session->refresh();

        $this->assertTrue(
            CalendarEventAttendee::query()
                ->where('calendar_event_id', $session->calendar_event_id)
                ->where('user_id', $user->id)
                ->exists()
        );
    }

    public function test_instructor_can_check_in_registered_attendee(): void
    {
        $instructor = User::query()->role('certified-field-mentor')->firstOrFail();
        $user = User::factory()->create();
        $user->assignRole('member');

        $session = TrainingSession::query()->where('title', 'Weekly FAP Coaching Lab')->firstOrFail();
        $attendance = app(TrainingCoachingService::class)->registerForSession($user, $session);

        app(TrainingCalendarService::class)->checkIn($attendance, $instructor);

        $this->assertDatabaseHas('training_session_attendance', [
            'id' => $attendance->id,
            'status' => 'attended',
        ]);
    }
}
