<?php

namespace App\Livewire\Booking;

use App\Models\AvailabilitySchedule;
use App\Models\Booking;
use App\Models\BookingEventType;
use App\Models\BookingLink;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class BookingsHub extends Component
{
    use WithPagination;

    public const TAB_DASHBOARD = 'dashboard';

    public const TAB_AVAILABILITY = 'availability';

    public const TAB_EVENT_TYPES = 'event-types';

    public const TAB_LINKS = 'links';

    public const TAB_REQUESTS = 'requests';

    public const TAB_APPOINTMENTS = 'appointments';

    public const TAB_CALENDAR = 'calendar';

    public const TAB_SETTINGS = 'settings';

    #[Url(except: self::TAB_APPOINTMENTS)]
    public string $tab = self::TAB_APPOINTMENTS;

    public int $panelKey = 0;

    public function mount(): void
    {
        abort_unless($this->canAccessHub(), 403);

        if (! $this->canAccessTab($this->tab)) {
            if (in_array($this->tab, [self::TAB_DASHBOARD, self::TAB_AVAILABILITY, self::TAB_EVENT_TYPES, self::TAB_LINKS, self::TAB_REQUESTS, self::TAB_SETTINGS], true)) {
                abort(403);
            }

            $this->tab = self::TAB_APPOINTMENTS;
        }
    }

    public function setTab(string $tab): void
    {
        if (! $this->canAccessTab($tab)) {
            return;
        }

        $this->tab = $tab;
        $this->panelKey++;
        $this->resetPage();
    }

    #[On('bookings-hub-set-tab')]
    public function onSetTab(string $tab): void
    {
        $this->setTab($tab);
    }

    public function render(): View
    {
        return view('livewire.booking.bookings-hub', [
            'tabs' => $this->availableTabs(),
            'panel' => $this->panelMeta($this->tab),
            'dashboard' => $this->tab === self::TAB_DASHBOARD ? $this->dashboardData() : null,
            'schedules' => $this->tab === self::TAB_AVAILABILITY ? $this->availabilityData() : collect(),
            'eventTypes' => $this->tab === self::TAB_EVENT_TYPES ? $this->eventTypesData() : collect(),
            'links' => $this->tab === self::TAB_LINKS ? $this->linksData() : collect(),
            'requestBookings' => $this->tab === self::TAB_REQUESTS ? $this->requestsData() : null,
            'calendarBookings' => $this->tab === self::TAB_CALENDAR ? $this->calendarData() : collect(),
        ])->layout('layouts.app');
    }

    /**
     * @return list<array{key: string, label: string, permission: string}>
     */
    private function availableTabs(): array
    {
        return collect([
            ['key' => self::TAB_DASHBOARD, 'label' => 'Dashboard', 'permission' => 'view booking dashboard'],
            ['key' => self::TAB_AVAILABILITY, 'label' => 'My Availability', 'permission' => 'manage own availability'],
            ['key' => self::TAB_EVENT_TYPES, 'label' => 'Event Types', 'permission' => 'manage own booking event types'],
            ['key' => self::TAB_LINKS, 'label' => 'Booking Links', 'permission' => 'create booking links'],
            ['key' => self::TAB_REQUESTS, 'label' => 'Requests', 'permission' => 'approve booking requests'],
            ['key' => self::TAB_APPOINTMENTS, 'label' => 'My Appointments', 'permission' => 'view own bookings'],
            ['key' => self::TAB_CALENDAR, 'label' => 'Session Calendar', 'permission' => 'view own bookings'],
            ['key' => self::TAB_SETTINGS, 'label' => 'Settings', 'permission' => 'manage booking settings'],
        ])->filter(fn (array $tab) => $this->canAccessTab($tab['key']))->values()->all();
    }

    /**
     * @return array{title: string, subtitle: string}
     */
    private function panelMeta(string $tab): array
    {
        return match ($tab) {
            self::TAB_DASHBOARD => [
                'title' => 'Mentor Booking Dashboard',
                'subtitle' => 'Manage CFM availability, booking links, pending requests, and mentor session outcomes connected to Field Apprenticeship.',
            ],
            self::TAB_AVAILABILITY => [
                'title' => 'My Availability',
                'subtitle' => 'Calendly-style weekly windows, overrides, blackout dates, and timezone-aware availability for CFM sessions.',
            ],
            self::TAB_EVENT_TYPES => [
                'title' => 'Booking Event Types',
                'subtitle' => 'Reusable mentor session templates with duration, buffers, booking windows, approval rules, and custom questions.',
            ],
            self::TAB_LINKS => [
                'title' => 'Booking Links',
                'subtitle' => 'Personal, event-specific, apprentice-only, invite-only, one-time, and expiring booking links.',
            ],
            self::TAB_REQUESTS => [
                'title' => 'Booking Requests',
                'subtitle' => 'Approval inbox for pending, declined, rescheduled, cancelled, completed, and no-show mentor session bookings.',
            ],
            self::TAB_CALENDAR => [
                'title' => 'Mentor Session Calendar',
                'subtitle' => 'Booking-focused calendar for CFM sessions and apprentice coaching appointments.',
            ],
            self::TAB_SETTINGS => [
                'title' => 'Booking Settings',
                'subtitle' => 'Reminder channels, default booking rules, reschedule cutoff, cancellation policy, and future SMS settings.',
            ],
            default => [
                'title' => 'My Appointments',
                'subtitle' => 'Search, filter, and review every mentor session where you are the CFM, trainee, or invited attendee.',
            ],
        };
    }

    /**
     * @return array{
     *     upcomingBookings: \Illuminate\Support\Collection<int, Booking>,
     *     pendingRequests: \Illuminate\Support\Collection<int, Booking>,
     *     eventTypes: \Illuminate\Support\Collection<int, BookingEventType>,
     *     availabilitySchedules: int
     * }
     */
    private function dashboardData(): array
    {
        $userId = (int) auth()->id();

        return [
            'upcomingBookings' => Booking::query()
                ->where(fn ($query) => $query->where('cfm_id', $userId)->orWhere('trainee_id', $userId))
                ->whereIn('status', ['confirmed', 'pending_approval'])
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at')
                ->limit(6)
                ->get(),
            'pendingRequests' => Booking::query()
                ->where('cfm_id', $userId)
                ->where('status', 'pending_approval')
                ->latest()
                ->limit(6)
                ->get(),
            'eventTypes' => BookingEventType::query()
                ->where('owner_id', $userId)
                ->latest()
                ->limit(6)
                ->get(),
            'availabilitySchedules' => AvailabilitySchedule::query()
                ->where('user_id', $userId)
                ->whereNull('deleted_at')
                ->count(),
        ];
    }

    private function availabilityData()
    {
        return AvailabilitySchedule::query()
            ->where('user_id', auth()->id())
            ->with('rules', 'overrides')
            ->get();
    }

    private function eventTypesData()
    {
        return BookingEventType::query()
            ->where('owner_id', auth()->id())
            ->withCount(['links', 'bookings', 'questions'])
            ->get();
    }

    private function linksData()
    {
        return BookingLink::query()
            ->where('owner_id', auth()->id())
            ->with('eventType', 'apprentice')
            ->latest()
            ->get();
    }

    private function requestsData()
    {
        return Booking::query()
            ->where('cfm_id', auth()->id())
            ->with('eventType', 'trainee')
            ->latest()
            ->paginate(12);
    }

    private function calendarData()
    {
        $userId = (int) auth()->id();

        return Booking::query()
            ->where(fn ($query) => $query->where('cfm_id', $userId)->orWhere('trainee_id', $userId))
            ->where('starts_at', '>=', now()->startOfMonth())
            ->orderBy('starts_at')
            ->get();
    }

    private function canAccessHub(): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return $user->can('view own bookings')
            || $user->can('view booking dashboard')
            || $user->can('manage own availability')
            || $user->can('manage own booking event types')
            || $user->can('create booking links')
            || $user->can('approve booking requests')
            || $user->can('manage booking settings');
    }

    private function canAccessTab(string $tab): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        return match ($tab) {
            self::TAB_DASHBOARD => $user->can('view booking dashboard'),
            self::TAB_AVAILABILITY => $user->can('manage own availability'),
            self::TAB_EVENT_TYPES => $user->can('manage own booking event types'),
            self::TAB_LINKS => $user->can('create booking links'),
            self::TAB_REQUESTS => $user->can('approve booking requests'),
            self::TAB_APPOINTMENTS, self::TAB_CALENDAR => $user->can('view own bookings'),
            self::TAB_SETTINGS => $user->can('manage booking settings'),
            default => false,
        };
    }
}
