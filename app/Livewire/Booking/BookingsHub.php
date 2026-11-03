<?php

namespace App\Livewire\Booking;

use App\Models\AvailabilitySchedule;
use App\Models\AvailabilityRule;
use App\Models\Booking;
use App\Models\BookingEventType;
use App\Models\BookingLink;
use App\Models\AvailabilityOverride;
use App\Models\BlackoutDate;
use App\Models\BookingQuestion;
use App\Models\BookingReschedule;
use App\Services\BookingWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
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

    public string $scheduleName = '';

    public string $scheduleTimezone = 'America/Vancouver';

    public ?int $ruleScheduleId = null;

    public int $ruleWeekday = 1;

    public string $ruleStartsAt = '09:00';

    public string $ruleEndsAt = '12:00';

    public ?int $overrideScheduleId = null;

    public string $overrideDate = '';

    public bool $overrideAvailable = true;

    public string $overrideStartsAt = '09:00';

    public string $overrideEndsAt = '12:00';

    public string $overrideReason = '';

    public string $blackoutStartsOn = '';

    public string $blackoutEndsOn = '';

    public string $blackoutReason = '';

    public bool $blackoutRecurring = false;

    public string $eventTitle = '';

    public string $eventDescription = '';

    public int $eventDuration = 30;

    public bool $eventApprovalRequired = false;

    public string $eventVisibility = 'assigned_apprentices';

    public ?int $linkEventTypeId = null;

    public ?int $linkScheduleId = null;

    public string $linkName = '';

    public ?string $declineReason = null;

    public ?int $questionEventTypeId = null;

    public string $questionText = '';

    public string $questionType = 'short_answer';

    public string $questionOptions = '';

    public bool $questionRequired = false;

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

    public function createSchedule(): void
    {
        abort_unless(auth()->user()->can('manage own availability'), 403);
        $validated = $this->validate([
            'scheduleName' => ['required', 'string', 'max:120'],
            'scheduleTimezone' => ['required', 'timezone'],
        ]);

        $schedule = AvailabilitySchedule::query()->create([
            'user_id' => auth()->id(),
            'name' => $validated['scheduleName'],
            'timezone' => $validated['scheduleTimezone'],
            'is_default' => ! AvailabilitySchedule::query()->where('user_id', auth()->id())->exists(),
            'is_active' => true,
        ]);
        $this->ruleScheduleId = $schedule->id;
        $this->scheduleName = '';
        $this->panelKey++;
        session()->flash('booking-status', 'Availability schedule created.');
    }

    public function addAvailabilityRule(): void
    {
        abort_unless(auth()->user()->can('manage own availability'), 403);
        $validated = $this->validate([
            'ruleScheduleId' => ['required', 'integer'],
            'ruleWeekday' => ['required', 'integer', 'between:1,7'],
            'ruleStartsAt' => ['required', 'date_format:H:i'],
            'ruleEndsAt' => ['required', 'date_format:H:i', 'after:ruleStartsAt'],
        ]);
        $schedule = AvailabilitySchedule::query()
            ->where('user_id', auth()->id())
            ->whereKey($validated['ruleScheduleId'])
            ->firstOrFail();

        $overlap = $schedule->rules()
            ->where('weekday', $validated['ruleWeekday'])
            ->where('is_available', true)
            ->where('starts_at', '<', $validated['ruleEndsAt'])
            ->where('ends_at', '>', $validated['ruleStartsAt'])
            ->exists();
        if ($overlap) {
            $this->addError('ruleStartsAt', 'This time overlaps an existing availability window.');

            return;
        }

        $schedule->rules()->create([
            'weekday' => $validated['ruleWeekday'],
            'starts_at' => $validated['ruleStartsAt'],
            'ends_at' => $validated['ruleEndsAt'],
            'is_available' => true,
            'sort_order' => $schedule->rules()->where('weekday', $validated['ruleWeekday'])->count() + 1,
        ]);
        $this->panelKey++;
        session()->flash('booking-status', 'Weekly availability window added.');
    }

    public function removeAvailabilityRule(int $ruleId): void
    {
        abort_unless(auth()->user()->can('manage own availability'), 403);
        $rule = AvailabilityRule::query()
            ->whereHas('schedule', fn ($query) => $query->where('user_id', auth()->id()))
            ->findOrFail($ruleId);
        $rule->delete();
        $this->panelKey++;
    }

    public function saveAvailabilityOverride(): void
    {
        abort_unless(auth()->user()->can('manage own availability'), 403);
        $validated = $this->validate([
            'overrideScheduleId' => ['required', 'integer'],
            'overrideDate' => ['required', 'date', 'after_or_equal:today'],
            'overrideAvailable' => ['boolean'],
            'overrideStartsAt' => [$this->overrideAvailable ? 'required' : 'nullable', 'date_format:H:i'],
            'overrideEndsAt' => [$this->overrideAvailable ? 'required' : 'nullable', 'date_format:H:i', ...($this->overrideAvailable ? ['after:overrideStartsAt'] : [])],
            'overrideReason' => ['nullable', 'string', 'max:255'],
        ]);
        $schedule = AvailabilitySchedule::query()
            ->where('user_id', auth()->id())
            ->findOrFail($validated['overrideScheduleId']);
        $schedule->overrides()->create([
            'override_date' => $validated['overrideDate'],
            'starts_at' => $validated['overrideAvailable'] ? $validated['overrideStartsAt'] : null,
            'ends_at' => $validated['overrideAvailable'] ? $validated['overrideEndsAt'] : null,
            'is_available' => $validated['overrideAvailable'],
            'reason' => $validated['overrideReason'],
        ]);
        $this->panelKey++;
        session()->flash('booking-status', 'Date-specific availability saved.');
    }

    public function createBlackoutDate(): void
    {
        abort_unless(auth()->user()->can('manage own availability'), 403);
        $validated = $this->validate([
            'blackoutStartsOn' => ['required', 'date', 'after_or_equal:today'],
            'blackoutEndsOn' => ['nullable', 'date', 'after_or_equal:blackoutStartsOn'],
            'blackoutReason' => ['nullable', 'string', 'max:255'],
            'blackoutRecurring' => ['boolean'],
        ]);
        BlackoutDate::query()->create([
            'user_id' => auth()->id(),
            'starts_on' => $validated['blackoutStartsOn'],
            'ends_on' => $validated['blackoutEndsOn'] ?: null,
            'reason' => $validated['blackoutReason'],
            'is_recurring' => $validated['blackoutRecurring'],
        ]);
        $this->panelKey++;
        session()->flash('booking-status', 'Blackout dates saved.');
    }

    public function removeBlackoutDate(int $blackoutId): void
    {
        abort_unless(auth()->user()->can('manage own availability'), 403);
        BlackoutDate::query()->where('user_id', auth()->id())->findOrFail($blackoutId)->delete();
        $this->panelKey++;
    }

    public function createEventType(): void
    {
        abort_unless(auth()->user()->can('manage own booking event types'), 403);
        $validated = $this->validate([
            'eventTitle' => ['required', 'string', 'max:120'],
            'eventDescription' => ['nullable', 'string', 'max:2000'],
            'eventDuration' => ['required', 'integer', 'between:5,240'],
            'eventApprovalRequired' => ['boolean'],
            'eventVisibility' => ['required', 'in:assigned_apprentices,public,private'],
        ]);
        $slug = Str::slug($validated['eventTitle']);
        if (BookingEventType::query()->where('owner_id', auth()->id())->where('slug', $slug)->exists()) {
            $this->addError('eventTitle', 'You already have an event type with this title.');

            return;
        }

        $eventType = BookingEventType::query()->create([
            'owner_id' => auth()->id(),
            'title' => $validated['eventTitle'],
            'slug' => $slug,
            'description' => $validated['eventDescription'],
            'duration_minutes' => $validated['eventDuration'],
            'approval_required' => $validated['eventApprovalRequired'],
            'visibility' => $validated['eventVisibility'],
            'allowed_attendee_type' => $validated['eventVisibility'] === 'public' ? 'public' : 'assigned_apprentices',
            'is_active' => true,
        ]);
        $this->eventTitle = '';
        $this->eventDescription = '';
        $this->panelKey++;
        session()->flash('booking-status', 'Booking event type created.');
    }

    public function addBookingQuestion(): void
    {
        abort_unless(auth()->user()->can('manage own booking event types'), 403);
        $validated = $this->validate([
            'questionEventTypeId' => ['required', 'integer'],
            'questionText' => ['required', 'string', 'max:500'],
            'questionType' => ['required', 'in:short_answer,long_answer,multiple_choice,yes_no'],
            'questionOptions' => ['nullable', 'string', 'max:1000'],
            'questionRequired' => ['boolean'],
        ]);
        $eventType = BookingEventType::query()->where('owner_id', auth()->id())->findOrFail($validated['questionEventTypeId']);
        $options = $validated['questionType'] === 'multiple_choice'
            ? collect(explode(',', $validated['questionOptions']))->map(fn (string $option) => trim($option))->filter()->unique()->values()->all()
            : null;
        if ($validated['questionType'] === 'multiple_choice' && count($options) < 2) {
            $this->addError('questionOptions', 'Add at least two comma-separated choices.');

            return;
        }

        BookingQuestion::query()->create([
            'booking_event_type_id' => $eventType->id,
            'question' => $validated['questionText'],
            'type' => $validated['questionType'],
            'options' => $options,
            'is_required' => $validated['questionRequired'],
            'sort_order' => $eventType->questions()->count() + 1,
            'is_active' => true,
        ]);
        $eventType->update(['custom_questions_enabled' => true]);
        $this->questionText = '';
        $this->questionOptions = '';
        $this->panelKey++;
        session()->flash('booking-status', 'Booking question added.');
    }

    public function removeBookingQuestion(int $questionId): void
    {
        abort_unless(auth()->user()->can('manage own booking event types'), 403);
        BookingQuestion::query()
            ->whereHas('eventType', fn ($query) => $query->where('owner_id', auth()->id()))
            ->findOrFail($questionId)
            ->delete();
        $this->panelKey++;
    }

    public function createBookingLink(): void
    {
        abort_unless(auth()->user()->can('create booking links'), 403);
        $validated = $this->validate([
            'linkEventTypeId' => ['required', 'integer'],
            'linkScheduleId' => ['required', 'integer'],
            'linkName' => ['required', 'string', 'max:120'],
        ]);
        $eventType = BookingEventType::query()->where('owner_id', auth()->id())->findOrFail($validated['linkEventTypeId']);
        $schedule = AvailabilitySchedule::query()->where('user_id', auth()->id())->findOrFail($validated['linkScheduleId']);
        $link = BookingLink::query()->create([
            'owner_id' => auth()->id(),
            'booking_event_type_id' => $eventType->id,
            'availability_schedule_id' => $schedule->id,
            'name' => $validated['linkName'],
            'slug' => $eventType->slug,
            'token' => Str::random(48),
            'link_type' => 'event_type',
            'visibility' => $eventType->visibility === 'public' ? 'public' : 'invite_only',
            'is_active' => true,
        ]);
        $this->linkName = '';
        $this->panelKey++;
        session()->flash('booking-status', 'Booking link created.');
        session()->flash('booking-link-url', route('bookings.invite', $link->token));
    }

    public function toggleEventType(int $eventTypeId): void
    {
        abort_unless(auth()->user()->can('manage own booking event types'), 403);
        $eventType = BookingEventType::query()->where('owner_id', auth()->id())->findOrFail($eventTypeId);
        $eventType->update(['is_active' => ! $eventType->is_active]);
        $this->panelKey++;
    }

    public function toggleBookingLink(int $linkId): void
    {
        abort_unless(auth()->user()->can('create booking links'), 403);
        $link = BookingLink::query()->where('owner_id', auth()->id())->findOrFail($linkId);
        $link->update(['is_active' => ! $link->is_active]);
        $this->panelKey++;
    }

    public function approveBooking(int $bookingId, BookingWorkflowService $workflow): void
    {
        $booking = Booking::query()->where('cfm_id', auth()->id())->findOrFail($bookingId);
        $workflow->approve($booking, auth()->user());
        $this->panelKey++;
        session()->flash('booking-status', 'Booking approved and added to your calendar.');
    }

    public function declineBooking(int $bookingId, BookingWorkflowService $workflow): void
    {
        $booking = Booking::query()->where('cfm_id', auth()->id())->findOrFail($bookingId);
        $workflow->decline($booking, auth()->user(), $this->declineReason);
        $this->declineReason = null;
        $this->panelKey++;
        session()->flash('booking-status', 'Booking request declined.');
    }

    public function cancelBooking(int $bookingId, BookingWorkflowService $workflow): void
    {
        $booking = Booking::query()
            ->where(fn ($query) => $query->where('cfm_id', auth()->id())->orWhere('trainee_id', auth()->id()))
            ->findOrFail($bookingId);
        $workflow->cancel($booking, auth()->user());
        $this->panelKey++;
        session()->flash('booking-status', 'Booking cancelled.');
    }

    public function resolveReschedule(int $rescheduleId, bool $approve, BookingWorkflowService $workflow): void
    {
        $reschedule = BookingReschedule::query()
            ->whereHas('booking', fn ($query) => $query->where('cfm_id', auth()->id()))
            ->findOrFail($rescheduleId);
        $workflow->resolveReschedule($reschedule, auth()->user(), $approve);
        $this->panelKey++;
        session()->flash('booking-status', $approve ? 'Reschedule approved.' : 'Reschedule declined.');
    }

    public function completeBooking(int $bookingId, BookingWorkflowService $workflow): void
    {
        $booking = Booking::query()->where('cfm_id', auth()->id())->findOrFail($bookingId);
        $workflow->complete($booking, auth()->user());
        $this->panelKey++;
        session()->flash('booking-status', 'Session marked completed.');
    }

    public function markBookingNoShow(int $bookingId, BookingWorkflowService $workflow): void
    {
        $booking = Booking::query()->where('cfm_id', auth()->id())->findOrFail($bookingId);
        $workflow->markNoShow($booking, auth()->user());
        $this->panelKey++;
        session()->flash('booking-status', 'Session marked as no-show.');
    }

    public function render(): View
    {
        return view('livewire.booking.bookings-hub', [
            'tabs' => $this->availableTabs(),
            'panel' => $this->panelMeta($this->tab),
            'dashboard' => $this->tab === self::TAB_DASHBOARD ? $this->dashboardData() : null,
            'schedules' => $this->tab === self::TAB_AVAILABILITY ? $this->availabilityData() : collect(),
            'blackouts' => $this->tab === self::TAB_AVAILABILITY ? BlackoutDate::query()->where('user_id', auth()->id())->where('starts_on', '>=', now()->toDateString())->orderBy('starts_on')->limit(10)->get() : collect(),
            'eventTypes' => $this->tab === self::TAB_EVENT_TYPES ? $this->eventTypesData() : collect(),
            'links' => $this->tab === self::TAB_LINKS ? $this->linksData() : collect(),
            'linkableEventTypes' => $this->tab === self::TAB_LINKS ? BookingEventType::query()->where('owner_id', auth()->id())->where('is_active', true)->orderBy('title')->get() : collect(),
            'linkableSchedules' => $this->tab === self::TAB_LINKS ? AvailabilitySchedule::query()->where('user_id', auth()->id())->where('is_active', true)->orderByDesc('is_default')->get() : collect(),
            'requestBookings' => $this->tab === self::TAB_REQUESTS ? $this->requestsData() : null,
            'rescheduleRequests' => $this->tab === self::TAB_REQUESTS ? BookingReschedule::query()
                ->where('status', 'pending')
                ->whereHas('booking', fn ($query) => $query->where('cfm_id', auth()->id()))
                ->with(['booking.eventType', 'booking.trainee', 'requestedBy'])
                ->latest()
                ->get() : collect(),
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
