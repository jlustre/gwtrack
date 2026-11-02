<?php

namespace App\Http\Controllers;

use App\Services\Calendar\CalendarMutationService;
use App\Services\Calendar\PublicHolidayCatalog;
use App\Services\Calendar\PublicHolidaySyncService;
use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use App\Models\CalendarEventActivityLog;
use App\Models\CalendarEventAttendee;
use App\Models\CalendarEventNote;
use App\Models\CalendarEventRecurrence;
use App\Models\CalendarEventReminder;
use App\Models\CalendarEventType;
use App\Models\CalendarScheduleBlock;
use App\Models\CalendarScheduleBlockOverride;
use App\Models\User;
use App\Models\UserCalendarPreference;
use App\Services\CalendarEventDisplayService;
use App\Services\CalendarScheduleBlockService;
use App\Services\CalendarShareService;
use App\Services\CalendarSpanLayoutService;
use App\Services\DashboardActivityService;
use App\Services\Notifications\NotificationOrchestrator;
use App\Support\LocationOptions;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct(
        private readonly CalendarShareService $calendarShare,
        private readonly CalendarScheduleBlockService $scheduleBlocks,
        private readonly CalendarEventDisplayService $eventDisplay,
        private readonly CalendarSpanLayoutService $spanLayout,
        private readonly NotificationOrchestrator $notifications,
        private readonly DashboardActivityService $dashboardActivity,
        private readonly CalendarMutationService $mutations,
    ) {}

    public function index(Request $request): View
    {
        return $this->workspace($request, $request->string('view', 'month')->value());
    }

    public function month(Request $request): View
    {
        return $this->workspace($request, 'month');
    }

    public function week(Request $request): View
    {
        return $this->workspace($request, 'week');
    }

    public function day(Request $request): View
    {
        return $this->workspace($request, 'day');
    }

    public function agenda(Request $request): View
    {
        return $this->workspace($request, 'agenda');
    }

    private function workspace(Request $request, string $viewMode): View
    {
        abort_unless($request->user()->hasAnyPermission(['view calendar', 'view shared calendar events', 'manage organization calendar']), 403);

        $viewMode = in_array($viewMode, ['month', 'week', 'work-week', 'day', 'agenda'], true) ? $viewMode : 'month';

        return view('calendar.workspace', [
            'viewMode' => $viewMode,
            'date' => $request->input('date', now()->toDateString()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('create calendar events'), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'calendar_event_type_id' => ['nullable', 'integer', 'exists:calendar_event_types,id'],
            'calendar_category_id' => ['nullable', 'integer', 'exists:calendar_categories,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'timezone' => ['required', 'string', Rule::in(array_keys(LocationOptions::timezones()))],
            'is_all_day' => ['nullable', 'boolean'],
            'is_recurring' => ['nullable', 'boolean'],
            'recurrence_frequency' => ['nullable', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'recurrence_interval' => ['nullable', 'integer', 'min:1', 'max:12'],
            'recurrence_weekdays' => ['nullable', 'array'],
            'recurrence_weekdays.*' => ['string', Rule::in(['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'])],
            'recurrence_end_type' => ['nullable', Rule::in(['never', 'after', 'on'])],
            'recurrence_ends_after_occurrences' => ['nullable', 'integer', 'min:1', 'max:120'],
            'recurrence_ends_on' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'visibility' => ['required', Rule::in(['private', 'shared_team', 'shared_downline', 'public_organization'])],
            'status' => ['required', Rule::in(['scheduled', 'draft'])],
            'notes' => ['nullable', 'string'],
            'attendee_user_ids' => ['nullable', 'array'],
            'attendee_user_ids.*' => ['integer', 'exists:users,id'],
            'external_attendee_name' => ['nullable', 'string', 'max:255'],
            'external_attendee_email' => ['nullable', 'email', 'max:255'],
            'reminder_minutes' => ['nullable', 'array'],
            'reminder_minutes.*' => ['integer', 'min:0', 'max:43200'],
            'reminder_channel' => ['required', Rule::in(['in_app', 'email', 'both'])],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ]);

        try {
            $this->mutations->createEvent($request->user(), $validated);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        return redirect()
            ->to($this->safeReturnUrl($validated['return_to'] ?? route('calendar.index')))
            ->with('status', 'Calendar event created.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('view calendar'), 403);

        $user = $request->user();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('calendar_categories', 'name')->where(fn ($query) => $query->where('user_id', $user->id)),
            ],
            'color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'is_public' => ['nullable', 'boolean'],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $this->mutations->storeCategory($user, $validated);

        return redirect()
            ->to($this->safeReturnUrl($validated['return_to'] ?? route('calendar.index')))
            ->with('status', 'Personal calendar created.');
    }

    public function updateCategory(Request $request, CalendarCategory $category): RedirectResponse
    {
        abort_unless($this->mutations->canManageCategory($request->user(), $category), 403);

        $nameRule = $category->isSystem()
            ? Rule::unique('calendar_categories', 'name')->ignore($category->id)->where(fn ($query) => $query->whereNull('user_id'))
            : Rule::unique('calendar_categories', 'name')->ignore($category->id)->where(fn ($query) => $query->where('user_id', $category->user_id));

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', $nameRule],
            'color' => ['required', 'regex:/^#[A-Fa-f0-9]{6}$/'],
            'is_public' => ['nullable', 'boolean'],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $this->mutations->updateCategory($request->user(), $category, $validated);

        return redirect()
            ->to($this->safeReturnUrl($validated['return_to'] ?? route('calendar.index')))
            ->with('status', 'Calendar updated.');
    }

    public function destroyCategory(Request $request, CalendarCategory $category): RedirectResponse
    {
        $this->mutations->destroyCategory($request->user(), $category);

        return redirect()
            ->to($this->safeReturnUrl($request->input('return_to', route('calendar.index'))))
            ->with('status', 'Calendar deleted.');
    }

    public function show(Request $request, CalendarEvent $event): View
    {
        $this->authorize('view', $event);

        return view('events.show', [
            'event' => $event->load(['organizer', 'type', 'category', 'attendees.user', 'relatedProspect.stage', 'relatedProspect.funnel', 'reminders', 'notes.creator']),
        ]);
    }

    public function settings(Request $request): View
    {
        $user = $request->user();
        $preference = UserCalendarPreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'timezone' => $user->profile?->timezone ?? config('app.timezone'),
            ]
        );

        return view('events.settings', [
            'preference' => $preference,
            'categories' => $this->visibleCategories($user),
            'weeklyBlocks' => $this->scheduleBlocks->weeklyBlocksFor($user),
            'blockOverrides' => $this->scheduleBlocks->overridesFor($user),
            'blockTypes' => CalendarScheduleBlock::TYPES,
            'weekdayLabels' => $this->weekdayLabels(),
        ]);
    }

    public function storeScheduleBlock(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('view calendar'), 403);

        $validated = $this->validateScheduleBlock($request);

        $this->scheduleBlocks->storeWeeklyBlock($request->user(), $validated);

        return redirect()
            ->to($this->safeReturnUrl($validated['return_to'] ?? route('calendar.settings')))
            ->with('status', 'Weekly schedule block saved.');
    }

    public function updateScheduleBlock(Request $request, CalendarScheduleBlock $block): RedirectResponse
    {
        abort_unless($block->user_id === $request->user()->id, 403);

        $validated = $this->validateScheduleBlock($request);

        $this->scheduleBlocks->updateWeeklyBlock($block, $validated);

        return redirect()
            ->to($this->safeReturnUrl($validated['return_to'] ?? route('calendar.settings')))
            ->with('status', 'Schedule block updated.');
    }

    public function destroyScheduleBlock(Request $request, CalendarScheduleBlock $block): RedirectResponse
    {
        abort_unless($block->user_id === $request->user()->id, 403);

        $block->delete();

        return redirect()
            ->to($this->safeReturnUrl($request->input('return_to', route('calendar.settings'))))
            ->with('status', 'Schedule block removed.');
    }

    public function storeScheduleBlockOverride(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermissionTo('view calendar'), 403);

        $validated = $this->validateScheduleBlockOverride($request);

        $this->scheduleBlocks->storeOverride($request->user(), $validated);

        return redirect()
            ->to($this->safeReturnUrl($validated['return_to'] ?? route('calendar.settings')))
            ->with('status', 'Date block saved.');
    }

    public function updateScheduleBlockOverride(Request $request, CalendarScheduleBlockOverride $override): RedirectResponse
    {
        abort_unless($override->user_id === $request->user()->id, 403);

        $validated = $this->validateScheduleBlockOverride($request, updating: true);

        $this->scheduleBlocks->updateOverride($override, $validated);

        return redirect()
            ->to($this->safeReturnUrl($validated['return_to'] ?? route('calendar.settings')))
            ->with('status', 'Date block updated.');
    }

    public function destroyScheduleBlockOverride(Request $request, CalendarScheduleBlockOverride $override): RedirectResponse
    {
        abort_unless($override->user_id === $request->user()->id, 403);

        $override->delete();

        return redirect()
            ->to($this->safeReturnUrl($request->input('return_to', route('calendar.settings'))))
            ->with('status', 'Date block removed.');
    }

    public function export(Request $request): Response
    {
        abort_unless($request->user()->hasAnyPermission(['view calendar', 'manage team calendar', 'manage organization calendar']), 403);

        $selectedCalendarIds = $this->selectedCalendarIds($request);

        $events = $this->visibleEventsQuery($request, $selectedCalendarIds)
            ->with(['organizer', 'type', 'category'])
            ->orderBy('starts_at')
            ->limit(1000)
            ->get();

        $csv = collect([['Title', 'Type', 'Category', 'Starts', 'Ends', 'Status', 'Visibility', 'Organizer', 'Location']])
            ->merge($events->map(fn (CalendarEvent $event): array => [
                $event->title,
                $event->type?->name,
                $event->category?->name,
                $event->starts_at?->toDateTimeString(),
                $event->ends_at?->toDateTimeString(),
                $event->status,
                $event->visibility,
                $event->organizer?->name,
                $event->location ?: $event->meeting_link,
            ]))
            ->map(fn (array $row): string => collect($row)->map(fn ($value): string => '"'.str_replace('"', '""', (string) $value).'"')->implode(','))
            ->implode("\n");

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="gwtracks-calendar-events.csv"',
        ]);
    }

    private function visibleEventsQuery(Request $request, ?array $selectedCalendarIds = null): Builder
    {
        $user = $request->user();
        $selectedCalendarIds ??= $this->selectedCalendarIds($request);
        $sharedCfmOrganizerIds = $this->calendarShare->sharedCfmOrganizerIdsFor($user);

        return CalendarEvent::query()
            ->where(function (Builder $query): void {
                $query->whereNull('calendar_category_id')
                    ->orWhereHas('category', fn (Builder $query) => $query->where('is_active', true));
            })
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $search = trim((string) $request->string('q'));

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category'), fn (Builder $query) => $query->where('calendar_category_id', $request->integer('category')))
            ->when(true, function (Builder $query) use ($selectedCalendarIds, $sharedCfmOrganizerIds): void {
                if (empty($selectedCalendarIds) && empty($sharedCfmOrganizerIds)) {
                    $query->whereRaw('1 = 0');

                    return;
                }

                $query->where(function (Builder $query) use ($selectedCalendarIds, $sharedCfmOrganizerIds): void {
                    if (! empty($selectedCalendarIds)) {
                        $query->whereIn('calendar_category_id', $selectedCalendarIds);
                    }

                    if (! empty($sharedCfmOrganizerIds)) {
                        $query->orWhere(function (Builder $query) use ($sharedCfmOrganizerIds): void {
                            $query->whereIn('organizer_id', $sharedCfmOrganizerIds)
                                ->where('visibility', '!=', 'private');
                        });
                    }
                });
            })
            ->when($request->filled('type'), fn (Builder $query) => $query->where('calendar_event_type_id', $request->integer('type')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->when($request->filled('visibility'), fn (Builder $query) => $query->where('visibility', $request->string('visibility')))
            ->where(function (Builder $query) use ($user, $sharedCfmOrganizerIds): void {
                if ($user->hasAnyPermission(['manage organization calendar', 'view private events'])) {
                    return;
                }

                $query->where('organizer_id', $user->id)
                    ->orWhereHas('attendees', fn (Builder $query) => $query->where('user_id', $user->id))
                    ->orWhere(function (Builder $query) use ($user): void {
                        $query->whereIn('visibility', ['public_organization', 'shared_downline'])
                            ->where(fn (Builder $query) => $query->where('visibility', 'public_organization')->orWhere('organizer_id', $user->sponsor_id));
                    })
                    ->orWhereHas('visibilityRules', function (Builder $query) use ($user): void {
                        $query->where('user_id', $user->id)
                            ->orWhere('team_id', $user->team_id)
                            ->orWhereIn('role_name', $user->getRoleNames()->all());
                    });

                if (! empty($sharedCfmOrganizerIds)) {
                    $query->orWhere(function (Builder $query) use ($sharedCfmOrganizerIds): void {
                        $query->whereIn('organizer_id', $sharedCfmOrganizerIds)
                            ->where('visibility', '!=', 'private');
                    });
                }
            });
    }

    private function attendeeUsers(Request $request): Collection
    {
        $user = $request->user();

        return User::query()
            ->where('id', '!=', $user->id)
            ->whereNull('deleted_at')
            ->when(
                ! $user->hasAnyPermission(['manage organization calendar', 'view all teams']),
                fn (Builder $query) => $query->where('team_id', $user->team_id)
            )
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'email']);
    }

    private function selectedCalendarIds(Request $request): array
    {
        $preference = $this->calendarPreference($request);

        $visibleIds = $this->visibleCalendarIds($request->user());

        if ($request->has('calendars_filter')) {
            $selectedIds = collect(Arr::wrap($request->input('category_ids', [])))
                ->filter(fn ($id): bool => is_numeric($id))
                ->map(fn ($id): int => (int) $id)
                ->intersect($visibleIds)
                ->values()
                ->all();

            $preference->update(['visible_calendar_categories' => $selectedIds]);

            return $selectedIds;
        }

        return collect($preference->visible_calendar_categories ?? $this->defaultVisibleCalendarIds($request->user()))
            ->intersect($visibleIds)
            ->values()
            ->all();
    }

    private function calendarPreference(Request $request): UserCalendarPreference
    {
        return UserCalendarPreference::firstOrCreate(
            ['user_id' => $request->user()->id],
            [
                'default_view' => 'month',
                'timezone' => $request->user()->profile?->timezone ?? 'PST',
                'visible_calendar_categories' => $this->defaultVisibleCalendarIds($request->user()),
                'show_weekends' => true,
            ]
        );
    }

    private function visibleCategories(User $user): Collection
    {
        return CalendarCategory::query()
            ->visibleTo($user)
            ->orderByRaw('user_id is null desc')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function regularVisibleCategories(User $user): Collection
    {
        return $this->visibleCategories($user)
            ->reject(fn (CalendarCategory $category): bool => $this->isHolidayCategory($category))
            ->values();
    }

    /**
     * @return Collection<int, CalendarCategory>
     */
    private function holidayCategories(User $user): Collection
    {
        return $this->visibleCategories($user)
            ->filter(fn (CalendarCategory $category): bool => $this->isHolidayCategory($category))
            ->values();
    }

    private function isHolidayCategory(CalendarCategory $category): bool
    {
        return in_array($category->slug, [
            PublicHolidayCatalog::US_CATEGORY_SLUG,
            PublicHolidayCatalog::CANADA_CATEGORY_SLUG,
        ], true);
    }

    private function assignableCategories(User $user): Collection
    {
        return CalendarCategory::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($user): void {
                $query->whereNull('user_id')
                    ->orWhere('user_id', $user->id);
            })
            ->orderByRaw('user_id is null desc')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    private function visibleCalendarIds(User $user): array
    {
        return $this->visibleCategories($user)
            ->pluck('id')
            ->map(fn (int $id): int => $id)
            ->all();
    }

    private function defaultVisibleCalendarIds(User $_user): array
    {
        $personalId = CalendarCategory::query()
            ->where('slug', 'personal')
            ->whereNull('user_id')
            ->where('is_active', true)
            ->value('id');

        return $personalId ? [(int) $personalId] : [];
    }

    private function canManageCategory(User $user, CalendarCategory $category): bool
    {
        if ($category->isOwnedBy($user)) {
            return true;
        }

        return $category->isSystem() && $user->hasPermissionTo('manage organization calendar');
    }

    private function uniqueCategorySlug(int $userId, string $name): string
    {
        $base = 'u'.$userId.'-'.Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (CalendarCategory::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function safeReturnUrl(string $url): string
    {
        return str_starts_with($url, url('/')) || str_starts_with($url, '/')
            ? $url
            : route('calendar.index');
    }

    private function recurrenceData(array $validated): array
    {
        $frequency = $validated['recurrence_frequency'] ?? 'weekly';
        $interval = (int) ($validated['recurrence_interval'] ?? 1);
        $start = CarbonImmutable::parse($validated['starts_at']);
        $weekdays = $frequency === 'weekly'
            ? array_values(array_unique($validated['recurrence_weekdays'] ?? [$this->weekdayCode($start)]))
            : null;
        $endType = $validated['recurrence_end_type'] ?? 'never';
        $endsAfter = $endType === 'after' ? (int) ($validated['recurrence_ends_after_occurrences'] ?? 12) : null;
        $endsOn = $endType === 'on' ? $validated['recurrence_ends_on'] ?? null : null;

        $ruleParts = [
            'FREQ='.strtoupper($frequency),
            'INTERVAL='.$interval,
        ];

        if ($weekdays) {
            $ruleParts[] = 'BYDAY='.implode(',', $weekdays);
        }

        if ($endsAfter) {
            $ruleParts[] = 'COUNT='.$endsAfter;
        }

        if ($endsOn) {
            $ruleParts[] = 'UNTIL='.CarbonImmutable::parse($endsOn)->format('Ymd');
        }

        return [
            'frequency' => $frequency,
            'interval' => $interval,
            'weekdays' => $weekdays,
            'ends_after_occurrences' => $endsAfter,
            'ends_on' => $endsOn,
            'rule' => implode(';', $ruleParts),
        ];
    }

    private function weekdayCode(CarbonImmutable $date): string
    {
        return match ($date->dayOfWeekIso) {
            1 => 'MO',
            2 => 'TU',
            3 => 'WE',
            4 => 'TH',
            5 => 'FR',
            6 => 'SA',
            default => 'SU',
        };
    }

    private function dateRange(CarbonImmutable $currentDate, string $viewMode): array
    {
        return match ($viewMode) {
            'day' => [$currentDate->startOfDay(), $currentDate->endOfDay()],
            'agenda' => [$currentDate->startOfDay(), $currentDate->addDays(30)->endOfDay()],
            'week', 'work-week' => [$currentDate->startOfWeek(), $currentDate->endOfWeek()],
            default => [$currentDate->startOfMonth()->startOfWeek(), $currentDate->endOfMonth()->endOfWeek()],
        };
    }

    private function shiftedDate(CarbonImmutable $currentDate, string $viewMode, int $direction): string
    {
        return match ($viewMode) {
            'day' => $currentDate->addDays($direction)->toDateString(),
            'agenda' => $currentDate->addWeeks($direction)->toDateString(),
            'week', 'work-week' => $currentDate->addWeeks($direction)->toDateString(),
            default => $currentDate->addMonthsNoOverflow($direction)->toDateString(),
        };
    }

    private function miniCalendarDays(CarbonImmutable $date): Collection
    {
        return $this->daysBetween($date->startOfMonth()->startOfWeek(), $date->endOfMonth()->endOfWeek());
    }

    private function monthDays(CarbonImmutable $date): Collection
    {
        return $this->daysBetween($date->startOfMonth()->startOfWeek(), $date->endOfMonth()->endOfWeek());
    }

    private function weekDays(CarbonImmutable $rangeStart, bool $workWeek = false): Collection
    {
        $days = $this->daysBetween($rangeStart->startOfWeek(), $rangeStart->endOfWeek());

        return $workWeek ? $days->filter(fn (CarbonImmutable $day): bool => ! $day->isWeekend())->values() : $days;
    }

    private function daysBetween(CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        $days = collect();

        for ($day = $start; $day->lte($end); $day = $day->addDay()) {
            $days->push($day);
        }

        return $days;
    }

    private function validateScheduleBlock(Request $request): array
    {
        $validated = $request->validate([
            'block_type' => ['required', Rule::in(array_keys(CalendarScheduleBlock::TYPES))],
            'label' => ['nullable', 'string', 'max:120'],
            'weekday' => ['required', 'integer', 'min:1', 'max:7'],
            'end_weekday' => ['nullable', 'integer', 'min:1', 'max:7'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i'],
            'is_shared' => ['nullable', 'boolean'],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $endWeekday = filled($validated['end_weekday'] ?? null)
            ? (int) $validated['end_weekday']
            : (int) $validated['weekday'];

        if ($endWeekday === (int) $validated['weekday'] && $validated['ends_at'] <= $validated['starts_at']) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'ends_at' => 'End time must be after start time when the block ends on the same day.',
            ]);
        }

        if (! filled($validated['end_weekday'] ?? null)) {
            $validated['end_weekday'] = null;
        }

        return $validated;
    }

    private function validateScheduleBlockOverride(Request $request, bool $updating = false): array
    {
        $validated = $request->validate([
            'block_date' => array_merge(
                ['required', 'date'],
                $updating ? [] : ['after_or_equal:today'],
            ),
            'block_end_date' => ['nullable', 'date', 'after_or_equal:block_date'],
            'block_type' => ['required', Rule::in(array_keys(CalendarScheduleBlock::TYPES))],
            'label' => ['nullable', 'string', 'max:120'],
            'reason' => ['nullable', 'string', 'max:255'],
            'is_all_day' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'required_unless:is_all_day,1', 'date_format:H:i'],
            'ends_at' => ['nullable', 'required_unless:is_all_day,1', 'date_format:H:i'],
            'is_shared' => ['nullable', 'boolean'],
            'return_to' => ['nullable', 'string', 'max:2048'],
        ]);

        $isAllDay = (bool) ($validated['is_all_day'] ?? false);
        $endDate = $validated['block_end_date'] ?? $validated['block_date'];

        if (! $isAllDay && $endDate === $validated['block_date'] && ($validated['ends_at'] ?? '') <= ($validated['starts_at'] ?? '')) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'ends_at' => 'End time must be after start time when the block ends on the same day.',
            ]);
        }

        if (! filled($validated['block_end_date'] ?? null)) {
            $validated['block_end_date'] = null;
        }

        return $validated;
    }

    /**
     * @return array<int, string>
     */
    private function weekdayLabels(): array
    {
        return [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];
    }

    private function stats(Collection $events, Collection $upcomingEvents): array
    {
        return [
            'visible' => $events->count(),
            'upcoming' => $upcomingEvents->count(),
            'training' => $events->filter(fn (CalendarEvent $event): bool => str_contains((string) $event->type?->slug, 'training'))->count(),
            'prospects' => $events->filter(fn (CalendarEvent $event): bool => filled($event->related_prospect_id) || str_contains((string) $event->type?->slug, 'prospect'))->count(),
        ];
    }
}
