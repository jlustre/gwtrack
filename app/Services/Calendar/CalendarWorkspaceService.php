<?php

namespace App\Services\Calendar;

use App\Models\CalendarCategory;
use App\Models\CalendarEvent;
use App\Models\CalendarEventType;
use App\Models\User;
use App\Models\UserCalendarPreference;
use App\Services\CalendarEventDisplayService;
use App\Services\CalendarScheduleBlockService;
use App\Services\CalendarShareService;
use App\Services\CalendarSpanLayoutService;
use App\Services\DashboardActivityService;
use App\Support\LocationOptions;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class CalendarWorkspaceService
{
    public function __construct(
        private readonly CalendarShareService $calendarShare,
        private readonly CalendarScheduleBlockService $scheduleBlocks,
        private readonly CalendarEventDisplayService $eventDisplay,
        private readonly CalendarSpanLayoutService $spanLayout,
        private readonly DashboardActivityService $dashboardActivity,
    ) {}

    /**
     * @param  array{
     *     view_mode?: string,
     *     date?: string|null,
     *     q?: string|null,
     *     type?: int|string|null,
     *     status?: string|null,
     *     visibility?: string|null,
     *     selected_calendar_ids?: list<int>|null,
     *     persist_selected_calendars?: bool
     * }  $options
     * @return array<string, mixed>
     */
    public function build(User $user, array $options = []): array
    {
        $viewMode = in_array($options['view_mode'] ?? 'month', ['month', 'week', 'work-week', 'day', 'agenda'], true)
            ? ($options['view_mode'] ?? 'month')
            : 'month';

        $currentDate = CarbonImmutable::parse($options['date'] ?? now()->toDateString())->startOfDay();

        app(PublicHolidaySyncService::class)->ensureRollingWindow((int) $currentDate->year);

        [$rangeStart, $rangeEnd] = $this->dateRange($currentDate, $viewMode);

        $selectedCalendarIds = array_key_exists('selected_calendar_ids', $options) && is_array($options['selected_calendar_ids'])
            ? $this->normalizeSelectedCalendarIds($user, $options['selected_calendar_ids'], (bool) ($options['persist_selected_calendars'] ?? false))
            : $this->selectedCalendarIds($user);

        $filters = [
            'q' => filled($options['q'] ?? null) ? trim((string) $options['q']) : null,
            'type' => filled($options['type'] ?? null) ? (int) $options['type'] : null,
            'status' => filled($options['status'] ?? null) ? (string) $options['status'] : null,
            'visibility' => filled($options['visibility'] ?? null) ? (string) $options['visibility'] : null,
        ];

        $sharedMentorCalendars = $this->calendarShare->sharedCfmOrganizersFor($user);

        $events = $this->visibleEventsQuery($user, $selectedCalendarIds, $filters)
            ->where(function (Builder $query) use ($rangeStart, $rangeEnd): void {
                $query->whereBetween('starts_at', [$rangeStart, $rangeEnd])
                    ->orWhereBetween('ends_at', [$rangeStart, $rangeEnd])
                    ->orWhere(function (Builder $query) use ($rangeStart, $rangeEnd): void {
                        $query->where('starts_at', '<=', $rangeStart)->where('ends_at', '>=', $rangeEnd);
                    });
            })
            ->with(['organizer:id,name,email', 'type', 'category', 'attendees'])
            ->orderBy('starts_at')
            ->get();

        $upcomingEvents = $this->visibleEventsQuery($user, $selectedCalendarIds, $filters)
            ->with(['type', 'category'])
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->limit(8)
            ->get();

        $ownScheduleBlocksByDate = $this->scheduleBlocks->expandedBlocksByDate($user, $rangeStart, $rangeEnd);
        $sharedScheduleBlocksByDate = $this->scheduleBlocks->sharedExpandedBlocksByDate($user, $rangeStart, $rangeEnd);
        $agendaScheduleBlocks = $this->scheduleBlocks->agendaBlocksFromExpanded(
            $ownScheduleBlocksByDate,
            $sharedScheduleBlocksByDate,
            $rangeStart,
            $rangeEnd,
        );
        $eventSegmentsByDate = $this->eventDisplay->segmentsByDate($events, $rangeStart, $rangeEnd);
        $monthDays = $this->monthDays($currentDate);
        $weekDays = $this->weekDays($rangeStart, $viewMode === 'work-week');
        $weekSpanRows = $this->spanLayout->spanningRows(
            $weekDays,
            $ownScheduleBlocksByDate,
            $sharedScheduleBlocksByDate,
            $eventSegmentsByDate,
        );
        $weekSinglesByDate = $this->spanLayout->singlesByDate(
            $weekDays,
            $ownScheduleBlocksByDate,
            $sharedScheduleBlocksByDate,
            $eventSegmentsByDate,
        );
        $monthWeekLayouts = $monthDays->chunk(7)->map(function (Collection $weekDaysChunk) use (
            $ownScheduleBlocksByDate,
            $sharedScheduleBlocksByDate,
            $eventSegmentsByDate,
        ): array {
            return [
                'days' => $weekDaysChunk,
                'spanningRows' => $this->spanLayout->spanningRows(
                    $weekDaysChunk,
                    $ownScheduleBlocksByDate,
                    $sharedScheduleBlocksByDate,
                    $eventSegmentsByDate,
                ),
                'singlesByDate' => $this->spanLayout->singlesByDate(
                    $weekDaysChunk,
                    $ownScheduleBlocksByDate,
                    $sharedScheduleBlocksByDate,
                    $eventSegmentsByDate,
                ),
            ];
        })->values();

        $activityPanels = $this->dashboardActivity->panelsFor($user);

        return [
            'viewMode' => $viewMode,
            'currentDate' => $currentDate,
            'rangeStart' => $rangeStart,
            'rangeEnd' => $rangeEnd,
            'events' => $events,
            'eventsByDate' => $events->groupBy(fn (CalendarEvent $event): string => $event->starts_at->toDateString()),
            'eventSegmentsByDate' => $eventSegmentsByDate,
            'weekSpanRows' => $weekSpanRows,
            'weekSinglesByDate' => $weekSinglesByDate,
            'monthWeekLayouts' => $monthWeekLayouts,
            'miniCalendarDays' => $this->miniCalendarDays($currentDate),
            'monthDays' => $monthDays,
            'weekDays' => $weekDays,
            'hours' => range(7, 20),
            'upcomingEvents' => $upcomingEvents,
            'categories' => $this->regularVisibleCategories($user),
            'holidayCategories' => $this->holidayCategories($user),
            'assignableCategories' => $this->assignableCategories($user),
            'types' => CalendarEventType::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'attendeeUsers' => $this->attendeeUsers($user),
            'eventTimezones' => LocationOptions::timezones(),
            'selectedCalendarIds' => $selectedCalendarIds,
            'sharedMentorCalendars' => $sharedMentorCalendars,
            'sharedScheduleBlockOwners' => $this->calendarShare->sharedScheduleBlockOwnersFor($user),
            'ownScheduleBlocksByDate' => $ownScheduleBlocksByDate,
            'sharedScheduleBlocksByDate' => $sharedScheduleBlocksByDate,
            'agendaScheduleBlocks' => $agendaScheduleBlocks,
            'dayBlocks' => collect($ownScheduleBlocksByDate[$currentDate->toDateString()] ?? [])
                ->merge(collect($sharedScheduleBlocksByDate[$currentDate->toDateString()] ?? [])),
            'dayEventSegments' => collect($eventSegmentsByDate[$currentDate->toDateString()] ?? []),
            'stats' => $this->stats($events, $upcomingEvents),
            'filters' => $filters,
            'previousDate' => $this->shiftedDate($currentDate, $viewMode, -1),
            'nextDate' => $this->shiftedDate($currentDate, $viewMode, 1),
            'tasksPanel' => $activityPanels['my_tasks'],
        ];
    }

    /**
     * @param  list<int|string>  $selectedIds
     * @return list<int>
     */
    public function normalizeSelectedCalendarIds(User $user, array $selectedIds, bool $persist = true): array
    {
        $visibleIds = $this->visibleCalendarIds($user);

        $normalized = collect($selectedIds)
            ->filter(fn ($id): bool => is_numeric($id))
            ->map(fn ($id): int => (int) $id)
            ->intersect($visibleIds)
            ->values()
            ->all();

        if ($persist) {
            $this->calendarPreference($user)->update(['visible_calendar_categories' => $normalized]);
        }

        return $normalized;
    }

    /**
     * @return list<int>
     */
    public function selectedCalendarIds(User $user): array
    {
        $preference = $this->calendarPreference($user);
        $visibleIds = $this->visibleCalendarIds($user);

        return collect($preference->visible_calendar_categories ?? $this->defaultVisibleCalendarIds($user))
            ->filter(fn ($id): bool => is_numeric($id))
            ->map(fn ($id): int => (int) $id)
            ->intersect($visibleIds)
            ->values()
            ->all();
    }

    public function assignableCategories(User $user): Collection
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

    /**
     * @param  array{q?: string|null, type?: int|null, status?: string|null, visibility?: string|null}  $filters
     */
    private function visibleEventsQuery(User $user, array $selectedCalendarIds, array $filters): Builder
    {
        $sharedCfmOrganizerIds = $this->calendarShare->sharedCfmOrganizerIdsFor($user);

        return CalendarEvent::query()
            ->where(function (Builder $query): void {
                $query->whereNull('calendar_category_id')
                    ->orWhereHas('category', fn (Builder $query) => $query->where('is_active', true));
            })
            ->when(filled($filters['q'] ?? null), function (Builder $query) use ($filters): void {
                $search = trim((string) $filters['q']);

                $query->where(function (Builder $query) use ($search): void {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
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
            ->when(filled($filters['type'] ?? null), fn (Builder $query) => $query->where('calendar_event_type_id', (int) $filters['type']))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', (string) $filters['status']))
            ->when(filled($filters['visibility'] ?? null), fn (Builder $query) => $query->where('visibility', (string) $filters['visibility']))
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

    private function attendeeUsers(User $user): Collection
    {
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

    private function calendarPreference(User $user): UserCalendarPreference
    {
        return UserCalendarPreference::firstOrCreate(
            ['user_id' => $user->id],
            [
                'default_view' => 'month',
                'timezone' => $user->profile?->timezone ?? 'PST',
                'visible_calendar_categories' => $this->defaultVisibleCalendarIds($user),
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

    /**
     * @return list<int>
     */
    private function visibleCalendarIds(User $user): array
    {
        return $this->visibleCategories($user)
            ->pluck('id')
            ->map(fn (int $id): int => $id)
            ->all();
    }

    /**
     * @return list<int>
     */
    private function defaultVisibleCalendarIds(User $_user): array
    {
        $personalId = CalendarCategory::query()
            ->where('slug', 'personal')
            ->whereNull('user_id')
            ->where('is_active', true)
            ->value('id');

        return $personalId ? [(int) $personalId] : [];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
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

    /**
     * @return array{visible: int, upcoming: int, training: int, prospects: int}
     */
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
