@php
    $summary = $todayScheduleSummary ?? [];
    $total = (int) ($summary['total'] ?? 0);
    $buckets = $summary['buckets'] ?? [];
    $tasks = $summary['tasks'] ?? ['label' => 'Tasks Due Today', 'count' => 0, 'url' => route('tasks.index')];
@endphp

<x-dropdown align="right" width="w-80 max-w-[calc(100vw-2rem)]" contentClasses="bg-white p-0">
    <x-slot name="trigger">
        <button
            type="button"
            class="gw-icon-btn-lg relative"
            title="{{ $total > 0 ? $total.' items scheduled today' : 'Today\'s schedule' }}"
        >
            <span class="sr-only">Today's schedule summary</span>
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            @if ($total > 0)
                <span class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-[#0F766E] px-1 text-[0.65rem] font-bold text-white">
                    {{ $total > 99 ? '99+' : $total }}
                </span>
            @endif
        </button>
    </x-slot>

    <x-slot name="content">
        <div class="w-80 max-w-[calc(100vw-2rem)]">
            <div class="border-b border-slate-100 bg-gradient-to-br from-[#083344] via-[#155E75] to-[#0F766E] px-4 py-3 text-white">
                <p class="text-xs font-bold uppercase tracking-wide text-gw-accent">Today's Schedule</p>
                <p class="mt-0.5 text-sm font-semibold">{{ $summary['date_label'] ?? now()->format('l, M j') }}</p>
                <p class="mt-1 text-xs text-cyan-100/90">Scheduled touchpoints and tasks due today.</p>
            </div>

            <div class="divide-y divide-slate-100 px-4 py-2">
                @forelse ($buckets as $bucket)
                    <div class="flex items-center justify-between py-2.5">
                        <span class="text-sm font-medium text-gw-primary">{{ $bucket['label'] }}</span>
                        <span @class([
                            'inline-flex min-w-[1.75rem] items-center justify-center rounded-full px-2 py-0.5 text-xs font-bold',
                            'bg-gw-accent-muted text-gw-primary' => ($bucket['count'] ?? 0) > 0,
                            'bg-slate-100 text-slate-500' => ($bucket['count'] ?? 0) === 0,
                        ])>
                            {{ $bucket['count'] ?? 0 }}
                        </span>
                    </div>
                @empty
                    <p class="py-3 text-sm text-slate-500">No schedule categories configured.</p>
                @endforelse

                <div class="flex items-center justify-between py-2.5">
                    <span class="text-sm font-medium text-gw-primary">{{ $tasks['label'] ?? 'Tasks Due Today' }}</span>
                    <span @class([
                        'inline-flex min-w-[1.75rem] items-center justify-center rounded-full px-2 py-0.5 text-xs font-bold',
                        'bg-gw-accent-muted text-gw-primary' => ($tasks['count'] ?? 0) > 0,
                        'bg-slate-100 text-slate-500' => ($tasks['count'] ?? 0) === 0,
                    ])>
                        {{ $tasks['count'] ?? 0 }}
                    </span>
                </div>
            </div>

            <div class="space-y-1 border-t border-slate-100 bg-slate-50 px-2 py-2">
                @if (! empty($summary['calendar_url']))
                    <a
                        href="{{ $summary['calendar_url'] }}"
                        class="flex items-center justify-between rounded-md px-3 py-2 text-sm font-semibold text-gw-primary transition hover:bg-white"
                    >
                        <span>Open Calendar</span>
                        <svg class="h-4 w-4 text-[#0F766E]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                @endif

                @if (! empty($summary['appointments_url']))
                    <a
                        href="{{ $summary['appointments_url'] }}"
                        class="flex items-center justify-between rounded-md px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-white"
                    >
                        <span>CRM Appointments</span>
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                @endif

                <a
                    href="{{ $tasks['url'] ?? route('tasks.index') }}"
                    class="flex items-center justify-between rounded-md px-3 py-2 text-sm font-semibold text-slate-700 transition hover:bg-white"
                >
                    <span>My Tasks</span>
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </a>
            </div>
        </div>
    </x-slot>
</x-dropdown>
