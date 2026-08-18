@props(['title', 'subtitle' => null])

@php
    $tabs = [
        ['label' => 'Dashboard', 'route' => 'bookings.dashboard', 'permission' => 'view booking dashboard'],
        ['label' => 'My Availability', 'route' => 'bookings.availability', 'permission' => 'manage own availability'],
        ['label' => 'Event Types', 'route' => 'bookings.event-types', 'permission' => 'manage own booking event types'],
        ['label' => 'Booking Links', 'route' => 'bookings.links', 'permission' => 'create booking links'],
        ['label' => 'Requests', 'route' => 'bookings.requests', 'permission' => 'approve booking requests'],
        ['label' => 'My Appointments', 'route' => 'bookings.my', 'permission' => 'view own bookings'],
        ['label' => 'Session Calendar', 'route' => 'bookings.calendar', 'permission' => 'view own bookings'],
        ['label' => 'Settings', 'route' => 'bookings.settings', 'permission' => 'manage booking settings'],
    ];
@endphp

<div class="space-y-5">
    <section class="rounded-xl border border-[#516070] bg-gradient-to-r from-[#07101F] via-gw-primary to-[#111827] p-5 text-white shadow-sm">
        <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">CFM Mentor Scheduling</p>
        <div class="mt-2 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold">{{ $title }}</h1>
                @if ($subtitle)
                    <p class="mt-1 max-w-3xl text-sm leading-6 text-slate-200">{{ $subtitle }}</p>
                @endif
            </div>
            <a href="{{ route('calendar.index') }}" class="inline-flex items-center justify-center rounded-md border border-gw-accent bg-gw-accent-soft px-4 py-2 text-sm font-semibold text-gw-primary hover:bg-gw-accent-muted">
                Open Calendar
            </a>
        </div>
    </section>

    <nav class="flex gap-2 overflow-x-auto rounded-lg border border-[#516070] bg-white/90 p-2 shadow-sm">
        @foreach ($tabs as $tab)
            @continue(! auth()->user()->hasPermissionTo($tab['permission']))
            <a href="{{ route($tab['route']) }}" class="whitespace-nowrap rounded-md px-3 py-2 text-sm font-semibold transition {{ request()->routeIs($tab['route']) ? 'bg-gw-accent text-gw-primary' : 'text-slate-600 hover:bg-gw-accent-soft hover:text-gw-primary' }}">
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    {{ $slot }}
</div>
