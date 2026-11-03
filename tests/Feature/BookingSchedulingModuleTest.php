<?php

namespace Tests\Feature;

use App\Livewire\Booking\BookingsHub;
use App\Livewire\Booking\MyBookingsList;
use App\Livewire\Booking\PublicBookingPage;
use App\Models\AvailabilityOverride;
use App\Models\AvailabilitySchedule;
use App\Models\BlackoutDate;
use App\Models\Booking;
use App\Models\BookingEventType;
use App\Models\BookingLink;
use App\Models\CalendarEvent;
use App\Services\BookingWorkflowService;
use Carbon\Carbon;
use App\Models\User;
use Database\Seeders\BookingSchedulingSeeder;
use Database\Seeders\CalendarModuleSeeder;
use Database\Seeders\RankSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BookingSchedulingModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_booking_scheduling_seeder_creates_cfm_foundation_data(): void
    {
        $this->seedScheduling();

        $this->assertGreaterThanOrEqual(8, BookingEventType::count());
        $this->assertGreaterThanOrEqual(1, AvailabilitySchedule::count());
        $this->assertGreaterThanOrEqual(8, BookingLink::count());
        $this->assertDatabaseHas('bookings', ['status' => 'confirmed']);
        $this->assertDatabaseHas('bookings', ['status' => 'pending_approval']);
        $this->assertDatabaseHas('calendar_events', ['title' => 'Weekly Progress Review - Booking Apprentice Trainee']);
    }

    public function test_cfm_can_open_booking_workspace_pages(): void
    {
        $this->seedScheduling();

        $cfm = User::where('email', 'booking-cfm@gwtracks.com')->firstOrFail();

        foreach ([
            'bookings.dashboard' => 'dashboard',
            'bookings.availability' => 'availability',
            'bookings.event-types' => 'event-types',
            'bookings.links' => 'links',
            'bookings.requests' => 'requests',
            'bookings.calendar' => 'calendar',
            'bookings.settings' => 'settings',
        ] as $route => $tab) {
            $this->actingAs($cfm)
                ->get(route($route))
                ->assertRedirect(route('bookings.my', ['tab' => $tab]));
        }

        $this->actingAs($cfm)
            ->get(route('bookings.my'))
            ->assertOk()
            ->assertSee('CFM Mentor Scheduling', false)
            ->assertSeeLivewire(BookingsHub::class);

        $this->actingAs($cfm)
            ->followingRedirects()
            ->get(route('bookings.dashboard'))
            ->assertOk()
            ->assertSee('Mentor Booking Dashboard', false);
    }

    public function test_bookings_hub_switches_tabs_without_leaving_page(): void
    {
        $this->seedScheduling();

        $cfm = User::where('email', 'booking-cfm@gwtracks.com')->firstOrFail();

        Livewire::actingAs($cfm)
            ->test(BookingsHub::class)
            ->assertSet('tab', 'appointments')
            ->assertSee('My Appointments', false)
            ->call('setTab', 'calendar')
            ->assertSet('tab', 'calendar')
            ->assertSee('Mentor Session Calendar', false)
            ->call('setTab', 'dashboard')
            ->assertSet('tab', 'dashboard')
            ->assertSee('Mentor Booking Dashboard', false)
            ->call('setTab', 'appointments')
            ->assertSet('tab', 'appointments')
            ->assertSee('Your mentor sessions', false);
    }

    public function test_trainee_can_view_own_bookings_but_not_cfm_availability_manager(): void
    {
        $this->seedScheduling();

        $trainee = User::where('email', 'booking-trainee@gwtracks.com')->firstOrFail();

        $this->assertSame(url('/bookings/my-appointments'), route('bookings.my'));

        $this->actingAs($trainee)
            ->get(route('bookings.my'))
            ->assertOk()
            ->assertSee('My Appointments')
            ->assertSee('Your mentor sessions', false)
            ->assertSeeLivewire(MyBookingsList::class);

        $this->actingAs($trainee)
            ->get('/bookings/my')
            ->assertRedirect('/bookings/my-appointments');

        $this->actingAs($trainee)
            ->get(route('bookings.availability'))
            ->assertForbidden();
    }

    public function test_my_appointments_table_filters_and_searches_without_full_reload(): void
    {
        $this->seedScheduling();

        $trainee = User::where('email', 'booking-trainee@gwtracks.com')->firstOrFail();
        $confirmed = Booking::query()->where('status', 'confirmed')->firstOrFail();

        Livewire::actingAs($trainee)
            ->test(MyBookingsList::class)
            ->assertSee('Weekly Progress Review')
            ->assertSee('Field Apprenticeship Session')
            ->set('status', 'confirmed')
            ->assertSee('Weekly Progress Review')
            ->assertDontSee('Field Apprenticeship Session')
            ->set('status', '')
            ->set('q', 'Apprenticeship')
            ->assertSee('Field Apprenticeship Session')
            ->assertDontSee($confirmed->eventType?->title ?? 'Weekly Progress Review')
            ->set('q', '')
            ->set('role', 'trainee')
            ->assertSee('Weekly Progress Review')
            ->call('clearFilters')
            ->assertSet('status', '')
            ->assertSet('q', '')
            ->assertSet('when', 'upcoming');
    }

    public function test_public_booking_invite_page_renders_seeded_event_type(): void
    {
        $this->seedScheduling();

        $link = BookingLink::where('link_type', 'apprentice')->firstOrFail();

        $this->get(route('bookings.invite', $link->token))
            ->assertOk()
            ->assertSee('Book a Session')
            ->assertSee('Field Apprenticeship Session');
    }

    public function test_public_booking_creates_calendar_event_for_auto_confirmed_session(): void
    {
        $this->seedScheduling();
        [$cfm, $trainee, $type, $link, $slot] = $this->availableBookingFixture('weekly-progress-review');

        $booking = app(BookingWorkflowService::class)->createBooking(
            $type,
            $link,
            Carbon::parse($slot['starts_at']),
            $trainee,
            null,
            null,
            $this->requiredAnswers($type),
            'Discuss weekly goals',
            'Prospect follow-up',
        );

        $this->assertSame('confirmed', $booking->status);
        $this->assertSame($cfm->id, $booking->cfm_id);
        $this->assertSame($trainee->id, $booking->trainee_id);
        $this->assertNotNull($booking->calendar_event_id);
        $this->assertDatabaseHas('calendar_events', [
            'id' => $booking->calendar_event_id,
            'organizer_id' => $cfm->id,
            'related_apprentice_id' => $trainee->id,
            'status' => 'scheduled',
        ]);
        $this->assertDatabaseHas('booking_answers', ['booking_id' => $booking->id]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $trainee->id,
            'module' => 'booking',
        ]);
        $this->assertSame(10, \App\Models\CalendarEventReminder::query()
            ->where('calendar_event_id', $booking->calendar_event_id)
            ->count());
        $this->assertFalse(
            collect(app(BookingWorkflowService::class)->availableSlots($type, $link, $booking->starts_at, $trainee))
                ->contains(fn (array $candidate): bool => Carbon::parse($candidate['starts_at'])->equalTo($booking->starts_at)),
            'A confirmed booking must no longer appear as an available slot.'
        );
    }

    public function test_availability_overrides_and_blackouts_control_bookable_slots(): void
    {
        $this->seedScheduling();
        [$cfm, $trainee, $type, $link, $slot] = $this->availableBookingFixture('weekly-progress-review');
        $workflow = app(BookingWorkflowService::class);
        $startsAt = Carbon::parse($slot['starts_at'])->timezone($link->schedule->timezone);
        $date = $startsAt->toDateString();

        BlackoutDate::query()->create([
            'user_id' => $cfm->id,
            'starts_on' => $date,
            'ends_on' => $date,
            'reason' => 'Unavailable',
        ]);
        $this->assertSame([], $workflow->availableSlots($type, $link, $startsAt, $trainee));

        BlackoutDate::query()->where('user_id', $cfm->id)->whereDate('starts_on', $date)->delete();
        AvailabilityOverride::query()->create([
            'availability_schedule_id' => $link->schedule->id,
            'override_date' => $date,
            'is_available' => false,
            'reason' => 'Unavailable',
        ]);
        $this->assertSame([], $workflow->availableSlots($type, $link, $startsAt, $trainee));

        AvailabilityOverride::query()->where('availability_schedule_id', $link->schedule->id)->whereDate('override_date', $date)->delete();
        AvailabilityOverride::query()->create([
            'availability_schedule_id' => $link->schedule->id,
            'override_date' => $date,
            'starts_at' => $startsAt->format('H:i'),
            'ends_at' => $startsAt->copy()->addMinutes($type->duration_minutes)->format('H:i'),
            'is_available' => true,
        ]);
        $slots = $workflow->availableSlots($type, $link, $startsAt, $trainee);

        $this->assertCount(1, $slots);
        $this->assertTrue(Carbon::parse($slots[0]['starts_at'])->equalTo($startsAt));
    }

    public function test_public_booking_form_submits_selected_slot_and_answers(): void
    {
        $this->seedScheduling();
        [$cfm, $trainee, $type, $link, $slot] = $this->availableBookingFixture('weekly-progress-review');
        $localDate = Carbon::parse($slot['starts_at'])->timezone($link->schedule->timezone)->toDateString();

        Livewire::actingAs($trainee)
            ->test(PublicBookingPage::class, ['inviteToken' => $link->token])
            ->set('date', $localDate)
            ->set('selectedSlot', $slot['starts_at'])
            ->set('answers', $this->requiredAnswers($type))
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('Your session is confirmed.');

        $this->assertDatabaseHas('bookings', [
            'booking_event_type_id' => $type->id,
            'cfm_id' => $cfm->id,
            'trainee_id' => $trainee->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_pending_booking_can_be_approved_and_cancelled_with_history(): void
    {
        $this->seedScheduling();
        [$cfm, $trainee, $type, $link, $slot] = $this->availableBookingFixture('field-apprenticeship-session');
        $workflow = app(BookingWorkflowService::class);
        $booking = $workflow->createBooking(
            $type,
            $link,
            Carbon::parse($slot['starts_at']),
            $trainee,
            null,
            null,
            $this->requiredAnswers($type),
        );

        $this->assertSame('pending_approval', $booking->status);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $cfm->id,
            'module' => 'booking',
        ]);
        $this->assertTrue(
            collect($workflow->availableSlots($type, $link, $booking->starts_at, $trainee, $booking))
                ->contains(fn (array $candidate): bool => Carbon::parse($candidate['starts_at'])->equalTo($booking->starts_at)),
            'The booked time should remain available to itself while approval is pending.'
        );
        $confirmed = $workflow->approve($booking, $cfm);
        $this->assertSame('confirmed', $confirmed->status);
        $this->assertNotNull($confirmed->calendar_event_id);

        $cancelled = $workflow->cancel($confirmed, $trainee, 'Need to choose a new date.');
        $this->assertSame('cancelled', $cancelled->status);
        $this->assertDatabaseHas('booking_cancellations', [
            'booking_id' => $booking->id,
            'cancelled_by' => $trainee->id,
            'cancelled_by_type' => 'trainee',
        ]);
        $this->assertDatabaseHas('calendar_events', [
            'id' => $confirmed->calendar_event_id,
            'status' => 'cancelled',
        ]);
    }

    public function test_reschedule_requests_require_mentor_approval_and_update_calendar(): void
    {
        $this->seedScheduling();
        [$cfm, $trainee, $type, $link, $slot] = $this->availableBookingFixture('weekly-progress-review');
        $workflow = app(BookingWorkflowService::class);
        $booking = $workflow->createBooking(
            $type,
            $link,
            Carbon::parse($slot['starts_at']),
            $trainee,
            null,
            null,
            $this->requiredAnswers($type),
        );

        $newSlot = null;
        for ($daysAhead = 1; $daysAhead <= $type->maximum_booking_days_ahead; $daysAhead++) {
            $date = Carbon::now($link->schedule->timezone)->addDays($daysAhead);
            $newSlot = collect($workflow->availableSlots($type, $link, $date, $trainee, $booking))
                ->first(fn (array $candidate): bool => $candidate['starts_at'] !== $slot['starts_at']);
            if ($newSlot) {
                break;
            }
        }
        $this->assertNotNull($newSlot, 'A second open time should be available for rescheduling.');

        $reschedule = $workflow->requestReschedule($booking, $trainee, Carbon::parse($newSlot['starts_at']), 'Schedule conflict');
        $this->assertSame('pending', $reschedule->status);

        $resolved = $workflow->resolveReschedule($reschedule, $cfm, true);
        $this->assertSame('approved', $resolved->status);
        $this->assertTrue($booking->fresh()->starts_at->equalTo(Carbon::parse($newSlot['starts_at'])));
        $this->assertTrue($booking->fresh()->calendarEvent->starts_at->equalTo($booking->fresh()->starts_at));
    }

    public function test_booking_hub_can_create_schedule_event_type_and_link(): void
    {
        $this->seedScheduling();
        $cfm = User::where('email', 'booking-cfm@gwtracks.com')->firstOrFail();

        Livewire::actingAs($cfm)
            ->test(BookingsHub::class, ['tab' => 'availability'])
            ->set('scheduleName', 'Evening Hours')
            ->set('scheduleTimezone', 'America/Toronto')
            ->call('createSchedule')
            ->set('ruleWeekday', 3)
            ->set('ruleStartsAt', '17:00')
            ->set('ruleEndsAt', '20:00')
            ->call('addAvailabilityRule')
            ->set('overrideScheduleId', AvailabilitySchedule::query()->where('user_id', $cfm->id)->where('name', 'Evening Hours')->value('id'))
            ->set('overrideDate', now()->addDays(3)->toDateString())
            ->set('overrideStartsAt', '18:00')
            ->set('overrideEndsAt', '20:00')
            ->set('overrideAvailable', true)
            ->call('saveAvailabilityOverride')
            ->set('blackoutStartsOn', now()->addDays(8)->toDateString())
            ->set('blackoutReason', 'Personal leave')
            ->call('createBlackoutDate')
            ->assertHasNoErrors()
            ->assertSee('Evening Hours');

        Livewire::actingAs($cfm)
            ->test(BookingsHub::class, ['tab' => 'event-types'])
            ->set('eventTitle', 'New Coaching Session')
            ->set('eventDescription', 'A coaching session for testing.')
            ->set('eventDuration', 45)
            ->set('eventApprovalRequired', true)
            ->set('eventVisibility', 'assigned_apprentices')
            ->call('createEventType')
            ->set('questionEventTypeId', BookingEventType::query()->where('owner_id', $cfm->id)->where('slug', 'new-coaching-session')->value('id'))
            ->set('questionText', 'What would you like to cover?')
            ->set('questionType', 'multiple_choice')
            ->set('questionOptions', 'Licensing, Field activity, Recruiting')
            ->set('questionRequired', true)
            ->call('addBookingQuestion')
            ->assertHasNoErrors()
            ->assertSee('New Coaching Session');

        $type = BookingEventType::query()->where('owner_id', $cfm->id)->where('slug', 'new-coaching-session')->firstOrFail();
        $schedule = AvailabilitySchedule::query()->where('user_id', $cfm->id)->where('name', 'Evening Hours')->firstOrFail();
        Livewire::actingAs($cfm)
            ->test(BookingsHub::class, ['tab' => 'links'])
            ->set('linkEventTypeId', $type->id)
            ->set('linkScheduleId', $schedule->id)
            ->set('linkName', 'Evening Coaching')
            ->call('createBookingLink')
            ->assertHasNoErrors()
            ->assertSee('Booking link created.');

        $this->assertDatabaseHas('booking_links', [
            'owner_id' => $cfm->id,
            'booking_event_type_id' => $type->id,
            'availability_schedule_id' => $schedule->id,
            'name' => 'Evening Coaching',
        ]);
        $this->assertDatabaseHas('availability_rules', [
            'availability_schedule_id' => $schedule->id,
            'weekday' => 3,
            'starts_at' => '17:00',
            'ends_at' => '20:00',
        ]);
        $this->assertDatabaseHas('availability_overrides', [
            'availability_schedule_id' => $schedule->id,
            'is_available' => true,
        ]);
        $this->assertDatabaseHas('blackout_dates', [
            'user_id' => $cfm->id,
            'reason' => 'Personal leave',
        ]);
        $this->assertDatabaseHas('booking_questions', [
            'booking_event_type_id' => $type->id,
            'question' => 'What would you like to cover?',
            'is_required' => true,
        ]);
    }

    public function test_admin_management_has_booking_resources(): void
    {
        $this->seedScheduling();

        $admin = User::where('email', 'calendar-owner@gwtracks.com')->firstOrFail();
        $admin->assignRole('admin');

        foreach (['booking-event-types', 'booking-links', 'bookings'] as $resource) {
            $this->actingAs($admin)
                ->get(route('admin.management.resource.index', $resource))
                ->assertOk();
        }
    }

    private function seedScheduling(): void
    {
        $this->seed([
            RankSeeder::class,
            RolePermissionSeeder::class,
            TeamSeeder::class,
            CalendarModuleSeeder::class,
            BookingSchedulingSeeder::class,
        ]);
    }

    /**
     * @return array{0: User, 1: User, 2: BookingEventType, 3: BookingLink, 4: array{starts_at: string, ends_at: string, label: string}}
     */
    private function availableBookingFixture(string $slug): array
    {
        $cfm = User::where('email', 'booking-cfm@gwtracks.com')->firstOrFail();
        $trainee = User::where('email', 'booking-trainee@gwtracks.com')->firstOrFail();
        $type = BookingEventType::query()->where('owner_id', $cfm->id)->where('slug', $slug)->firstOrFail();
        $link = BookingLink::query()->where('booking_event_type_id', $type->id)->firstOrFail();
        $workflow = app(BookingWorkflowService::class);

        for ($daysAhead = 1; $daysAhead <= $type->maximum_booking_days_ahead; $daysAhead++) {
            $date = Carbon::now($link->schedule->timezone)->addDays($daysAhead);
            $slot = $workflow->availableSlots($type, $link, $date, $trainee)[0] ?? null;
            if ($slot) {
                return [$cfm, $trainee, $type, $link, $slot];
            }
        }

        $this->fail('The seeded CFM should have at least one future booking slot.');
    }

    /**
     * @return array<int, string>
     */
    private function requiredAnswers(BookingEventType $type): array
    {
        return $type->questions()
            ->where('is_active', true)
            ->where('is_required', true)
            ->pluck('id')
            ->mapWithKeys(fn (int $id): array => [$id => 'Test booking answer'])
            ->all();
    }
}
