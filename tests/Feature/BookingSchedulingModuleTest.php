<?php

namespace Tests\Feature;

use App\Livewire\Booking\BookingsHub;
use App\Livewire\Booking\MyBookingsList;
use App\Models\AvailabilitySchedule;
use App\Models\Booking;
use App\Models\BookingEventType;
use App\Models\BookingLink;
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
}
