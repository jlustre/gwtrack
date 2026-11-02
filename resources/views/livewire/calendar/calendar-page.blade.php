@php
    $viewLabels = [
        'month' => 'Month',
        'week' => 'Week',
        'day' => 'Day',
        'agenda' => 'Agenda',
    ];
    $chip = fn ($active = false) => 'inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold transition '.($active ? 'bg-gw-primary text-white' : 'border border-gw-accent/50 bg-gw-accent-soft text-gw-primary hover:bg-gw-accent-muted');
    $panel = 'rounded-lg border border-[#516070] bg-white/90 shadow-sm';
    $eventCard = 'rounded-md border px-2.5 py-2 text-left shadow-sm transition hover:shadow-md';
@endphp

<div wire:key="calendar-workspace" class="space-y-5">
    <div
        x-data="{
            dayPopupOpen: false,
            dayPopupDate: null,
            dayPopupLabel: '',
            dayPopupItems: [],
            openDayPopup(date, label, items) {
                this.dayPopupDate = date;
                this.dayPopupLabel = label;
                this.dayPopupItems = items || [];
                this.dayPopupOpen = true;
            },
            closeDayPopup() {
                this.dayPopupOpen = false;
            },
            openCreateForDay() {
                this.dayPopupOpen = false;
                $wire.openCreateForDay(this.dayPopupDate);
            },
        }"
        x-on:keydown.escape.window="$wire.closeEventDetail(); $wire.closeCreateModal(); $wire.closeAddCategoryModal(); $wire.cancelEditCategory(); closeDayPopup()"
        class="space-y-5"
    >
    @if ($statusMessage !== '')
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ $statusMessage }}
        </div>
    @elseif (session('status'))
        <div class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
            {{ session('status') }}
        </div>
    @endif

    <div wire:loading.flex class="fixed inset-x-0 top-16 z-40 justify-center">
        <div class="rounded-full border border-gw-accent/40 bg-white px-4 py-1.5 text-xs font-semibold text-gw-primary shadow">
            Updating calendar…
        </div>
    </div>

        <section class="rounded-xl border border-[#516070] bg-gradient-to-r from-gw-primary via-[#132B4B] to-gw-primary p-5 text-white shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Calendar & Events</p>
                    <h1 class="mt-1 text-2xl font-semibold">GWTracks Calendar</h1>
                    <p class="mt-1 max-w-3xl text-sm text-slate-200">
                        Coordinate team trainings, mentor sessions, prospect appointments, rank reviews, licensing deadlines, and organization-wide events.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="goToday" class="{{ $chip() }}">Today</button>
                    <button type="button" wire:click="goPrevious" class="{{ $chip() }}" aria-label="Previous period">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L9.06 10l3.71 3.71a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd" /></svg>
                    </button>
                    <button type="button" wire:click="goNext" class="{{ $chip() }}" aria-label="Next period">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L10.94 10 7.23 6.29a.75.75 0 1 1 1.06-1.06l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" /></svg>
                    </button>
                    <a href="{{ route('calendar.export', ['view' => $viewMode, 'date' => $currentDate->toDateString(), 'q' => $q, 'type' => $type]) }}" class="{{ $chip() }}">Export</a>
                    <a href="{{ route('calendar.settings') }}" wire:navigate class="{{ $chip() }}">Availability</a>
                </div>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-[17rem_minmax(0,1fr)] xl:items-stretch">
            <aside class="{{ $panel }} flex min-h-0 flex-col overflow-hidden">
                <div class="shrink-0 border-b border-[#516070]/20 bg-[#F6F8FB] px-4 py-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Create</p>
                            <h2 class="text-sm font-semibold text-gw-primary">Schedule Event</h2>
                        </div>
                        <button type="button" wire:click="openCreateModal" class="gw-icon-btn-accent" title="Create event">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                        </button>
                    </div>
                </div>

                <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                    <div class="shrink-0 space-y-4 p-4 pb-0">
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <div class="text-sm font-semibold text-gw-primary">{{ $currentDate->format('F Y') }}</div>
                            <div class="text-xs font-semibold text-slate-500">{{ $viewLabels[$viewMode] ?? 'Month' }}</div>
                        </div>
                        <div class="grid grid-cols-7 text-center text-[0.65rem] font-bold uppercase text-slate-400">
                            @foreach (['M','T','W','T','F','S','S'] as $dayName)
                                <span class="py-1">{{ $dayName }}</span>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-7 gap-1 text-center text-xs">
                            @foreach ($miniCalendarDays as $day)
                                <button
                                    type="button"
                                    wire:click="goToDate('{{ $day->toDateString() }}', 'day')"
                                    class="rounded-md py-1.5 font-semibold transition {{ $day->isSameDay($currentDate) ? 'bg-gw-accent text-gw-primary' : ($day->month === $currentDate->month ? 'text-gw-primary hover:bg-gw-accent-soft' : 'text-slate-300 hover:bg-slate-50') }}"
                                >
                                    {{ $day->day }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-2">
                        <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Views</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($viewLabels as $key => $label)
                                <button type="button" wire:click="setView('{{ $key }}')" class="{{ $chip($viewMode === $key) }} justify-center">
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <form wire:submit.prevent="applyFilters" class="space-y-3">
                        <div>
                            <label for="calendar-search" class="text-xs font-bold uppercase tracking-wide text-slate-500">Search</label>
                            <input id="calendar-search" wire:model="q" class="mt-1 w-full rounded-md border-[#516070]/30 bg-white text-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Event, place, person">
                        </div>

                        <div>
                            <label for="calendar-type" class="text-xs font-bold uppercase tracking-wide text-slate-500">Type</label>
                            <select id="calendar-type" wire:model="type" class="mt-1 w-full rounded-md border-[#516070]/30 bg-white text-sm focus:border-gw-accent focus:ring-gw-accent">
                                <option value="">All event types</option>
                                @foreach ($types as $eventType)
                                    <option value="{{ $eventType->id }}">{{ $eventType->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="w-full rounded-md bg-gw-primary px-3 py-2 text-sm font-semibold text-white transition hover:bg-[#132B4B]">Apply Filters</button>
                    </form>
                    </div>

                    <div class="flex min-h-0 flex-1 flex-col overflow-hidden px-4 pb-4 pt-4">
                        @if ($holidayCategories->isNotEmpty())
                            <div class="mb-4 space-y-2">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Public Holidays</p>
                                <div class="space-y-2">
                                    @foreach ($holidayCategories as $category)
                                        <label class="flex items-center justify-between rounded-md border border-[#516070]/20 bg-[#F8FAFC] px-3 py-2 text-sm font-medium text-gw-primary">
                                            <span class="flex min-w-0 items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    value="{{ $category->id }}"
                                                    wire:model.live="selectedCalendarIds"
                                                    class="rounded border-[#516070] text-gw-accent focus:ring-gw-accent"
                                                >
                                                <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $category->color }}"></span>
                                                <span class="truncate">{{ $category->name }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div class="mb-2 flex shrink-0 items-center justify-between gap-2">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">My Calendars</p>
                            <button
                                type="button"
                                wire:click="openAddCategoryModal"
                                class="inline-flex items-center gap-1 rounded-md border border-gw-accent/50 bg-gw-accent-soft px-2 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-gw-primary transition hover:bg-gw-accent-muted"
                                title="Add personal calendar"
                            >
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                                Add
                            </button>
                        </div>

                        <div class="min-h-0 max-h-60 flex-1 space-y-2 overflow-y-auto pr-1 xl:max-h-none">
                        @if (($sharedMentorCalendars ?? collect())->isNotEmpty())
                            <div class="space-y-2 border-t border-[#516070]/15 pt-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Shared Mentor Calendars</p>
                                <div class="space-y-2">
                                    @foreach ($sharedMentorCalendars as $mentor)
                                        <div class="flex items-center justify-between rounded-md border border-gw-accent/30 bg-gw-accent-soft px-3 py-2 text-sm text-gw-primary">
                                            <div class="min-w-0">
                                                <p class="truncate font-semibold">{{ $mentor->name }}</p>
                                                <p class="text-[0.65rem] font-medium uppercase tracking-wide text-slate-500">CFM Calendar</p>
                                            </div>
                                            <span class="ml-2 rounded-full bg-white px-2 py-0.5 text-[0.6rem] font-bold uppercase text-gw-primary">Shared</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if (($sharedScheduleBlockOwners ?? collect())->isNotEmpty())
                            <div class="space-y-2 border-t border-[#516070]/15 pt-4">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Shared Availability</p>
                                <div class="space-y-2">
                                    @foreach ($sharedScheduleBlockOwners as $owner)
                                        <div class="rounded-md border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-gw-primary">
                                            <p class="truncate font-semibold">{{ $owner->name }}</p>
                                            <p class="text-[0.65rem] font-medium uppercase tracking-wide text-slate-500">
                                                {{ $owner->hasRole('certified-field-mentor') ? 'CFM blocked times' : 'Trainee blocked times' }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                                <a href="{{ route('calendar.settings') }}" class="block text-xs font-semibold text-gw-accent-dark hover:underline">Manage my availability →</a>
                            </div>
                        @else
                            <div class="border-t border-[#516070]/15 pt-4">
                                <a href="{{ route('calendar.settings') }}" class="block rounded-md border border-dashed border-gw-accent/40 bg-gw-accent-soft px-3 py-2 text-xs font-semibold text-gw-accent-dark hover:bg-gw-accent-muted">
                                    Block work & personal times →
                                </a>
                            </div>
                        @endif

                        <div class="space-y-2">
                            @foreach ($categories as $category)
                                <div class="relative flex items-center justify-between rounded-md border border-[#516070]/20 bg-[#F8FAFC] px-3 py-2">
                                    <label class="flex min-w-0 flex-1 items-center gap-2 text-sm font-medium text-gw-primary">
                                        <input
                                            type="checkbox"
                                            value="{{ $category->id }}"
                                            wire:model.live="selectedCalendarIds"
                                            class="rounded border-[#516070] text-gw-accent focus:ring-gw-accent"
                                        >
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $category->color }}"></span>
                                        <span class="truncate">{{ $category->name }}</span>
                                    </label>

                                    <div class="ml-2 flex items-center gap-1">
                                        <span class="mr-1 text-xs font-semibold text-slate-500">{{ $events->where('calendar_category_id', $category->id)->count() }}</span>

                                        @if (($category->user_id && $category->user_id === auth()->id()) || ($category->user_id === null && auth()->user()->can('manage organization calendar')))
                                            <button type="button" wire:click="startEditCategory({{ $category->id }})" title="Customize calendar" class="gw-icon-btn-accent">
                                                <span class="sr-only">Customize {{ $category->name }}</span>
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path d="m13.586 3.586 2.828 2.828-9.193 9.193a2 2 0 0 1-.878.51l-3.03.757a.75.75 0 0 1-.91-.91l.757-3.03a2 2 0 0 1 .51-.878l9.193-9.193ZM15 2.172a2 2 0 0 1 2.828 0 2 2 0 0 1 0 2.828l-.414.414-2.828-2.828.414-.414Z" />
                                                </svg>
                                            </button>

                                            <button
                                                type="button"
                                                wire:click="deleteCategory({{ $category->id }})"
                                                wire:confirm="Delete this calendar category? Existing events will remain, but this calendar group will be hidden."
                                                title="Delete calendar"
                                                class="gw-icon-btn-danger"
                                            >
                                                <span class="sr-only">Delete {{ $category->name }}</span>
                                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75V4.5H3.75a.75.75 0 0 0 0 1.5h.32l.67 10.03A2.75 2.75 0 0 0 7.48 18h5.04a2.75 2.75 0 0 0 2.74-1.97L15.93 6h.32a.75.75 0 0 0 0-1.5H14v-.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM7.5 4.5v-.75c0-.69.56-1.25 1.25-1.25h2.5c.69 0 1.25.56 1.25 1.25v.75h-5Z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="flex min-w-0 flex-col self-stretch">
            <div class="{{ $panel }} flex min-h-[32rem] min-w-0 flex-1 flex-col overflow-hidden xl:min-h-[36rem]" data-calendar-main-panel role="region" aria-label="Calendar view">
                <div class="shrink-0 border-b border-[#516070]/20 bg-[#F6F8FB] px-4 py-3">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">{{ $viewLabels[$viewMode] ?? 'Month' }} View</p>
                            <h2 class="text-xl font-semibold text-gw-primary">
                                @if ($viewMode === 'day')
                                    {{ $currentDate->format('l, F j, Y') }}
                                @elseif ($viewMode === 'agenda')
                                    {{ $rangeStart->format('M j') }} - {{ $rangeEnd->format('M j, Y') }}
                                @elseif (in_array($viewMode, ['week', 'work-week'], true))
                                    {{ $rangeStart->format('M j') }} - {{ $rangeEnd->format('M j, Y') }}
                                @else
                                    {{ $currentDate->format('F Y') }}
                                @endif
                            </h2>
                        </div>
                        <div class="grid grid-cols-4 gap-2 text-center">
                            @foreach ([['Visible', $stats['visible']], ['Upcoming', $stats['upcoming']], ['Training', $stats['training']], ['Prospects', $stats['prospects']]] as [$label, $value])
                                <div class="rounded-md border border-gw-accent/40 bg-gw-accent-soft px-3 py-2">
                                    <div class="text-sm font-bold text-gw-accent">{{ $value }}</div>
                                    <div class="text-[0.65rem] font-semibold uppercase text-slate-500">{{ $label }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                @if ($viewMode === 'agenda')
                    <div class="min-h-0 flex-1 divide-y divide-[#516070]/15 overflow-y-auto">
                        @forelse ($agendaScheduleBlocks as $block)
                            <div class="grid gap-3 px-4 py-3 sm:grid-cols-[7rem_minmax(0,1fr)_8rem]" style="background-color: {{ $block['color'] }}12; border-left: 4px solid {{ $block['color'] }};">
                                <div class="text-sm font-semibold text-gw-primary">
                                    {{ $block['range_starts_at']->format('M j') }}
                                    @if (! $block['range_starts_at']->isSameDay($block['range_ends_at']))
                                        <div class="text-xs font-medium text-slate-500">through {{ $block['range_ends_at']->format('M j') }}</div>
                                    @endif
                                    <div class="text-xs font-medium text-slate-500">{{ $block['time_label'] ?? 'Blocked' }}</div>
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-gw-primary">{{ $block['label'] ?? 'Blocked' }}</div>
                                    @if (! empty($block['owner_name']) && ($block['owner_id'] ?? auth()->id()) !== auth()->id())
                                        <div class="mt-1 text-xs text-slate-500">Shared by {{ $block['owner_name'] }}</div>
                                    @endif
                                </div>
                                <div class="flex items-start justify-end">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold text-gw-primary" style="background-color: {{ $block['color'] }}33; border: 1px solid {{ $block['color'] }}">
                                        Blocked
                                    </span>
                                </div>
                            </div>
                        @empty
                        @endforelse
                        @foreach ($events as $event)
                            <button
                                type="button"
                                wire:click="openEventDetail({{ $event->id }})"
                                class="grid w-full gap-3 px-4 py-3 text-left transition hover:shadow-sm sm:grid-cols-[7rem_minmax(0,1fr)_8rem]"
                                style="background-color: {{ $event->display_color }}12; border-left: 4px solid {{ $event->display_color }};"
                            >
                                <div class="text-sm font-semibold text-gw-primary">
                                    {{ $event->starts_at->format('M j') }}
                                    <div class="text-xs font-medium text-slate-500">{{ $event->is_all_day ? 'All day' : $event->starts_at->format('g:i A') }}</div>
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-gw-primary">{{ $event->title }}</div>
                                    <div class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $event->description ?: 'No description added yet.' }}</div>
                                </div>
                                <div class="flex items-start justify-end">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold text-gw-primary" style="background-color: {{ $event->display_color }}33; border: 1px solid {{ $event->display_color }}">
                                        {{ $event->type?->name ?? 'Event' }}
                                    </span>
                                </div>
                            </button>
                        @endforeach
                        @if ($agendaScheduleBlocks->isEmpty() && $events->isEmpty())
                            <div class="px-4 py-10 text-center text-sm text-slate-500">No visible calendar events in this range.</div>
                        @endif
                    </div>
                @elseif ($viewMode === 'day')
                    <div class="flex min-h-0 flex-1 flex-col overflow-x-auto">
                        <div class="flex min-h-0 min-w-[42rem] flex-1 flex-col divide-y divide-[#516070]/15">
                            @php
                                $displayedBlockKeys = [];
                                $displayedEventKeys = [];
                            @endphp
                            @foreach ($hours as $hour)
                                @php
                                    $hourEvents = $dayEventSegments->filter(function (array $segment) use ($hour, &$displayedEventKeys): bool {
                                        $key = (string) ($segment['range_key'] ?? $segment['id']);

                                        if (isset($displayedEventKeys[$key])) {
                                            return false;
                                        }

                                        $segmentStartHour = (int) $segment['segment_starts_at']->format('G');
                                        $segmentEndHour = (int) $segment['segment_ends_at']->format('G');

                                        if ($segmentStartHour <= $hour && $segmentEndHour > $hour) {
                                            $displayedEventKeys[$key] = true;

                                            return true;
                                        }

                                        return false;
                                    });
                                    $hourBlocks = $dayBlocks->filter(function (array $block) use ($hour, &$displayedBlockKeys): bool {
                                        $key = (string) ($block['range_key'] ?? $block['id']);

                                        if (isset($displayedBlockKeys[$key])) {
                                            return false;
                                        }

                                        $blockStartHour = (int) $block['starts_at']->format('G');
                                        $blockEndHour = (int) $block['ends_at']->format('G');

                                        if ($blockStartHour <= $hour && $blockEndHour > $hour) {
                                            $displayedBlockKeys[$key] = true;

                                            return true;
                                        }

                                        return false;
                                    });
                                @endphp
                                <div class="grid min-h-0 flex-1 grid-cols-[5rem_minmax(0,1fr)]">
                                    <div class="flex items-start bg-[#F8FAFC] px-3 py-3 text-xs font-semibold text-slate-500">{{ sprintf('%02d:00', $hour) }}</div>
                                    <div class="min-h-0 space-y-2 overflow-y-auto px-3 py-3">
                                        @foreach ($hourBlocks as $block)
                                            @include('events.partials.schedule-block-chip', ['block' => $block, 'showOwner' => ($block['owner_id'] ?? auth()->id()) !== auth()->id()])
                                        @endforeach
                                        @foreach ($hourEvents as $segment)
                                            @include('events.partials.calendar-event-chip', ['segment' => $segment, 'modal' => true])
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @elseif (in_array($viewMode, ['week', 'work-week'], true))
                    <div class="flex min-h-0 flex-1 flex-col overflow-x-auto">
                        <div class="flex min-h-0 min-w-[52rem] flex-1 flex-col">
                            <div class="grid shrink-0 border-b border-[#516070]/15" style="grid-template-columns: repeat({{ $weekDays->count() }}, minmax(0, 1fr));">
                                @foreach ($weekDays as $day)
                                    <button
                                        type="button"
                                        wire:click="goToDate('{{ $day->toDateString() }}', 'day')"
                                        class="border-r border-[#516070]/15 bg-[#F8FAFC] px-3 py-3 text-center last:border-r-0"
                                    >
                                        <div class="text-xs font-bold uppercase text-slate-500">{{ $day->format('D') }}</div>
                                        <div class="mt-1 text-lg font-semibold {{ $day->isToday() ? 'text-gw-accent' : 'text-gw-primary' }}">{{ $day->day }}</div>
                                    </button>
                                @endforeach
                            </div>
                            <div class="flex min-h-0 flex-1 flex-col overflow-visible">
                                @foreach ($weekSpanRows as $spanRow)
                                    <div class="grid shrink-0 overflow-visible" style="grid-template-columns: repeat({{ $weekDays->count() }}, minmax(0, 1fr));">
                                        @foreach ($weekDays as $day)
                                            @php
                                                $spanItem = $spanRow[$day->toDateString()] ?? null;
                                            @endphp
                                            <div class="overflow-visible border-r border-[#516070]/15 px-2 py-1 last:border-r-0">
                                                @include('events.partials.calendar-span-cell', [
                                                    'item' => $spanItem,
                                                    'compact' => true,
                                                    'modal' => true,
                                                    'showOwner' => $spanItem && ($spanItem['payload']['owner_id'] ?? auth()->id()) !== auth()->id(),
                                                ])
                                            </div>
                                        @endforeach
                                    </div>
                                @endforeach
                                <div class="grid min-h-0 flex-1 overflow-visible" style="grid-template-columns: repeat({{ $weekDays->count() }}, minmax(0, 1fr));">
                                    @foreach ($weekDays as $day)
                                        @php
                                            $dateKey = $day->toDateString();
                                            $daySingles = collect($weekSinglesByDate[$dateKey] ?? []);
                                        @endphp
                                        <div class="flex min-h-0 flex-col gap-1 overflow-visible border-r border-[#516070]/15 p-2 last:border-r-0">
                                            @foreach ($daySingles as $item)
                                                @include('events.partials.calendar-span-cell', [
                                                    'item' => $item,
                                                    'compact' => true,
                                                    'modal' => true,
                                                    'showOwner' => ($item['payload']['owner_id'] ?? auth()->id()) !== auth()->id(),
                                                ])
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    @php
                        $monthWeekCount = (int) ceil($monthDays->count() / 7);
                        $formatDayPopupItem = static function (?array $item): ?array {
                            if ($item === null) {
                                return null;
                            }

                            if (($item['type'] ?? null) === 'block') {
                                $block = $item['payload'] ?? [];
                                $rangeStart = $block['range_starts_at'] ?? $block['starts_at'] ?? null;
                                $rangeEnd = $block['range_ends_at'] ?? $block['ends_at'] ?? null;
                                $timeLabel = $block['time_label'] ?? (
                                    ($block['range_is_all_day'] ?? $block['is_all_day'] ?? false)
                                        ? 'All day'
                                        : (filled($rangeStart) && filled($rangeEnd)
                                            ? $rangeStart->format('g:i A').' – '.$rangeEnd->format('g:i A')
                                            : 'Blocked')
                                );

                                return [
                                    'type' => 'block',
                                    'title' => $block['label'] ?? 'Blocked',
                                    'time' => $timeLabel,
                                    'meta' => $block['owner_name'] ?? null,
                                    'url' => null,
                                    'color' => $block['color'] ?? '#64748B',
                                ];
                            }

                            $segment = $item['payload'] ?? [];
                            $event = $segment['event'] ?? null;

                            if (! $event) {
                                return null;
                            }

                            return [
                                'type' => 'event',
                                'id' => $event->id,
                                'title' => $event->title,
                                'time' => $segment['time_label'] ?? ($event->is_all_day ? 'All day' : $event->starts_at->format('g:i A')),
                                'meta' => null,
                                'url' => null,
                                'color' => $event->display_color,
                            ];
                        };
                    @endphp
                    <div class="flex min-h-0 flex-1 flex-col overflow-x-auto">
                        <div class="flex min-h-0 min-w-[54rem] flex-1 flex-col">
                            <div class="grid shrink-0 grid-cols-7 border-b border-[#516070]/15 bg-[#F8FAFC] text-center text-xs font-bold uppercase text-slate-500">
                                @foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $dayName)
                                    <div class="border-r border-[#516070]/15 px-3 py-2 last:border-r-0">{{ $dayName }}</div>
                                @endforeach
                            </div>
                            <div class="grid min-h-0 flex-1 overflow-visible" style="grid-template-rows: repeat({{ $monthWeekCount }}, minmax(0, 1fr)); grid-template-columns: repeat(7, minmax(0, 1fr));">
                                @foreach ($monthWeekLayouts as $weekLayout)
                                    @foreach ($weekLayout['days'] as $day)
                                        @php
                                            $dateKey = $day->toDateString();
                                            $daySingles = collect($weekLayout['singlesByDate'][$dateKey] ?? []);
                                            $daySpanCount = count($weekLayout['spanningRows']);
                                            $dayPopupItems = collect($weekLayout['spanningRows'])
                                                ->map(fn (array $row) => $formatDayPopupItem($row[$dateKey] ?? null))
                                                ->filter()
                                                ->concat($daySingles->map(fn (array $item) => $formatDayPopupItem($item))->filter())
                                                ->values()
                                                ->all();
                                            $dayItemCount = count($dayPopupItems);
                                        @endphp
                                        <div
                                            role="button"
                                            tabindex="0"
                                            data-calendar-day-cell
                                            data-date="{{ $dateKey }}"
                                            x-on:click="openDayPopup(@js($dateKey), @js($day->format('l, F j, Y')), @js($dayPopupItems))"
                                            x-on:keydown.enter.prevent="openDayPopup(@js($dateKey), @js($day->format('l, F j, Y')), @js($dayPopupItems))"
                                            x-on:keydown.space.prevent="openDayPopup(@js($dateKey), @js($day->format('l, F j, Y')), @js($dayPopupItems))"
                                            class="group/day flex min-h-0 cursor-pointer flex-col overflow-visible border-b border-r border-[#516070]/15 bg-white p-2 last:border-r-0 transition hover:z-20 hover:border-gw-accent hover:bg-gw-accent-soft/50 hover:ring-2 hover:ring-inset hover:ring-gw-accent/70 {{ $day->month === $currentDate->month ? '' : 'bg-slate-50/70' }}"
                                        >
                                            <div class="mb-2 flex shrink-0 items-center justify-between">
                                                <button
                                                    type="button"
                                                    wire:click.stop="goToDate('{{ $day->toDateString() }}', 'day')"
                                                    class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold {{ $day->isToday() ? 'bg-gw-accent text-gw-primary' : 'text-slate-600 group-hover/day:bg-gw-accent-soft' }}"
                                                >
                                                    {{ $day->day }}
                                                </button>
                                                <span class="text-[0.65rem] font-semibold text-slate-400">{{ $dayItemCount }}</span>
                                            </div>
                                            <div class="min-h-0 flex-1 space-y-1 overflow-visible">
                                                @foreach ($weekLayout['spanningRows'] as $spanRow)
                                                    @php
                                                        $spanItem = $spanRow[$dateKey] ?? null;
                                                    @endphp
                                                    @include('events.partials.calendar-span-cell', [
                                                        'item' => $spanItem,
                                                        'compact' => true,
                                                        'modal' => true,
                                                        'showOwner' => $spanItem && ($spanItem['payload']['owner_id'] ?? auth()->id()) !== auth()->id(),
                                                    ])
                                                @endforeach
                                                @foreach ($daySingles->take($daySpanCount === 0 ? 4 : max(1, 4 - $daySpanCount)) as $item)
                                                    @include('events.partials.calendar-span-cell', [
                                                        'item' => $item,
                                                        'compact' => true,
                                                        'modal' => true,
                                                        'showOwner' => ($item['payload']['owner_id'] ?? auth()->id()) !== auth()->id(),
                                                    ])
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
            </div>
        </section>

        <button type="button" wire:click="openCreateModal" class="fixed bottom-6 right-6 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-gw-accent text-gw-primary shadow-lg ring-4 ring-white lg:hidden" title="Create event">
            <svg class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
        </button>

        @include('livewire.calendar.partials.create-event-modal')
        @include('livewire.calendar.partials.event-detail-modal')
        @include('events.partials.day-events-popup')

        @if ($editingCategoryId)
            @php($editingCategory = $categories->firstWhere('id', $editingCategoryId))
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4">
                <div class="w-full max-w-md rounded-xl border border-gw-accent/50 bg-white shadow-xl" x-on:click.outside="$wire.cancelEditCategory()">
                    <form wire:submit.prevent="updateCategory">
                        <div class="border-b border-slate-100 px-5 py-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Customize Calendar</p>
                                    <h3 class="text-lg font-semibold text-gw-primary">{{ $editingCategory?->name ?? 'Calendar' }}</h3>
                                </div>
                                <button type="button" wire:click="cancelEditCategory" class="gw-icon-btn-close" aria-label="Close customize modal">
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                                </button>
                            </div>
                        </div>
                        <div class="space-y-4 px-5 py-5">
                            <div>
                                <label for="edit-calendar-name" class="block text-sm font-semibold text-gw-primary">Name</label>
                                <input id="edit-calendar-name" wire:model="editCategoryName" class="mt-2 block w-full rounded-lg border-[#516070]/40 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent">
                                @error('editCategoryName') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="edit-calendar-color" class="block text-sm font-semibold text-gw-primary">Color</label>
                                <div class="mt-2 flex items-center gap-3">
                                    <input id="edit-calendar-color" type="color" wire:model.live="editCategoryColor" class="h-10 w-14 rounded border border-[#516070]/40 bg-white p-1">
                                    <input wire:model="editCategoryColor" class="block w-full rounded-lg border-[#516070]/40 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="#84BB41">
                                </div>
                                @error('editCategoryColor') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            @if ($editingCategory?->user_id)
                                <label class="flex items-start gap-2 text-sm text-gw-primary">
                                    <input type="checkbox" wire:model="editCategoryIsPublic" value="1" class="mt-1 rounded border-[#516070] text-gw-accent focus:ring-gw-accent">
                                    <span>
                                        <span class="font-semibold">Share publicly</span>
                                        <span class="mt-1 block text-xs text-slate-500">Other members can see this calendar when enabled.</span>
                                    </span>
                                </label>
                            @endif
                        </div>
                        <div class="flex justify-end gap-3 border-t border-slate-100 px-5 py-4">
                            <button type="button" wire:click="cancelEditCategory" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                            <button type="submit" class="rounded-lg bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary hover:bg-gw-accent-hover">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div x-show="$wire.addCategoryOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4">
            <div class="w-full max-w-md rounded-xl border border-gw-accent/50 bg-white shadow-xl" x-on:click.outside="$wire.closeAddCategoryModal()">
                <form wire:submit.prevent="storeCategory">
                    <div class="border-b border-slate-100 px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">My Calendars</p>
                                <h3 class="text-lg font-semibold text-gw-primary">Add Calendar Category</h3>
                            </div>
                            <button type="button" wire:click="closeAddCategoryModal" class="gw-icon-btn-close" aria-label="Close add calendar modal">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                            </button>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">Personal calendars are private by default and only visible to you unless you mark them public.</p>
                    </div>
                    <div class="space-y-4 px-5 py-5">
                        <div>
                            <label for="new-calendar-name" class="block text-sm font-semibold text-gw-primary">Name</label>
                            <input id="new-calendar-name" wire:model="newCategoryName" required class="mt-2 block w-full rounded-lg border-[#516070]/40 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="Example: Client Follow-ups">
                            @error('newCategoryName') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="new-calendar-color" class="block text-sm font-semibold text-gw-primary">Color</label>
                            <div class="mt-2 flex items-center gap-3">
                                <input id="new-calendar-color" type="color" wire:model.live="newCategoryColor" class="h-10 w-14 rounded border border-[#516070]/40 bg-white p-1">
                                <input wire:model="newCategoryColor" class="block w-full rounded-lg border-[#516070]/40 text-sm shadow-sm focus:border-gw-accent focus:ring-gw-accent" placeholder="#84BB41">
                            </div>
                            @error('newCategoryColor') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <label class="flex items-start gap-2 text-sm text-gw-primary">
                            <input type="checkbox" wire:model="newCategoryIsPublic" value="1" class="mt-1 rounded border-[#516070] text-gw-accent focus:ring-gw-accent">
                            <span>
                                <span class="font-semibold">Share publicly</span>
                                <span class="mt-1 block text-xs text-slate-500">Let other members see this calendar category on their calendar page.</span>
                            </span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-3 border-t border-slate-100 px-5 py-4">
                        <button type="button" wire:click="closeAddCategoryModal" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" class="rounded-lg bg-gw-accent px-4 py-2 text-sm font-bold text-gw-primary hover:bg-gw-accent-hover">Create Calendar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <section class="grid gap-4 xl:grid-cols-3">
        <livewire:dashboard.upcoming-phone-calls-panel :compact="true" />
        <livewire:dashboard.upcoming-appointments-panel :compact="true" />
        <div class="space-y-4">
            @include('calendar.partials.tasks-list-panel', [
                'panel' => $tasksPanel ?? [],
                'compact' => true,
            ])
            <livewire:tasks.assigned-by-me-panel :compact="true" :limit="10" panel-id="calendar-assigned-by-me-panel" />
        </div>
    </section>
</div>
