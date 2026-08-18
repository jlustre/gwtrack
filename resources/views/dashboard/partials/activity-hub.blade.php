@php
    $panels = $activity ?? [];
    $panelOrder = [
        'my_tasks',
        'upcoming_meetings',
        'calendar',
        'notifications',
        'recent_messages',
    ];
@endphp

<section>
    <div class="mb-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-gw-accent">Daily Activity</p>
        <h2 class="text-lg font-semibold text-gw-primary">Tasks, Meetings & Communications</h2>
        <p class="mt-1 text-sm text-slate-500">Live tasks, phone calls, meetings, calendar events, notifications, and messages from your account.</p>
    </div>

    <div class="grid auto-rows-fr gap-4 md:grid-cols-2 xl:grid-cols-2">
        @foreach ($panelOrder as $panelKey)
            @if (isset($panels[$panelKey]))
                <x-dashboard-activity-panel :panel="$panels[$panelKey]" />
            @endif

            @if ($panelKey === 'my_tasks')
                <livewire:dashboard.upcoming-phone-calls-panel />
            @endif
        @endforeach
    </div>
</section>
