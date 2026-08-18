<div class="space-y-5">
    <section class="rounded-xl border border-[#516070] bg-gradient-to-r from-[#07101F] via-gw-primary to-[#111827] p-5 text-white shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">CFM Mentor Scheduling</p>
        <div class="mt-2 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">{{ $panel['title'] }}</h1>
                <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-200">{{ $panel['subtitle'] }}</p>
            </div>
            <a href="{{ route('calendar.index') }}" class="inline-flex items-center justify-center rounded-md border border-gw-accent bg-gw-accent-soft px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-muted">
                Open Calendar
            </a>
        </div>
    </section>

    <nav class="flex gap-2 overflow-x-auto rounded-lg border border-[#516070] bg-white/90 p-2 shadow-sm">
        @foreach ($tabs as $navTab)
            <button
                type="button"
                wire:click="setTab('{{ $navTab['key'] }}')"
                wire:key="booking-tab-{{ $navTab['key'] }}"
                @class([
                    'whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold transition',
                    'bg-gw-accent text-gw-primary' => $tab === $navTab['key'],
                    'text-slate-600 hover:bg-gw-accent-soft hover:text-gw-primary' => $tab !== $navTab['key'],
                ])
                @if ($tab === $navTab['key']) aria-current="page" @endif
            >
                {{ $navTab['label'] }}
            </button>
        @endforeach
    </nav>

    <div wire:key="booking-panel-{{ $tab }}-{{ $panelKey }}">
        @switch($tab)
            @case('dashboard')
                @include('livewire.booking.panels.dashboard', ['dashboard' => $dashboard])
                @break

            @case('availability')
                @include('livewire.booking.panels.availability', ['schedules' => $schedules])
                @break

            @case('event-types')
                @include('livewire.booking.panels.event-types', ['eventTypes' => $eventTypes])
                @break

            @case('links')
                @include('livewire.booking.panels.links', ['links' => $links])
                @break

            @case('requests')
                @include('livewire.booking.panels.requests', ['bookings' => $requestBookings])
                @break

            @case('calendar')
                @include('livewire.booking.panels.calendar', ['bookings' => $calendarBookings])
                @break

            @case('settings')
                @include('livewire.booking.panels.settings')
                @break

            @default
                <livewire:booking.my-bookings-list wire:key="my-bookings-list-{{ $panelKey }}" />
        @endswitch
    </div>
</div>
